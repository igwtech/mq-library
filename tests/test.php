<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('UTC');
/** Tracing * */
//register_tick_function(function() {
//   $stack=debug_backtrace();
//   echo "TRACE:{$stack[0]['file']}:{$stack[0]['line']}\n";
//});
//declare(ticks=1);

/**
 * Sample Config
 */
define('MQ_SERVER', 'amqp://guest:guest@192.168.99.100/');
ob_start();
require __DIR__ . '/../mq-library.phar';
ob_end_clean();






try {
    echo "START\n";
    $delegate = new MQ\Delegate(function(){
        throw new Exception("TEST EXCEPTION");
    });
    $delegate('123');
    echo "END\n";
} catch (Exception $e) {
    echo $e->getMessage();
    echo " ERROR\n";
}
