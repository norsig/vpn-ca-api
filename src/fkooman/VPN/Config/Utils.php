<?php
/**
 * Copyright 2015 François Kooman <fkooman@tuxed.net>.
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
use RuntimeException;

class Utils
{
    public static function validateCommonName($commonName)
    {
        if (0 === preg_match('/^[a-zA-Z0-9-_.@]+$/', $commonName)) {
            throw new BadRequestException('invalid characters in common name');
        }

        if (64 < strlen($commonName)) {
            throw new BadRequestException('common name too long');
        }

        // MUST NOT be '..'
        if ('..' === $commonName) {
            throw new BadRequestException('common name cannot be ".."');
        }
    }

    public static function validateUserId($userId)
    {
        if (!is_null($userId)) {
            if (0 === preg_match('/^[a-zA-Z0-9-.@]+$/', $userId)) {
                throw new BadRequestException('invalid characters in user id');
            }

            if (64 < strlen($userId)) {
                throw new BadRequestException('user id too long');
            }

            // MUST NOT be '..'
            if ('..' === $userId) {
                throw new BadRequestException('user id cannot be ".."');
            }
        }
    }

    public static function exec($cmd)
    {
        exec($cmd, $output, $returnValue);

        if (0 !== $returnValue) {
            throw new RuntimeException(
                sprintf('command "%s" did not complete successfully', $cmd)
            );
        }
    }

    public static function getFile($filePath)
    {
        $fileContent = @file_get_contents($filePath);
        if (false === $fileContent) {
            throw new RuntimeException(
                sprintf('unable to read "%s"', $filePath)
            );
        }

        return $fileContent;
    }
}
