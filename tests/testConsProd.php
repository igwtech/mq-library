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
//    PRODUCER=>array(METHOD=>'',PARAMS=>''),
//    CONSUMER=>array(CALLBACK=>''),

$MQ_CONFIG = <<<EOT
{
    "Exchange":{ "Name":"amq.direct","Type":"direct"},
    "Queues":[
        { "Name":"rpc.fibonacchi","Routing":null,"Dead":"validate-dead" }
    ]
}
EOT;
require __DIR__ . '/../mq-library.phar';

class FibonachiRcpServer extends MQ\RPC\RpcServer {

    public function __construct($connectionString, $jsonConfig) {
        parent::__construct($connectionString, MQ\Configuration\EndpointConfig::create($jsonConfig));
    }

    public function fib($n) {
        echo "SERVER: Doing Fib($n)\n";
        if ($n == 0) {
            return 0;
        }
        if ($n == 1) {
            return 1;
        }
        return $this->fib($n - 1) + $this->fib($n - 2);
    }

}

class FibonachiRpcClient extends MQ\RPC\RpcClient {

    public function __construct($connectionString, $jsonConfig) {
        parent::__construct($connectionString, MQ\Configuration\EndpointConfig::create($jsonConfig));
        $this->methodQueueMap['fib'] = 'rpc.fibonacchi';
    }

}






try {
    if ($argv[1] == 'server') {
        $server = new FibonachiRcpServer(MQ_SERVER, $MQ_CONFIG);
        $server->start();
    } else {

        $client = new FibonachiRpcClient(MQ_SERVER, $MQ_CONFIG);
        while (true) {
            $num = ceil(rand(1,10));
            echo "CLIENT:Calling fib($num)\n";

            $r = $client->fib($num);
            echo "CLIENT:Returned $r\n";
        }
    }
} catch (Exception $e) {
    error_log($e->getMessage());
}
