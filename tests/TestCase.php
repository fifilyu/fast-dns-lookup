<?php
/**
 * Copyright (c) 2016, Fifi Lyu. All rights reserved.
 *
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/util.php';

class FastDnsLookupTestCase extends PHPUnit_Framework_TestCase
{
    protected static $ip_txt = __DIR__ . '/ip.txt';
    protected static $ip_datas;

    public static function setUpBeforeClass()
    {
        file_put_contents(self::$ip_txt, '8.8.8.0/24 USA' . PHP_EOL);
        $_POST = array('domains' => "foobar.com\nbaz.com");

        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            $redis->lPush('results', 'baz.com:0.0.0.0');
            $redis->lPush('results', 'www.baz.com:0.0.0.0');
            $redis->lPush('results', 'foobar.com:8.8.8.8');
            $redis->lPush('results', 'www.foobar.com:3.3.3.3');
        } catch (RedisException $e) {
            echo '错误：连接Redis服务器失败。';
        }
    }

    public static function tearDownAfterClass()
    {
        if (file_exists(self::$ip_txt))
            unlink(self::$ip_txt);

        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            $redis->del('domains');
            $redis->del('results');
        } catch (RedisException $e) {
            echo '错误：连接Redis服务器失败。';
        }
    }

    public function testIsIpInCidr()
    {
        $this->assertTrue(is_ip_in_cidr('8.8.8.8', '8.8.8.0/24'));
        $this->assertTrue(is_ip_in_cidr('4.4.4.4', '4.4.0.0/16'));
        $this->assertFalse(is_ip_in_cidr('1.2.3.4', '1.1.0.0/16'));
    }

    public function testLoadIpData()
    {
        self::$ip_datas = load_ip_data(self::$ip_txt);
        $this->assertCount(1, self::$ip_datas);
        $this->assertTrue(isset(self::$ip_datas['8.8.8.0/24']));
        $this->assertEquals('USA', self::$ip_datas['8.8.8.0/24']);
    }

    public function testCallbackSetRegion()
    {
        global $ip_datas;
        $ip_datas = self::$ip_datas;
        $result = callback_set_region(array('foobar.com', '8.8.8.8', ''));

        $this->assertCount(3, $result);
        $this->assertEquals('foobar.com', $result[0]);
        $this->assertEquals('8.8.8.8', $result[1]);
        $this->assertEquals('USA', $result[2]);
    }

    public function testDoPost()
    {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);

            do_post($redis, true);

            $domains = $redis->lRange('domains', 0, -1);

            $this->assertCount(4, $domains);
            $this->assertEquals('baz.com', $domains[0]);
            $this->assertEquals('www.baz.com', $domains[1]);
            $this->assertEquals('foobar.com', $domains[2]);
            $this->assertEquals('www.foobar.com', $domains[3]);
        } catch (RedisException $e) {
            echo '错误：连接Redis服务器失败。';
        }
    }

    public function testDoGet()
    {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);

            $outputs = do_get($redis);
            $this->assertCount(4, $outputs);
            $this->assertEquals(array('www.foobar.com', '3.3.3.3', ''), $outputs[0]);
            $this->assertEquals(array('foobar.com', '8.8.8.8', ''), $outputs[1]);
            $this->assertEquals(array('www.baz.com', '0.0.0.0', ''), $outputs[2]);
            $this->assertEquals(array('baz.com', '0.0.0.0', ''), $outputs[3]);
        } catch (RedisException $e) {
            echo '错误：连接Redis服务器失败。';
        }
    }

    public function testDoRequest()
    {
        global $ip_datas;
        $ip_datas = self::$ip_datas;

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertNull(do_request(true));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        list($outputs_by_domain, $outputs_by_ip) = do_request(true);

        $this->assertCount(2, $outputs_by_domain);

        $this->assertTrue(isset($outputs_by_domain['baz.com']));
        $this->assertCount(2, $outputs_by_domain['baz.com']);
        $this->assertEquals(array('www.baz.com', '0.0.0.0', ''), $outputs_by_domain['baz.com'][0]);
        $this->assertEquals(array('baz.com', '0.0.0.0', ''), $outputs_by_domain['baz.com'][1]);

        $this->assertTrue(isset($outputs_by_domain['foobar.com']));
        $this->assertCount(2, $outputs_by_domain['foobar.com']);
        $this->assertEquals(array('www.foobar.com', '3.3.3.3', ''), $outputs_by_domain['foobar.com'][0]);
        $this->assertEquals(array('foobar.com', '8.8.8.8', 'USA'), $outputs_by_domain['foobar.com'][1]);

        $this->assertCount(3, $outputs_by_ip);

        $this->assertTrue(isset($outputs_by_ip['known']));
        $this->assertCount(1, $outputs_by_ip['known']);
        $this->assertEquals(array('foobar.com', '8.8.8.8', 'USA'), $outputs_by_ip['known'][0]);

        $this->assertTrue(isset($outputs_by_ip['unknown']));
        $this->assertCount(1, $outputs_by_ip['unknown']);
        $this->assertEquals(array('www.foobar.com', '3.3.3.3', ''), $outputs_by_ip['unknown'][0]);

        $this->assertTrue(isset($outputs_by_ip['failed']));
        $this->assertCount(2, $outputs_by_ip['failed']);
        $this->assertEquals(array('www.baz.com', '0.0.0.0', ''), $outputs_by_ip['failed'][0]);
        $this->assertEquals(array('baz.com', '0.0.0.0', ''), $outputs_by_ip['failed'][1]);
    }
}