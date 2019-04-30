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

//require __DIR__ . '/../mq-library.phar';
require __DIR__.'/../vendor/autoload.php';

/**
 * Sample Config
 */
define('MQ_SERVER', 'amqp://guest:guest@192.168.99.100/');
define('FIB_EVENT_NAME','fibonacchi' );

class FibEventDispatcher extends MQ\Event\EventDispatcher {
    public function __construct($connectionString) {
        parent::__construct($connectionString,FIB_EVENT_NAME );
        $this->attach(array($this,'onEvent'));
    }
    
    public function onEvent($data,array $headers){
        $event = $this->serializer->unserialize($data);
        if(false === ($event && is_object($event) && is_subclass_of($event, 'MQ\Event\IEvent'))) {
            throw new ErrorException('Invalid Event Data for: '.  var_export($event,true));
        }
        echo "RESULT IS: ".$this->fib($event->getValue()) ."\n";
        return true;
    }
    
    protected function fib($n) {
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
class FibEventSender extends MQ\Event\EventSender {
    
}

try {
    if (isset($argv[1]) && $argv[1] == 'server') {
        $server = new FibEventDispatcher(MQ_SERVER);
        $server->start();
    } else {
        echo "Creating Client\n";
        $client = new FibEventSender(MQ_SERVER);
        
        while(true) {
            srand(time());
            $num = ceil(rand(10,20));
            echo "CLIENT:Calling fib($num)\n";
        
            $task = $client->fireEvent(new \MQ\Event\ValueEvent($num,FIB_EVENT_NAME));
            var_dump(serialize(new \MQ\Event\ValueEvent($num,FIB_EVENT_NAME)));
            echo "Event notification sent\n";
            echo "Now client is doing something else that takes time (sleep 10secs)\n";
            sleep(10);
        }
        
    }
} catch (Exception $e) {
    echo $e->getMessage()."\n";
}



