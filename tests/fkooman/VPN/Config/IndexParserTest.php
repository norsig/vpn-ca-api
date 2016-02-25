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

use PHPUnit_Framework_TestCase;

class IndexParserTest extends PHPUnit_Framework_TestCase
{
    // XXX add a test for expired

    public function testGetCertList()
    {
        $i = new IndexParser(__DIR__.'/data/index.txt');
        $this->assertSame(
            [
                [
                    'user_id' => 'foo',
                    'name' => 'foo',
                    'state' => 'R',
                    'exp' => 1487771854,
                    'rev' => 1456236048,
                ],
                [
                    'user_id' => 'bar',
                    'name' => 'test',
                    'state' => 'V',
                    'exp' => 1487771884,
                    'rev' => false,
                ],
                [
                    'user_id' => 'bar',
                    'name' => 'Test',
                    'state' => 'V',
                    'exp' => 1487779835,
                    'rev' => false,
                ],
                [
                    'user_id' => 'bar',
                    'name' => 'lkjlkjlkj',
                    'state' => 'R',
                    'exp' => 1487863193,
                    'rev' => 1456327200,
                ],
            ],
            $i->getCertList()
        );
    }

    public function testGetCertListForUser()
    {
        $i = new IndexParser(__DIR__.'/data/index.txt');
        $this->assertSame(
            [
                [
                    'user_id' => 'foo',
                    'name' => 'foo',
                    'state' => 'R',
                    'exp' => 1487771854,
                    'rev' => 1456236048,
                ],
            ],
            $i->getCertList('foo')
        );
    }

    public function testGetCertListForNonExistingUser()
    {
        $i = new IndexParser(__DIR__.'/data/index.txt');
        $this->assertSame(
            [
            ],
            $i->getCertList('baz')
        );
    }
}
