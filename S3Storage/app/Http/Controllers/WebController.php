<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Plugins\S3Storage\Http\Controllers;

use App\Helpers\ConfigHelper;
use App\Helpers\FileHelper;
use App\Helpers\PrimaryHelper;
use App\Models\File;
use App\Utilities\ConfigUtility;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebController extends Controller
{
    public function upload(Request $request)
    {
        $langTag = $request->attributes->get('langTag');
        $authUid = $request->attributes->get('authUid');
        $usageType = $request->attributes->get('usageType');
        $fileType = $request->attributes->get('fileType');

        $authUserId = PrimaryHelper::fresnsPrimaryId('user', $authUid);

        $publishType = match ($usageType) {
            'post' => 'post',
            'comment' => 'comment',
            'postDraft' => 'post',
            'commentDraft' => 'comment',
            default => null,
        };

        $uploadConfig = [];
        if ($publishType) {
            $editorConfig = ConfigUtility::getEditorConfigByType($publishType, $authUserId, $langTag);

            $uploadConfig = $editorConfig[$fileType];
        }

        $typeInt = match ($fileType) {
            'image' => File::TYPE_IMAGE,
            'video' => File::TYPE_VIDEO,
            'audio' => File::TYPE_AUDIO,
            'document' => File::TYPE_DOCUMENT,
            default => null,
        };

        $inputAccept = FileHelper::fresnsFileAcceptByType($typeInt);
        $extensionNames = match ($fileType) {
            'image' => ConfigHelper::fresnsConfigByItemKey('image_extension_names'),
            'video' => ConfigHelper::fresnsConfigByItemKey('video_extension_names'),
            'audio' => ConfigHelper::fresnsConfigByItemKey('audio_extension_names'),
            'document' => ConfigHelper::fresnsConfigByItemKey('document_extension_names'),
            default => null,
        };
        $maxUploadNumber = $request->attributes->get('maxUploadNumber');
        $maxSize = $uploadConfig['maxSize'] ?? match ($fileType) {
            'image' => ConfigHelper::fresnsConfigByItemKey('image_max_size'),
            'video' => ConfigHelper::fresnsConfigByItemKey('video_max_size'),
            'audio' => ConfigHelper::fresnsConfigByItemKey('audio_max_size'),
            'document' => ConfigHelper::fresnsConfigByItemKey('document_max_size'),
            default => 0,
        };
        $maxDuration = match ($fileType) {
            'image' => 0,
            'video' => ConfigHelper::fresnsConfigByItemKey('video_max_duration'),
            'audio' => ConfigHelper::fresnsConfigByItemKey('audio_max_duration'),
            'document' => 0,
            default => 0,
        };

        $fsLang = ConfigHelper::fresnsConfigLanguagePack($langTag);

        return view('S3Storage::upload', compact('fileType', 'inputAccept', 'extensionNames', 'maxUploadNumber', 'maxSize', 'maxDuration', 'fsLang'));
    }
}
