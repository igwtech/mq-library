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

require __DIR__ . '/../vendor/autoload.php';
interface FiboAPI {
    public function fib($n);
}

class FibonachiRcpServer extends MQ\RPC\RpcServer implements FiboAPI {

    public function __construct($connectionString) {
        parent::__construct($connectionString);
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

    public function __construct($connectionString) {
        parent::__construct($connectionString,'FiboAPI');
    }

}






try {
    if ($argv[1] == 'server') {
        $server = new FibonachiRcpServer(MQ_SERVER);
        $server->start();
    } else {

        $client = new FibonachiRpcClient(MQ_SERVER);
        while (true) {
            $num = ceil(rand(1,10));
            echo "CLIENT:Calling fib($num)\n";

            $r = $client->fib($num);
            echo "CLIENT:Returned $r\n";
        }
    }
} catch (Exception $e) {
    echo $e->getMessage(). "\n";
}
