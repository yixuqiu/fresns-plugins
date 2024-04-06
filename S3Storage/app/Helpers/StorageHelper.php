<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Plugins\S3Storage\Helpers;

use App\Helpers\CacheHelper;
use App\Helpers\ConfigHelper;
use App\Helpers\FileHelper;
use App\Helpers\StrHelper;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    // get disk config
    public static function diskConfig(int $fileType): array
    {
        $configs = FileHelper::fresnsFileStorageConfigByType($fileType);

        $diskConfig = [
            'driver' => 's3',
            'key' => $configs['secretId'],
            'secret' => $configs['secretKey'],
            'region' => $configs['bucketRegion'],
            'bucket' => $configs['bucketName'],
            'url' => $configs['bucketDomain'],
            'endpoint' => $configs['antiLinkKey'] ?? $configs['bucketDomain'],
            'use_path_style_endpoint' => false,
            'throw' => false,
        ];

        return $diskConfig;
    }

    // build disk
    public static function buildDisk(int $fileType): void
    {
        $diskConfig = StorageHelper::diskConfig($fileType);

        config([
            'filesystems.default' => 's3',
            'filesystems.disks.s3' => $diskConfig,
        ]);
    }

    // get anti link url
    public static function fileUrl(?File $file, ?string $type = null): ?string
    {
        if (empty($file)) {
            return null;
        }

        $storageConfig = FileHelper::fresnsFileStorageConfigByType($file->type);
        $antiLinkKey = $storageConfig['antiLinkKey'];
        $antiLinkExpire = $storageConfig['antiLinkExpire'] ?? 30;

        StorageHelper::buildDisk($file->type);

        $fileName = $file->name;
        $filePath = $file->path;

        $fileUrl = null;
        switch ($type) {
            case 'imageConfigUrl':
                $configs = ConfigHelper::fresnsConfigByItemKeys([
                    'image_handle_position',
                    'image_thumb_config',
                ]);

                $newFilePath = FileHelper::fresnsFilePathByHandlePosition($configs['image_handle_position'], $configs['image_thumb_config'], $filePath);

                $fileUrl = Storage::temporaryUrl(
                    $newFilePath,
                    now()->addMinutes($antiLinkExpire),
                    [
                        'ResponseContentDisposition' => "attachment; filename={$fileName}",
                    ]
                );
                break;

            case 'imageRatioUrl':
                $configs = ConfigHelper::fresnsConfigByItemKeys([
                    'image_handle_position',
                    'image_thumb_ratio',
                ]);

                $newFilePath = FileHelper::fresnsFilePathByHandlePosition($configs['image_handle_position'], $configs['image_thumb_ratio'], $filePath);

                $fileUrl = Storage::temporaryUrl(
                    $newFilePath,
                    now()->addMinutes($antiLinkExpire),
                    [
                        'ResponseContentDisposition' => "attachment; filename={$fileName}",
                    ]
                );
                break;

            case 'imageSquareUrl':
                $configs = ConfigHelper::fresnsConfigByItemKeys([
                    'image_handle_position',
                    'image_thumb_square',
                ]);

                $newFilePath = FileHelper::fresnsFilePathByHandlePosition($configs['image_handle_position'], $configs['image_thumb_square'], $filePath);

                $fileUrl = Storage::temporaryUrl(
                    $newFilePath,
                    now()->addMinutes($antiLinkExpire),
                    [
                        'ResponseContentDisposition' => "attachment; filename={$fileName}",
                    ]
                );
                break;

            case 'imageBigUrl':
                $configs = ConfigHelper::fresnsConfigByItemKeys([
                    'image_handle_position',
                    'image_thumb_big',
                ]);

                $newFilePath = FileHelper::fresnsFilePathByHandlePosition($configs['image_handle_position'], $configs['image_thumb_big'], $filePath);

                $fileUrl = Storage::temporaryUrl(
                    $newFilePath,
                    now()->addMinutes($antiLinkExpire),
                    [
                        'ResponseContentDisposition' => "attachment; filename={$fileName}",
                    ]
                );
                break;

            case 'videoUrl':
                $configs = ConfigHelper::fresnsConfigByItemKeys([
                    'video_transcode_handle_position',
                    'video_transcode_parameter',
                ]);

                $newFilePath = FileHelper::fresnsFilePathByHandlePosition($configs['video_transcode_handle_position'], $configs['video_transcode_parameter'], $filePath);

                $fileUrl = Storage::temporaryUrl(
                    $newFilePath,
                    now()->addMinutes($antiLinkExpire),
                    [
                        'ResponseContentDisposition' => "attachment; filename={$fileName}",
                    ]
                );
                break;

            case 'videoPosterUrl':
                $configs = ConfigHelper::fresnsConfigByItemKeys([
                    'video_poster_handle_position',
                    'video_poster_parameter',
                ]);
                $videoPosterPath = $file->video_poster_path;

                $fileUrl = $videoPosterPath ? StrHelper::qualifyUrl($videoPosterPath, $storageConfig['bucketDomain']) : null;
                break;

            case 'audioUrl':
                $configs = ConfigHelper::fresnsConfigByItemKeys([
                    'audio_transcode_handle_position',
                    'audio_transcode_parameter',
                ]);

                $newFilePath = FileHelper::fresnsFilePathByHandlePosition($configs['audio_transcode_handle_position'], $configs['audio_transcode_parameter'], $filePath);

                $fileUrl = Storage::temporaryUrl(
                    $newFilePath,
                    now()->addMinutes($antiLinkExpire),
                    [
                        'ResponseContentDisposition' => "attachment; filename={$fileName}",
                    ]
                );
                break;

            case 'documentPreviewUrl':
                $fileUrl = FileHelper::fresnsFileDocumentPreviewUrl($file->extension);
                break;

            case 'originalUrl':
                $fileOriginalPath = $file->original_path ?? $file->path;

                $fileUrl = Storage::temporaryUrl(
                    $fileOriginalPath,
                    now()->addMinutes($antiLinkExpire),
                    [
                        'ResponseContentDisposition' => "attachment; filename={$fileName}",
                    ]
                );
                break;
        }

        return $fileUrl;
    }

    // get file info
    public static function fileInfo(string $fileIdOrFid): int|array
    {
        $cacheKey = 'fresns_file_info_'.$fileIdOrFid;
        $cacheTag = 'fresnsFiles';

        $fileInfo = CacheHelper::get($cacheKey, $cacheTag);
        if (empty($fileInfo)) {
            if (StrHelper::isPureInt($fileIdOrFid)) {
                $fileModel = File::where('id', $fileIdOrFid)->first();
            } else {
                $fileModel = File::where('fid', $fileIdOrFid)->first();
            }

            if (! $fileModel || ! $fileModel?->is_enabled) {
                return null;
            }

            $fileInfo = $fileModel->getFileInfo();

            // anti link
            $configs = FileHelper::fresnsFileStorageConfigByType($fileModel->type);
            if ($configs['antiLinkStatus']) {
                $urlKeys = [
                    'imageConfigUrl', 'imageRatioUrl', 'imageSquareUrl', 'imageBigUrl',
                    'videoUrl', 'videoPosterUrl',
                    'audioUrl',
                    'documentPreviewUrl',
                ];

                foreach ($urlKeys as $key) {
                    if (! array_key_exists($key, $fileInfo)) {
                        continue;
                    }

                    $fileInfo[$key] = StorageHelper::fileUrl($fileModel, $key);
                }
            }

            $cacheTime = CacheHelper::fresnsCacheTimeByFileType($fileModel->type, null, 2);
            CacheHelper::put($fileInfo, $cacheKey, $cacheTag, null, $cacheTime);
        }

        return $fileInfo;
    }
}
