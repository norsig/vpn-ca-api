<?php
/**
 * Copyright 2016 François Kooman <fkooman@tuxed.net>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace fkooman\VPN\Config;

use fkooman\Http\Exception\BadRequestException;

class InputValidation
{
    const COMMON_NAME_PATTERN = '/^[a-zA-Z0-9-_.@]+$/';
    const USER_ID_PATTERN = '/^[a-zA-Z0-9-.@]+$/';  // like common_name, but no "_"

    public static function commonName($commonName)
    {
        if (!is_string($commonName)) {
            throw new BadRequestException('invalid type for "common_name"');
        }
        if (0 === strlen($commonName)) {
            throw new BadRequestException('empty "common_name"');
        }
        if (0 === preg_match(self::COMMON_NAME_PATTERN, $commonName)) {
            throw new BadRequestException('invalid value for "common_name"');
        }
        if ('..' === $commonName) {
            throw new BadRequestException('"common_name" cannot be ".."');
        }

        return $commonName;
    }

    public static function userId($userId)
    {
        if (!is_string($userId)) {
            throw new BadRequestException('invalid type for "user_id"');
        }
        if (0 === strlen($userId)) {
            throw new BadRequestException('empty "user_id"');
        }
        if (0 === preg_match(self::USER_ID_PATTERN, $userId)) {
            throw new BadRequestException('invalid value for "user_id"');
        }
        if ('..' === $userId) {
            throw new BadRequestException('"user_id" cannot be ".."');
        }

        return $userId;
    }

    public static function certType($certType)
    {
        if ('client' !== $certType && 'server' != $certType) {
            throw new BadRequestException('invalid "cert_type"');
        }

        return $certType;
    }
}
