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

include_once __DIR__ . '/../php-domain-name/src/domain_name.php';

/**
 * 判断IP是否属于指定的子网
 *
 * @param string $ip IP地址
 * @param string $cidr 子网，如8.8.8.0/24
 * @return bool
 */
function is_ip_in_cidr($ip, $cidr)
{
    list($net, $mask) = explode("/", $cidr);

    $spec_network = ip2long($net);
    $spec_mask = ~((1 << (32 - $mask)) - 1);

    $long_ip = ip2long($ip);

    $network = $long_ip & $spec_mask;

    return ($network == $spec_network);
}

/**
 * 载入IP数据文件
 *
 * 用于判断指定IP所属子网
 *
 * @return array
 */
function load_ip_data($file)
{
    $data = array();

    if (!file_exists($file)) {
        echo 'IP数据文件不存在：' . $file . '<br>';
        return $data;
    }

    $fh = fopen($file, 'r');

    if (!$fh) {
        echo '无法打开IP数据文件：' . $file . '<br>';
        return $data;
    }

    $i = 0;

    while ($line = fgets($fh)) {
        ++$i;
        $cols = explode(' ', $line);

        if (count($cols) != 2) {
            echo "IP数据文件第" . strval($i) . "行无效：" . $line . '<br>';
            continue;
        }

        if (!preg_match("/^\d+\.\d+\.\d+\.\d+\/\d{1,2}$/", $cols[0])) {
            echo "IP数据文件第" . strval($i) . "行无效：" . $line . '<br>';
            continue;
        }

        $data[$cols[0]] = trim($cols[1], "\n\r ");
    }

    fclose($fh);

    return $data;
}

/** @var array $ip_datas IP数据数组 */
$ip_datas = load_ip_data(__DIR__ . '/../ip.txt');

/**
 * 设置IP物理区域的回调函数
 *
 * @param array $var 数组，比如 array('foobar.com', '1.1.1.1', '')
 * @return array 比如 array('foobar.com', '1.1.1.1', 'China')
 */
function callback_set_region($var)
{
    global $ip_datas;

    $ip = $var[1];

    foreach ($ip_datas as $cidr => $region) {
        if (is_ip_in_cidr($ip, $cidr)) {
            $var[2] = $region;
            return $var;
        }
    }

    return $var;
}

/**
 * 输出以域名排序的结果
 *
 * @param array $array 以域名排序的数组
 */
function print_result_array_by_domain($array)
{
    echo '<table class="table table-hover">';
    echo '<thead>';
    echo '<tr>';
    echo '<th id="domain" class="domain_td">域名</th>';
    echo '<th id="ip">解析IP</th>';
    echo '<th id="region">地区</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($array as $no_host_domain_name => $results) {
        asort($results);

        foreach ($results as $result) {
            $ip_query_url = 'http://ip.cn/index.php?ip=' . $result[1];
            $tr_class = ($result[2]) ? '' : 'class="unknown_region"';

            echo '<tr ' . $tr_class . '>';
            // 在新窗口打开链接，并且隐藏引用地址
            echo '<td headers="domain" class="domain_td"><a href="javascript:;" onclick="open_new_window(this);">' . $result[0] . '</a></td>';

            echo '<td headers="ip">';
            // 在新窗口打开链接，并且隐藏引用地址
            echo '<a href="javascript:;" onclick="window.open(\'' . $ip_query_url . '\',\'_blank\')">' . $result[1] . '</a>';
            echo '</td>';

            echo '<td headers="region">' . ($result[2] ? $result[2] : '未知') . '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';
}

/**
 * 输出以IP排序的结果
 *
 * @param array $array IP排序的数组
 * @param string $title 表格标题
 * @param string $class_id CSS样式
 */
function print_result_array($array, $title, $class_id)
{
    $is_failed_results = $class_id == 'failed_results';

    echo '<table class="table table-hover">';
    echo '<caption><h3 class="' . $class_id . '">' . $title . '(' . strval(count($array)) . ')' . '</h3></caption>';
    echo '<thead>';
    echo '<tr>';
    echo '<th id="domain" class="domain_td">域名</th>';
    echo '<th id="ip">解析IP</th>';
    echo '<th id="region">地区</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($array as $result) {
        $ip_query_url = 'http://ip.cn/index.php?ip=' . $result[1];

        echo '<tr >';

        if ($is_failed_results) {
            echo '<td headers="domain" class="domain_td">' . $result[0] . '</td>';
            echo '<td headers="ip">' . $result[1] . '</td>';
        } else {
            // 在新窗口打开链接，并且隐藏引用地址
            echo '<td headers="domain" class="domain_td"><a href="javascript:;" onclick="open_new_window(this);">' . $result[0] . '</a></td>';

            echo '<td headers="ip">';
            // 在新窗口打开链接，并且隐藏引用地址
            echo '<a href="javascript:;" onclick="window.open(\'' . $ip_query_url . '\',\'_blank\')">' . $result[1] . '</a>';
            echo '</td>';
        }

        echo '<td headers="region">' . ($result[2] ? $result[2] : '未知') . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}

/**
 * 响应POST请求，并将等待查询的域名插入redis列表中
 *
 * @param Redis $redis
 * @param boolean $is_test 单元测试标记
 */
function do_post($redis, $is_test = False)
{
    $msg = '';

    if (isset($_POST['domains']) && $_POST['domains']) {
        $domains = preg_split('/\r\n|[\r\n]/', $_POST['domains']);

        $new_domains = array();
        $total = count($domains);
        $init_len = $total;
        $append_len = 0;
        $is_err = False;

        foreach ($domains as $domain) {
            try {
                $dn = DomainName\detect($domain);

                // 如果是不含主机字段的域名，则追加www。比如foobar.com，则追加www.foobar.com
                if (count($dn->getFeildHosts()) == 0) {
                    ++$append_len;
                    $new_domains[] = 'www.' . $domain;
                }

                $new_domains[] = $domain;
            } catch (DomainName\DomainNameException $e) {
                $is_err = True;
                $msg = '无效的域名：' . $domain;
                break;
            }
        }

        if (!$is_err) {
            $total += $append_len;
            $msg = '总共等待查询' . strval($total) . '个域名。';
            $msg .= '用户输入' . strval($init_len) . '个，';
            $msg .= '系统自动追加www二级域名' . strval($append_len) . '个。';

            if ($new_domains)
                call_user_func_array(array($redis, "lPush"),
                    array_merge(array('domains'), $new_domains));
        }
    }

    if (!$is_test) {
        if ($msg)
            header('Location: /index.php?msg=' . $msg, true, 302);
        else
            header('Location: /index.php', true, 302);
        exit(0);
    }
}

/**
 * 响应GET方法，从redis中取出解析结果
 *
 * @param Redis $redis
 * @return array 解析结果数组
 */
function do_get($redis)
{
    if (isset($_GET['action']) && $_GET['action'] == 'reset') {
        $redis->delete('results');
        header('Location: /index.php', true, 302);
        exit(0);
    }

    if (isset($_GET['msg']) && $_GET['msg'])
        echo '<h3><p class="text-danger">系统消息：' . $_GET['msg'] . '</p></h3>';

    # ('qq.com', '125.65.110.235')
    # ('wxwmkc.com', '125.65.110.235')
    $results_str = $redis->lRange('results', 0, -1);

    $results = array();

    foreach ($results_str as $r_str) {
        list($domain, $ip) = explode(':', $r_str);
        $results[] = array($domain, $ip, '');
    }

    return $results;
}

/**
 * 处理页面请求
 * 处理POST或者GET请求
 *
 * @param boolean $is_test 单元测试标记
 * @return null|array
 */
function do_request($is_test = false)
{
    $outputs = array();

    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $http_method = $_SERVER['REQUEST_METHOD'];

        if ($http_method == 'POST') {
            do_post($redis, $is_test);
        } else if ($http_method == 'GET') {
            $outputs = do_get($redis);
        } else {
            header('Location: /index.php', true, 302);
            exit(1);
        }
    } catch (RedisException $e) {
        echo '错误：连接Redis服务器失败。';
        exit(1);
    }

    if ($http_method != 'GET')
        return;

    $outputs_by_domain = array();
    $outputs_by_ip = array('known' => array(), 'unknown' => array(), 'failed' => array());
    $outputs = array_map("callback_set_region", $outputs);

    foreach ($outputs as $r) {
        $domain_name = $r[0];
        $ip = $r[1];
        $region = $r[2];

        try {
            $dn_obj = DomainName\detect($domain_name);
            $no_host_domain_name =
                $dn_obj->getFeildDomainName() . implode('', $dn_obj->getFeildTopLevelDomains());

            $outputs_by_domain[$no_host_domain_name][] = $r;
        } catch (DomainName\DomainNameException $e) {
            echo '错误：无效的域名 ' . $domain_name;
            exit(1);
        }

        if ($ip == '0.0.0.0') {
            $outputs_by_ip['failed'][] = $r;
            continue;
        }

        if ($region)
            $outputs_by_ip['known'][] = $r;
        else
            $outputs_by_ip['unknown'][] = $r;
    }

    ksort($outputs_by_domain);
    ksort($outputs_by_ip['failed']);
    ksort($outputs_by_ip['known']);
    ksort($outputs_by_ip['unknown']);

    return array($outputs_by_domain, $outputs_by_ip);
}