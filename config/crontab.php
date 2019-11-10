<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'TASKS'=>[
        [
            'id'=>1,        //任务唯一标识，不可重复
            'title'=>'task_1',
            'cron_expr'=>'*/5 * * * *', // 类似Linux的crontab计时写法
            'command'=>'crontab/index/hello', // 要执行的脚本路由：模块名/控制器/方法
            'allow_ip'=>'', //允许运行的IP，多个用英文逗号分隔
            'is_force'=>false, //上次任务没执行完是否强制执行，默认否
            'type'=>1,  //命令模式，0执行内置函数，1调用PHP CLI脚步命令,2调用外部其他脚步命令
            'status'=>1,//任务开关
        ],
        [
            'id'=>2,
            'title'=>'task_2',
            'cron_expr'=>'* * * * *',
            'command'=>'crontab/index/task2',
            'allow_ip'=>'',
            'is_force'=>false,
            'type'=>1,
            'status'=>1,//任务开关
        ]
    ],
    // PHP命令行程序路径
    'PHP_CLI_PATH' => '/usr/local/php/bin/php',
    // 日志记录文件夹
    'CRONTAB_LOG_DIR' => '/tmp/php_task_logs/',
];
