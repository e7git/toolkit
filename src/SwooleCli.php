<?php

namespace Sayhey\Toolkit;

/**
 * 仅在cli模式下可用的Swoole相关小工具
 * @multiProcess 多进程协程处理
 * 
 */
class SwooleCli
{

    /**
     * 检查运行条件是否满足
     * @return bool
     */
    public static function check(): bool
    {
        return ('cli' === php_sapi_name() && extension_loaded('swoole'));
    }

    /**
     * 多进程协程处理
     * @param callable $action 业务回调，回调函数返回类型必须为string
     * @param array $params 业务参数
     * @param int $workerNum 最大子进程数，1-100之间
     * @param int $coroutineNum 每个子进程最大协程数
     */
    public static function multiProcess(callable $action, array $params, int $workerNum = 10, int $coroutineNum = 100)
    {
        $begin = microtime(true);
        swoole_set_process_name('master:toolkit-swoolecli');

        $workerParams = array_chunk($params, ceil(count($params) / min(100, max(1, intval($workerNum)))));
        $workerNumReal = count($workerParams);
        $workerExitFlag = '__w0RkEr_Ex1T_f1Ag__';

        // 创建子进程
        foreach ($workerParams as $workerParam) {
            $coroutineParams = array_chunk($workerParam, ceil(count($workerParam) / max(1, intval($coroutineNum))));
            $process = (new \Swoole\Process(function() use ($coroutineParams, $action, $workerExitFlag) {
                        swoole_set_process_name('worker:toolkit-swoolecli');

                        // 创建协程
                        foreach ($coroutineParams as $coroutineParam) {
                            go(function () use($coroutineParam, $action) {
                                foreach ($coroutineParam as $param) {
                                    echo call_user_func($action, $param), PHP_EOL;
                                }
                            });
                        }

                        echo $workerExitFlag;
                    }, true, 1, true));
            if (!$process->start()) {
                echo 'fork worker fail: errmsg=', swoole_strerror(swoole_errno()), PHP_EOL;
                continue;
            }

            // 进程通信
            \Swoole\Event::add($process->pipe, function() use($process, $begin, $workerExitFlag, &$workerNumReal) {
                $data = $process->read();
                if ($workerExitFlag === substr($data, -strlen($workerExitFlag))) {
                    echo explode($workerExitFlag, $data)[0];
                    \Swoole\Event::del($process->pipe);
                    if (--$workerNumReal <= 0) {
                        echo 'all finished, use time: ', round(microtime(true) - $begin, 5), 's', PHP_EOL;
                    }
                } else {
                    echo $data;
                }
            });
        }
    }

}
