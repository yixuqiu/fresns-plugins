<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Plugins\S3Storage\Services;

use App\Fresns\Words\File\DTO\GetAntiLinkFileInfoDTO;
use App\Fresns\Words\File\DTO\GetAntiLinkFileInfoListDTO;
use App\Fresns\Words\File\DTO\GetAntiLinkFileOriginalUrlDTO;
use App\Fresns\Words\File\DTO\GetUploadTokenDTO;
use App\Fresns\Words\File\DTO\LogicalDeletionFilesDTO;
use App\Fresns\Words\File\DTO\PhysicalDeletionFilesDTO;
use App\Fresns\Words\File\DTO\UploadFileDTO;
use App\Helpers\CacheHelper;
use App\Helpers\FileHelper;
use App\Helpers\PrimaryHelper;
use App\Helpers\StrHelper;
use App\Models\File;
use App\Models\FileUsage;
use App\Utilities\ConfigUtility;
use App\Utilities\FileUtility;
use Fresns\CmdWordManager\Traits\CmdWordResponseTrait;
use Illuminate\Support\Facades\Storage;
use Plugins\S3Storage\Helpers\StorageHelper;

class CmdWordService
{
    use CmdWordResponseTrait;

    // getUploadToken
    public function getUploadToken($wordBody)
    {
        /**
         * S3 upload are not supported by the storage provider.
         */
        // return $this->failure(32104, ConfigUtility::getCodeMessage(32104));

        $dtoWordBody = new GetUploadTokenDTO($wordBody);

        StorageHelper::buildDisk($dtoWordBody->type);

        $expiration = now()->addMinutes($dtoWordBody->minutes)->setTimezone('UTC');

        $presignedUrl = Storage::temporaryUploadUrl($dtoWordBody->path, $expiration);

        $data = [
            'method' => 'PUT',
            'url' => $presignedUrl['url'],
            'headers' => $presignedUrl['headers'],
            'activeMinutes' => $dtoWordBody->minutes,
            'expiration' => date('Y-m-d H:i:s', strtotime($expiration)),
        ];

        return $this->success($data);
    }

    // uploadFile
    public function uploadFile($wordBody)
    {
        $dtoWordBody = new UploadFileDTO($wordBody);

        $diskConfig = StorageHelper::diskConfig($dtoWordBody->type);

        $bodyInfo = [
            'type' => $dtoWordBody->type,
            'warningType' => $dtoWordBody->warningType,

            'usageType' => $dtoWordBody->usageType,
            'platformId' => $dtoWordBody->platformId,
            'tableName' => $dtoWordBody->tableName,
            'tableColumn' => $dtoWordBody->tableColumn,
            'tableId' => $dtoWordBody->tableId,
            'tableKey' => $dtoWordBody->tableKey,
            'moreInfo' => $dtoWordBody->moreInfo,
            'aid' => $dtoWordBody->aid,
            'uid' => $dtoWordBody->uid,
        ];

        $fileModel = FileUtility::uploadFile($bodyInfo, $diskConfig, $dtoWordBody->file);

        $fileInfo = FileHelper::fresnsFileInfoById($fileModel->fid, $bodyInfo);

        if (empty($fileInfo)) {
            return $this->failure(32104, ConfigUtility::getCodeMessage(32104));
        }

        return $this->success($fileInfo);
    }

    // getAntiLinkFileInfo
    public function getAntiLinkFileInfo($wordBody)
    {
        $dtoWordBody = new GetAntiLinkFileInfoDTO($wordBody);

        $fileInfo = StorageHelper::fileInfo($dtoWordBody->fileIdOrFid);

        return $this->success($fileInfo);
    }

    // getAntiLinkFileInfoList
    public function getAntiLinkFileInfoList($wordBody)
    {
        $dtoWordBody = new GetAntiLinkFileInfoListDTO($wordBody);

        $data = [];
        foreach ($dtoWordBody->fileIdsOrFids as $id) {
            $data[] = StorageHelper::fileInfo($id);
        }

        return $this->success($data);
    }

    // getAntiLinkFileOriginalUrl
    public function getAntiLinkFileOriginalUrl($wordBody)
    {
        $dtoWordBody = new GetAntiLinkFileOriginalUrlDTO($wordBody);

        if (StrHelper::isPureInt($dtoWordBody->fileIdOrFid)) {
            $file = PrimaryHelper::fresnsModelById('file', $dtoWordBody->fileIdOrFid);
        } else {
            $file = PrimaryHelper::fresnsModelByFsid('file', $dtoWordBody->fileIdOrFid);
        }

        $originalUrl = StorageHelper::fileUrl($file, 'originalUrl');

        return $this->success([
            'originalUrl' => $originalUrl,
        ]);
    }

    // logicalDeletionFiles
    public function logicalDeletionFiles($wordBody)
    {
        $dtoWordBody = new LogicalDeletionFilesDTO($wordBody);

        FileUtility::logicalDeletionFiles($dtoWordBody->fileIdsOrFids);

        return $this->success();
    }

    // physicalDeletionFiles
    public function physicalDeletionFiles($wordBody)
    {
        $dtoWordBody = new PhysicalDeletionFilesDTO($wordBody);

        // Storage disk
        StorageHelper::buildDisk($dtoWordBody->type);

        foreach ($dtoWordBody->fileIdsOrFids as $id) {
            if (StrHelper::isPureInt($id)) {
                $file = File::where('id', $id)->first();
            } else {
                $file = File::where('fid', $id)->first();
            }

            if (empty($file)) {
                continue;
            }

            FileUsage::where('file_id', $file->id)->delete();

            $deleteStatus = Storage::delete($file->path);

            if (! $deleteStatus) {
                return $this->failure(21006);
            }

            if ($file->type == File::TYPE_VIDEO && $file->video_poster_path) {
                Storage::delete($file->video_poster_path);
            }

            if ($file->original_path) {
                Storage::delete($file->original_path);
            }

            $file->update([
                'physical_deletion' => true,
            ]);

            $file->delete();

            // forget cache
            CacheHelper::clearDataCache('file', $file->fid);
        }

        return $this->success();
    }
}
