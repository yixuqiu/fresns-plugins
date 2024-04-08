<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Plugins\S3Storage\Http\Middleware;

use App\Helpers\AppHelper;
use App\Helpers\CacheHelper;
use App\Models\File;
use App\Utilities\ConfigUtility;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class CheckAccess
{
    public function handle(Request $request, Closure $next)
    {
        // verify access token
        $fresnsResp = \FresnsCmdWord::plugin('Fresns')->verifyAccessToken([
            'accessToken' => $request->accessToken,
            'userLogin' => true,
        ]);

        $langTag = $fresnsResp->getData('langTag') ?? AppHelper::getLangTag();
        View::share('langTag', $langTag);

        if ($fresnsResp->isErrorResponse()) {
            $code = $fresnsResp->getCode();
            $message = $fresnsResp->getMessage().' (accessToken)';

            return response()->view('S3Storage::error', compact('code', 'message'), 403);
        }

        // postMessageKey
        $postMessageKey = $request->postMessageKey;
        View::share('postMessageKey', $postMessageKey);

        // postMessageKey
        $uploadInfo = $request->uploadInfo;
        if (empty($uploadInfo)) {
            $code = 30001;
            $message = ConfigUtility::getCodeMessage(30001, 'Fresns', $langTag).' (uploadInfo)';

            return response()->view('S3Storage::error', compact('code', 'message'), 403);
        }

        // usageType,usageFsid,type
        $uploadInfoArr = explode(',', $uploadInfo);

        // check upload perm
        $type = match ($uploadInfoArr[2] ?? null) {
            'image' => File::TYPE_IMAGE,
            'video' => File::TYPE_VIDEO,
            'audio' => File::TYPE_AUDIO,
            'document' => File::TYPE_DOCUMENT,
            default => null,
        };
        $wordBody = [
            'uid' => $fresnsResp->getData('uid'),
            'usageType' => $uploadInfoArr[0] ?? null,
            'usageFsid' => $uploadInfoArr[1] ?? null,
            'type' => $type,
            'extension' => null,
            'size' => null,
            'duration' => null,
        ];
        $permResp = \FresnsCmdWord::plugin('Fresns')->checkUploadPerm($wordBody);

        if ($permResp->isErrorResponse()) {
            $code = $permResp->getCode();
            $message = $permResp->getMessage();

            return response()->view('S3Storage::error', compact('code', 'message'), 403);
        }

        // request attributes
        $request->attributes->add([
            'langTag' => $langTag,
            'timezone' => $fresnsResp->getData('timezone'),
            'authUid' => $fresnsResp->getData('uid'),
            'usageType' => $uploadInfoArr[0],
            'usageFsid' => $uploadInfoArr[1],
            'fileType' => $uploadInfoArr[2],
            'maxUploadNumber' => $permResp->getData('maxUploadNumber'),
        ]);

        // plugin auth info
        $authUlid = (string) Str::ulid();

        CacheHelper::put('S3Storage', $authUlid, 'fresnsPluginAuth', null, now()->addMinutes(15));

        Cookie::queue('fresns_plugin_s3_storage_platform_id', $fresnsResp->getData('platformId'));
        Cookie::queue('fresns_plugin_s3_storage_lang_tag', $langTag);
        Cookie::queue('fresns_plugin_s3_storage_auth_ulid', $authUlid);
        Cookie::queue('fresns_plugin_s3_storage_auth_uid', $fresnsResp->getData('uid'));
        Cookie::queue('fresns_plugin_s3_storage_file_usage_type', $uploadInfoArr[0]);
        Cookie::queue('fresns_plugin_s3_storage_file_usage_fsid', $uploadInfoArr[1]);
        Cookie::queue('fresns_plugin_s3_storage_file_type', $uploadInfoArr[2]);

        return $next($request);
    }
}