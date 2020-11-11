<?php

// 是否终端执行
if ('cli' !== php_sapi_name()) {
    echo 'only run on cli mode', PHP_EOL;
    exit;
}

// 自动加载
if (is_file(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
} else {

    function autoload($class)
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        require_once str_replace('Sayhey/Toolkit', 'src', $file);
    }

    spl_autoload_register('autoload');
}

/*
 * 多进程协程处理演示
 * 请求百度首页100次
 * 调整multiProcess的进程和协程数，对比运行速度
 */
$urlParams = [];
for ($i = 0; $i < 100; $i++) {
    $urlParams[] = ['index' => $i, 'url' => 'https://www.baidu.com/'];
}
Sayhey\Toolkit\SwooleCli::multiProcess(function($params) {
    file_get_contents($params['url']);
    return '[OK]' . $params['index'];
}, $urlParams, 10, 10);
