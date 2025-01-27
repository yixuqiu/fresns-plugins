<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Plugins\SmtpEmail\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\PluginHelper;
use App\Models\Config;
use App\Utilities\AppUtility;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class WebController extends Controller
{
    /**
     *  mail setting.
     */
    public function settings()
    {
        $version = PluginHelper::fresnsPluginVersionByFskey('SmtpEmail');
        $marketUrl = AppUtility::MARKETPLACE_URL.'/open-source';

        $content = Config::query()->whereIn('item_key', [
            'fresnsemail_smtp_host',
            'fresnsemail_smtp_port',
            'fresnsemail_smtp_username',
            'fresnsemail_smtp_password',
            'fresnsemail_verify_type',
            'fresnsemail_from_mail',
            'fresnsemail_from_name',
        ])->pluck('item_value', 'item_key');

        $locale = \request()->cookie('fresns_panel_locale');

        return view('SmtpEmail::setting', compact('content', 'version', 'marketUrl', 'locale'));
    }

    /**
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function postSettings(Request $request)
    {
        collect($request->only([
            'fresnsemail_smtp_host',
            'fresnsemail_smtp_port',
            'fresnsemail_smtp_username',
            'fresnsemail_smtp_password',
            'fresnsemail_verify_type',
            'fresnsemail_from_mail',
            'fresnsemail_from_name',
        ]))->each(function (?string $value, string $key) {
            $fresnsConfigs = Config::query()->firstWhere('item_key', $key) ?: Config::query()->newModelInstance();
            $fresnsConfigs->item_key = $key;
            $fresnsConfigs->item_value = $value ?: '';
            $fresnsConfigs->item_type = 'string';
            $fresnsConfigs->saveOrFail();
        });

        $keys = [
            'fresnsemail_smtp_host',
            'fresnsemail_smtp_port',
            'fresnsemail_smtp_username',
            'fresnsemail_smtp_password',
            'fresnsemail_verify_type',
            'fresnsemail_from_mail',
            'fresnsemail_from_name',
        ];
        CacheHelper::forgetFresnsConfigs($keys);

        return back()->with('success', __('success!'));
    }

    /**
     * test send.
     */
    public function sendTest(Request $request)
    {
        $email = $request->input('email');

        $validator = Validator::make($request->post(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 20000,
                'message' => $validator->errors()->all()[0],
            ]);
        }

        try {
            $wordBody = [
                'email' => $email,
                'title' => 'Fresns test email',
                'content' => 'This is a Fresns software testing email',
            ];
            $fresnsResp = \FresnsCmdWord::plugin('SmtpEmail')->sendEmail($wordBody);

            if ($fresnsResp->isErrorResponse()) {
                return $fresnsResp->getErrorResponse();
            }
        } catch (\Exception $exception) {
            return [
                'code' => 20000,
                'message' => $exception->getMessage(),
                'data' => [],
            ];
        }

        return [
            'code' => 0,
            'message' => 'ok',
            'data' => [],
        ];
    }
}
