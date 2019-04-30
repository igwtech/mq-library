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

//require __DIR__ . '/../mq-library.phar';
require __DIR__.'/../vendor/autoload.php';


try {
    if (isset($argv[1]) && $argv[1] == 'server') {
        $server = new \MQ\PubSub\Subscriber(MQ_SERVER);
        $server->subscribe(new MQ\Delegate(function($params) {
            echo "Topic an APPLE has arrived\n";
        }),"red.fruits.apple");
        $server->subscribe(new MQ\Delegate(function($params) {
            echo "Topic Something YELLOW has arrived\n";
        }),"yellow.*");
        $server->subscribe(new MQ\Delegate(function($params) {
            echo "Topic an ORANGE County has arrived\n";
        }),"*.city.orange");
        $server->start();
    } else {
        $colors=array('red'=>1,'blue'=>2,'yellow'=>3);
        $class=array('city'=>1,'fruits'=>2);
        $item=array('apple'=>1,'orange'=>3,'pearis'=>3);
        $client = new \MQ\PubSub\Publisher(MQ_SERVER);
        while (true) {
            $topic = implode('.',array(array_rand($colors),  array_rand($class),  array_rand($item)));
            echo "CLIENT:New Topic $topic\n";
            $client->publish('NEW ITEM', $topic);
            sleep(1);
        }
    }
} catch (Exception $e) {
    error_log($e->getMessage());
}
