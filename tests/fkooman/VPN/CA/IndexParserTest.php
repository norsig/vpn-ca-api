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

namespace fkooman\VPN\CA;

use PHPUnit_Framework_TestCase;

class IndexParserTest extends PHPUnit_Framework_TestCase
{
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
                [
                    'user_id' => 'foo',
                    'name' => 'a_b_c_d_e',
                    'state' => 'E',
                    'exp' => 1455442030,
                    'rev' => false,
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
                [
                    'user_id' => 'foo',
                    'name' => 'a_b_c_d_e',
                    'state' => 'E',
                    'exp' => 1455442030,
                    'rev' => false,
                ],
            ],
            $i->getUserCertList('foo')
        );
    }

    public function testGetCertListForNonExistingUser()
    {
        $i = new IndexParser(__DIR__.'/data/index.txt');
        $this->assertSame(
            [
            ],
            $i->getUserCertList('baz')
        );
    }

    public function testGetCertInfo()
    {
        $i = new IndexParser(__DIR__.'/data/index.txt');
        $this->assertSame(
            [
                'user_id' => 'foo',
                'name' => 'foo',
                'state' => 'R',
                'exp' => 1487771854,
                'rev' => 1456236048,
            ],
            $i->getCertInfo('foo_foo')
        );
    }
}
