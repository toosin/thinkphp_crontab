<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
// 必须在CLI模式运行
if (substr(php_sapi_name(), 0, 3) !== 'cli') {
    die("This Programe can only be run in CLI mode\n");
}
// 入口文件名
define('CURR_RUN_FILENAME', __FILE__);
// 定义应用目录
define('APP_PATH', __DIR__ . '/../../application/');
// 加载框架引导文件
require __DIR__ . '/../../thinkphp/start.php';
