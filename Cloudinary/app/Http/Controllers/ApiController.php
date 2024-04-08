<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Plugins\Cloudinary\Http\Controllers;

use App\Fresns\Api\Exceptions\ResponseException;
use App\Fresns\Api\Traits\ApiResponseTrait;
use App\Helpers\FileHelper;
use App\Helpers\PrimaryHelper;
use App\Models\File;
use App\Models\FileUsage;
use App\Utilities\FileUtility;
use Cloudinary\Api\ApiUtils;
use Cloudinary\Configuration\CloudConfig;
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
        $platformId = Cookie::get('fresns_plugin_cloudinary_platform_id');
        $authUid = Cookie::get('fresns_plugin_cloudinary_auth_uid');
        $usageType = Cookie::get('fresns_plugin_cloudinary_file_usage_type');
        $fileType = Cookie::get('fresns_plugin_cloudinary_file_type');

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

        $resourceType = match ($typeInt) {
            File::TYPE_IMAGE => 'image',
            File::TYPE_VIDEO => 'video',
            File::TYPE_AUDIO => 'video',
            default => 'raw',
        };

        $storageConfig = FileHelper::fresnsFileStorageConfigByType($typeInt);
        $storePath = FileHelper::fresnsFileStoragePath($typeInt, $usageTypeInt);

        $cloudConfig = new CloudConfig();
        $cloudConfig->setCloudConfig('cloud_name', $storageConfig['bucketName']);
        $cloudConfig->setCloudConfig('api_key', $storageConfig['secretId']);
        $cloudConfig->setCloudConfig('api_secret', $storageConfig['secretKey']);

        $publicId = (string) Str::ulid();

        // https://cloudinary.com/documentation/image_upload_api_reference#upload_required_parameters
        $paramsToSign = [
            'folder' => $storePath,
            'public_id' => $publicId,
            'timestamp' => time(),
        ];
        ApiUtils::signRequest($paramsToSign, $cloudConfig);

        $data = [
            'method' => 'POST',
            'url' => 'https://api.cloudinary.com/v1_1/'.$storageConfig['bucketName'].'/'.$resourceType.'/upload',
            'apiKey' => $paramsToSign['api_key'],
            'folder' => $paramsToSign['folder'],
            'publicId' => $paramsToSign['public_id'],
            'timestamp' => $paramsToSign['timestamp'],
            'signature' => $paramsToSign['signature'],
        ];

        // warning type
        $warningType = match ($request->warning) {
            'none' => File::WARNING_NONE,
            'nudity' => File::WARNING_NUDITY,
            'violence' => File::WARNING_VIOLENCE,
            'sensitive' => File::WARNING_SENSITIVE,
            default => File::WARNING_NONE,
        };

        $path = $storePath.'/'.$publicId.'.'.$request->extension;
        $videoPosterPath = $storePath.'/'.$publicId.'.jpg';

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
            'path' => $path,
            'videoPosterPath' => ($typeInt == File::TYPE_VIDEO) ? $videoPosterPath : null,
            'transcodingState' => File::TRANSCODING_STATE_WAIT,
            'originalPath' => null,
            'warningType' => $warningType,
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

        $data['fid'] = $fileModel->fid;

        return $this->success($data);
    }

    // update uploaded
    public function updateUploaded(Request $request)
    {
        $langTag = Cookie::get('fresns_plugin_cloudinary_lang_tag');
        $authUid = Cookie::get('fresns_plugin_cloudinary_auth_uid');

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
