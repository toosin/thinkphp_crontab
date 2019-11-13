ThinkPHP5.1内实现类似Laravel内的crontab定时任务

一、说明

1，项目实现类似Laravel内的Crontab模块，在TP5.1中实现定时任务（分钟级）。

2，项目核心代码在application下的crontab模块，入口为crontab目录下的crontab.php,须搭配crontab配置文件。

3，项目须调用外部命令（shell 命令），暂只支持Linux，确保php.ini中没有禁用exec()方法。

二、使用方法

1，将crontab目录拷贝至你的项目application目录下当作单独模块使用，同时于config目录下新建crontab.php配置文件，配置内容及说明见示例。

2，定时任务写在contab目录下的controller目录下，配置文件中的command项，配置为对应的：crontab(模块名)/controller名/方法名（任务）

3，配置Linux定时任务：以Cli模式执行crontab模块下的Crontab控制器中的task方法。(task方法负责调配配置文件中你配置的任务，所以Linux的crontab中只需配置本任务)
配置例如：* * * * * /usr/local/bin/php /home/www/thinkphp_crontab/crontab/crontab.php crontab/Crontab/task >/dev/null 2>&1
