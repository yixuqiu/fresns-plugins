<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Plugins\S3Storage\Http\Controllers;

use App\Fresns\Api\Exceptions\ResponseException;
use App\Fresns\Api\Traits\ApiResponseTrait;
use App\Helpers\FileHelper;
use App\Helpers\PrimaryHelper;
use App\Models\File;
use App\Models\FileUsage;
use App\Utilities\FileUtility;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class ApiController extends Controller
{
    use ApiResponseTrait;

    // get upload token
    public function uploadToken(Request $request)
    {
        $platformId = Cookie::get('fresns_plugin_s3_storage_platform_id');
        $authUid = Cookie::get('fresns_plugin_s3_storage_auth_uid');
        $usageType = Cookie::get('fresns_plugin_s3_storage_file_usage_type');
        $fileType = Cookie::get('fresns_plugin_s3_storage_file_type');

        $typeInt = match ($fileType) {
            'image' => File::TYPE_IMAGE,
            'video' => File::TYPE_VIDEO,
            'audio' => File::TYPE_AUDIO,
            'document' => File::TYPE_DOCUMENT,
            default => null,
        };

        $usageTypeInt = match ($usageType) {
            'userAvatar' => FileUsage::TYPE_USER,
            'userBanner' => FileUsage::TYPE_USER,
            'conversation' => FileUsage::TYPE_CONVERSATION,
            'post' => FileUsage::TYPE_POST,
            'comment' => FileUsage::TYPE_COMMENT,
            'postDraft' => FileUsage::TYPE_POST,
            'commentDraft' => FileUsage::TYPE_COMMENT,
            default => FileUsage::TYPE_OTHER,
        };

        $storePath = FileHelper::fresnsFileStoragePath($typeInt, $usageTypeInt);
        $fileNewName = (string) Str::ulid();
        $path = $storePath.'/'.$fileNewName.'.'.$request->extension;

        $wordBody = [
            'type' => $typeInt,
            'path' => $path,
            'minutes' => 10,
        ];

        $fresnsResp = \FresnsCmdWord::plugin('S3Storage')->getUploadToken($wordBody);

        if ($fresnsResp->isErrorResponse()) {
            return $fresnsResp->getErrorResponse();
        }

        // warning type
        $warningType = match ($request->warning) {
            'none' => File::WARNING_NONE,
            'nudity' => File::WARNING_NUDITY,
            'violence' => File::WARNING_VIOLENCE,
            'sensitive' => File::WARNING_SENSITIVE,
            default => File::WARNING_NONE,
        };

        $fileInfo = [
            'type' => $typeInt,
            'name' => $request->name,
            'mime' => $request->mime,
            'extension' => $request->extension,
            'size' => $request->size,
            'width' => $request->width,
            'height' => $request->height,
            'duration' => $request->duration,
            'sha' => $request->sha,
            'shaType' => $request->shaType,
            'warningType' => $warningType,
            'path' => $path,
            'transcodingState' => File::TRANSCODING_STATE_WAIT,
            'videoPosterPath' => null,
            'originalPath' => null,
            'uploaded' => false,
        ];

        $usageInfo = [
            'usageType' => $request->attributes->get('usageType'),
            'platformId' => $platformId,
            'tableName' => $request->attributes->get('tableName'),
            'tableColumn' => $request->attributes->get('tableColumn'),
            'tableId' => $request->attributes->get('tableId'),
            'tableKey' => $request->attributes->get('tableKey'),
            'sortOrder' => $request->sortOrder,
            'moreInfo' => $request->moreInfo,
            'aid' => null,
            'uid' => $authUid,
            'remark' => null,
        ];

        $fileModel = FileUtility::saveFileInfo($fileInfo, $usageInfo);

        if (! $fileModel) {
            throw new ResponseException(30008);
        }

        $data = $fresnsResp->getData();
        $data['fid'] = $fileModel->fid;

        return $this->success($data);
    }

    // update uploaded
    public function updateUploaded(Request $request)
    {
        $langTag = Cookie::get('fresns_plugin_s3_storage_lang_tag');
        $authUid = Cookie::get('fresns_plugin_s3_storage_auth_uid');

        $request->headers->set('X-Fresns-Client-Lang-Tag', $langTag);
        $request->headers->set('X-Fresns-Uid', $authUid);

        // check file
        $file = File::whereFid($request->fid)->first();
        if (empty($file)) {
            throw new ResponseException(37600);
        }

        if (! $file->is_enabled) {
            throw new ResponseException(37601);
        }

        $authUserId = PrimaryHelper::fresnsPrimaryId('user', $authUid);
        $checkUploader = FileUsage::where('file_id', $file->id)->where('user_id', $authUserId)->first();

        if (! $checkUploader) {
            throw new ResponseException(37602);
        }

        $file->update([
            'is_uploaded' => true,
        ]);

        $usageInfo = [
            'tableName' => $request->attributes->get('tableName'),
            'tableColumn' => $request->attributes->get('tableColumn'),
            'tableId' => $request->attributes->get('tableId'),
            'tableKey' => $request->attributes->get('tableKey'),
        ];

        $fileInfo = FileHelper::fresnsFileInfoById($file->fid, $usageInfo);

        return $this->success($fileInfo);
    }
}
