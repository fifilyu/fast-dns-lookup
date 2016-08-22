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

include_once '../includes/util.php';

list($outputs_by_domain, $outputs_by_ip) = do_request();

$total_by_domain = count($outputs_by_domain);
$total_all = $total_by_domain + count($outputs_by_ip);

// 以域名排序时，分成3组显示
$group_len = floor($total_by_domain / 3);
$left_domain = $total_by_domain % 3;

if ($left_domain == 0) {
    $group_len_1 = $group_len;
    $group_len_2 = $group_len;
    $group_len_3 = $group_len;
} else {
    $group_len_1 = $group_len + ($left_domain >= 1 ? 1 : 0);
    $group_len_2 = $group_len + ($left_domain == 2 ? 1 : 0);;
    $group_len_3 = $group_len;
}

$offset_1 = 0;
$offset_2 = $group_len_1;
$offset_3 = $group_len_1 + $group_len_2;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>快速域名解析查询系统</title>
    <link href='/css/fastdnslookup.css' rel='stylesheet' type='text/css'/>
    <link href='/css/bootstrap.css' rel='stylesheet' type='text/css'/>
    <script src="/js/fastdnslookup.js"></script>
    <script src="/js/jquery.min.js"></script>
</head>
<body>
<div class="container">
    <h1>快速域名解析查询系统</h1>
    <form method="post" action="/index.php">
        <div class="bootstrap-table">
            <div class="fixed-table-container" style="padding-bottom: 10px;">
                <div class="fixed-table-body">
                    <table class="table table-hover">
                        <tr>
                            <td>
                                <textarea name="domains" cols="100" rows="10" placeholder="请输入域名，一行一个" ></textarea>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="fixed-table-toolbar">
                    <div class="bs-bars">
                        <div id="toolbar">
                            <button id="submit_btn" class="btn-lg btn-danger">查询</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
    <div class="fixed-table-toolbar">
        <div class="bs-bars">
            <div id="toolbar">
                <button id="refresh_btn" class="btn-danger btn-lg">刷新</button>
                <button id="reset_btn" class="btn-success btn-lg">重置</button>

                <script type="text/javascript">
                    document.getElementById("refresh_btn").onclick = function () {
                        location.href = "/index.php";
                    };

                    document.getElementById("reset_btn").onclick = function () {
                        location.href = "/index.php?action=reset";
                    };
                </script>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <h2><?php echo '按照域名排序，总共查询了' . strval($total_all) . '个域名' ?></h2>
    <div class="row">

        <?php if ($total_by_domain <= 3) { ?>
            <div class="col-lg-4">
                <?php print_result_array_by_domain($outputs_by_domain); ?>
            </div>
        <?php } else { ?>
            <div class="col-lg-4">
                <?php print_result_array_by_domain(array_slice($outputs_by_domain, $offset_1, $group_len_1)); ?>
            </div>
            <div class="col-lg-4">
                <?php print_result_array_by_domain(array_slice($outputs_by_domain, $offset_2, $group_len_2)); ?>
            </div>
            <div class="col-lg-4">
                <?php print_result_array_by_domain(array_slice($outputs_by_domain, $offset_3, $group_len_3)); ?>
            </div>
        <?php } ?>
    </div>
    <br>
    <h2><?php echo '按照IP地址排序，总共查询了' . strval($total_all) . '个域名' ?></h2>
    <div class="row">
        <div class="col-lg-4">
            <?php print_result_array($outputs_by_ip['known'], '已知IP的域名列表', 'results'); ?>
        </div>

        <div class="col-lg-4">
            <?php print_result_array($outputs_by_ip['unknown'], '未知IP的域名列表', 'unknown_results'); ?>
        </div>
        <div class="col-lg-4">
            <?php print_result_array($outputs_by_ip['failed'], '解析错误的域名列表', 'failed_results'); ?>
        </div>
    </div>

    <hr class="featurette-divider">

    <footer>
        <p class="pull-right"><a href="#">返回顶部</a></p>
        <p>&copy; 2016 Fifi Lyu</p>
    </footer>
</div>
</body>
</html>