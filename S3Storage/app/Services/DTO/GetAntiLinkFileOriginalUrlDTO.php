<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Plugins\S3Storage\Services\DTO;

use Fresns\DTO\DTO;

class GetAntiLinkFileOriginalUrlDTO extends DTO
{
    public function rules(): array
    {
        return [
            'type' => ['integer', 'required', 'in:1,2,3,4'],
            'fileIdOrFid' => ['string', 'required'],
        ];
    }
}