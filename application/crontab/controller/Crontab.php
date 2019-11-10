<?php
// +----------------------------------------------------------------------
// | Cron控制器
// +----------------------------------------------------------------------
// | Since: 2014-09-15 16:02
// +----------------------------------------------------------------------
// | Author: shioujun <shioujun@gmail.com>
// +----------------------------------------------------------------------
namespace app\crontab\controller;

/**
 * Crontab计划任务
 */
class Crontab
{

    /**
     * [task 执行任务]
     *
     * @return [type] [description]
     */
    public function task()
    {
        $rs = config('crontab.TASKS');
        echo "start at " . date("H:i:s", time()) . "\n";
        // 获取当前服务器IP
        $curr_ip = $this->getServerIp();
        foreach ($rs as $key => $value) {
            $time = time();
            $expr = trim($value['cron_expr']);
            $ips = trim($value['allow_ip']);
            $ip_flag = false;
            $id = $value['id'];
            
            if ($ips === "*" || $ips === "") {
                $ip_flag = true;
            } else {
                $ips = explode(',', $ips);
                if (in_array($curr_ip, $ips)) {
                    $ip_flag = true;
                }
            }
            
            if ($ip_flag && $this->is_time_cron($time, $expr)) {
                $cmd = $value['command'];
                $php_cli_path = config('crontab.PHP_CLI_PATH');
                
                $is_force = $value['is_force'] == 1 ? true : false;
                $type = $value['type'];
                
                // PHP CLI模式
                if ($type == 1) {
                    // 组装命令行字符串
                    $cmd = config('crontab.PHP_CLI_PATH') . ' ' . CURR_RUN_FILENAME . ' ' . $cmd;
                    //echo $cmd."\n";
                    $this->process_execute($cmd, $id, true, $is_force);
                }                 // 其他外部命令模式
                else if ($type == 2) {
                    $this->process_execute($cmd, $id, true, $is_force);
                }                 // 内置函数模式
                else if ($type == 0) {
                    $cmd = "action('" . $cmd . "');";
                    $this->process_execute($cmd . ';', $id, false, $is_force);
                }
            }
        }
        echo "end at " . date("H:i:s", time()) . "\n\n";
    }

    /**
     * [is_time_cron 验证表达式$cron是否要在$time时间运行，正确返回true，否则返回false]
     *
     * @param [type] $time
     *            [时间戳]
     * @param [type] $cron
     *            [crontab表达式]
     * @return boolean [description]
     */
    function is_time_cron($time, $cron)
    {
        $cron_parts = explode(' ', $cron);
        if (count($cron_parts) != 5) {
            return false;
        }

        list ($min, $hour, $day, $mon, $week) = explode(' ', $cron);

        $to_check = array(
            'min' => 'i',
            'hour' => 'G',
            'day' => 'j',
            'mon' => 'n',
            'week' => 'w'
        );

        $ranges = array(
            'min' => '0-59',
            'hour' => '0-23',
            'day' => '1-31',
            'mon' => '1-12',
            'week' => '0-6'
        );

        foreach ($to_check as $part => $c) {
            $val = $$part;
            $values = array();

            /*
             * For patters like 0-23/2
             */
            if (strpos($val, '/') !== false) {
                // Get the range and step
                list ($range, $steps) = explode('/', $val);

                // Now get the start and stop
                if ($range == '*') {
                    $range = $ranges[$part];
                }
                list ($start, $stop) = explode('-', $range);

                for ($i = $start; $i <= $stop; $i = $i + $steps) {
                    $values[] = $i;
                }
            }         /*
           * For patters like : 2 2,5,8 2-23
           */
            else {
                $k = explode(',', $val);

                foreach ($k as $v) {
                    if (strpos($v, '-') !== false) {
                        list ($start, $stop) = explode('-', $v);

                        for ($i = $start; $i <= $stop; $i ++) {
                            $values[] = $i;
                        }
                    } else {
                        $values[] = $v;
                    }
                }
            }

            if (! in_array(date($c, $time), $values) and (strval($val) != '*')) {
                return false;
            }
        }

        return true;
    }

    /**
     * [process_execute 多进程运行命令]
     *
     * @param [string] $input
     *            [要执行的脚步]
     * @param [int] $id
     *            [计划任务ID]
     * @param [boolean] $is_extern_command
     *            [是否外部命令]
     * @param [boolean] $is_force
     *            [上次任务未结束是否强制执行]
     * @return [type] [description]
     */
    function process_execute($input, $id, $is_extern_command = false, $is_force = false)
    {
        try {
            $log_dir = config('crontab.CRONTAB_LOG_DIR');
            if (! is_dir($log_dir)) {
                mkdir($log_dir);
            }

            $error_file = $log_dir . "error.log";
            $output_file = $log_dir . "run.log";

            // 创建子进程
            $pid = pcntl_fork();
            // 子进程
            if ($pid == 0) {
                // 每条任务生成一个唯一文件，用于记录运行时的PID
                $pid_file = $log_dir . $id . ".pid";

                if (file_exists($pid_file)) {
                    $pid = intval(file_get_contents($pid_file), 10);
                    if ($pid) {
                        // 判断当前任务是否在运行
                        $is_run = $this->isRunning($pid);

                        // 非强制执行，等待
                        if ($is_run && ! $is_force) {
                            $count = 0;
                            while ($is_run) {
                                $count ++;
                                // 重试30次后，上次的进程仍然没有结束，报错
                                if ($count > 30) {
                                    throw new \Exception("task #" . $id . " have retry 30 time , but last process still is running");
                                }
                                sleep(1);
                                $is_run = $this->isRunning($pid);
                            }
                        } else {
                            // echo "force run \n\n";
                            // exec('kill -9 '.$pid ." > /dev/null 2>&1");
                        }
                    }
                }
                echo "start taks #" . $id . " at " . date("H:i:s", time()) . "\n";
                // 外部命令，不会阻塞，建议使用
                if ($is_extern_command) {
                    exec(sprintf("%s >> %s 2>&1 & echo $! > %s", $input, $output_file, $pid_file));
                }             // 内置函数，会阻塞，建议少用
                else {
                    //echo '----------';
                    eval($input);
                }
                exit();
            } else if ($pid == - 1) {
                throw new \Exception("Couldn't create child process");
            } else {
                $pid = pcntl_wait($status, WUNTRACED); // 取得子进程结束状态
                if (pcntl_wifexited($status)) {
                    echo "end task #" . $id . ' at ' . date("H:i:s", time()) . "\n";
                }
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $code = $e->getCode();
            file_put_contents($error_file, date("Y-m-d H:i:s") . "\t" . $msg . "\n", FILE_APPEND);
        }
    }

    /**
     * [isRunning 判断指定pid的进程是否在运行]
     *
     * @param [type] $pid
     *            [description]
     * @return boolean [description]
     */
    function isRunning($pid)
    {
        try {
            $result = shell_exec(sprintf("ps %d", $pid));

            if (count(preg_split("/\n/", $result)) > 2) {
                return true;
            }
        } catch (\Exception $e) {
        }
        return false;
    }

    function newChild($func_name)
    {
        $args = func_get_args();
        unset($args[0]);
        $pid = pcntl_fork();

        // 子进程
        if ($pid == 0) {
            $pid = posix_getpid();
            function_exists($func_name) and exit(call_user_func_array($func_name, $args)) or exit(- 1);
        } else if ($pid == - 1) {
            echo "Couldn't create child process";
        } else {
            // $pid = pcntl_wait($status, WUNTRACED); //取得子进程结束状态
            // if (pcntl_wifexited($status)) {
            // }
        }
    }

    function asynCallback($func, $args = '')
    {
        $base = event_base_new();
        $event = event_new();

        event_set($event, 0, EV_READ | EV_WRITE, $func);
        event_base_set($event, $base);
        event_add($event);

        event_base_loop($base);
    }

    /*function RR($fd)
    {
        global $code;
        R($code);
    }*/

    /**
     * [getServerIp CLI下获取当前服务器IP]
     *
     * @return [type] [description]
     */
    function getServerIp()
    {
        $ss = exec('/sbin/ifconfig eth0 | sed -n \'s/^ *.*addr:\\([0-9.]\\{7,\\}\\) .*$/\\1/p\'', $arr);
        $ret = $arr[0];
        return $ret;
    }


}