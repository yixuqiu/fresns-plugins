<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Plugins\SharePoster\Http\Controllers;

use App\Fresns\Api\Exceptions\ResponseException;
use App\Fresns\Api\Traits\ApiHeaderTrait;
use App\Fresns\Api\Traits\ApiResponseTrait;
use App\Helpers\ConfigHelper;
use App\Helpers\PrimaryHelper;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Plugins\SharePoster\Helpers\PosterHelper;
use Plugins\SharePoster\Http\DTO\ApiDTO;

class ApiController extends Controller
{
    use ApiHeaderTrait;
    use ApiResponseTrait;

    public function generate(Request $request)
    {
        $dtoRequest = new ApiDTO($request->all());

        $type = $dtoRequest->type;
        $fsid = $dtoRequest->fsid;

        // if ($type == 'hashtag') {
        //     $fsid = StrHelper::slug($fsid);
        // }

        try {
            $model = PrimaryHelper::fresnsModelByFsid($type, $fsid);
        } catch (\Exception $e) {
            $model = null;
        }

        if (empty($model)) {
            throw new ResponseException(30002);
        }

        $configArr = ConfigHelper::fresnsConfigByItemKey('shareposter_config');

        $config = $configArr[$type];

        $langTag = $dtoRequest->langTag ?? $this->langTag();

        if (! $config['cache']) {
            $url = PosterHelper::generatePoster($type, $model, $langTag);

            if (empty($url)) {
                throw new ResponseException(32302);
            }

            return $this->success([
                'url' => $url,
            ]);
        }

        if ($type == 'user') {
            $fsid = $model->uid;
        }

        $filePath = PosterHelper::getPosterPath($type, $model);

        $disk = Storage::disk('public');
        $url = $disk->url($filePath);

        if (! $disk->exists($filePath) || empty($url)) {
            $url = PosterHelper::generatePoster($type, $model, $langTag);
        }

        if (empty($url)) {
            throw new ResponseException(32302);
        }

        return $this->success([
            'url' => $url,
        ]);
    }
}
