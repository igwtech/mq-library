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
use MQ\Configuration\EndpointConfig;
use MQ\ClientEndpoint;
use MQ\ServiceEndpoint;

/**
 * Sample Config
 */
define('MQ_SERVER', 'amqp://guest:guest@192.168.99.100/');
define('FIB_EXCHANGE_NAME','tasks.process.fibonacchi' );

/**
 * This class represents the Worker.
 * Receives an AsyncResult Object
 */
class FibonachiWorker extends MQ\Task\JobWorker {

    public function __construct($connectionString) {
        parent::__construct($connectionString,FIB_EXCHANGE_NAME );
    }

    public function FibonachiWorker(FibTask $task) {
        $n = $task->getNumber();
        echo "SERVER: Doing TaskId: {$task->getTaskId()} Fib($n)\n";
        $r=$this->fib($n);
        echo "SERVER: Done TaskId: {$task->getTaskId()} Fib($n)=$r\n";
        $task->setResult($r);
        return;
    }
     
    private function fib($n) {
        if ($n == 0) {
            return 0;
        }
        if ($n == 1) {
            return 1;
        }
        return $this->fib($n - 1) + $this->fib($n - 2);
    }
}

class FibonachiTaskClient extends \MQ\Task\JobClient {
    public function __construct($connectionString) {
        parent::__construct($connectionString, FIB_EXCHANGE_NAME);
    }
    protected function createAsyncResult() {
        $num = func_get_arg(0);
        var_dump($num);
        return new FibTask($num);
    }
    protected function freeAsyncResult(\MQ\Task\AsyncResult $res) {
        if(file_exists("/tmp/".$res->getTaskId().".tmp")) {
            unlink("/tmp/".$res->getTaskId().".tmp");
        }
    }
}

/**
 * This class represents the Task State
 * You need to include variables and persistence 
 * to hold the actual working data.
 */
class FibTask extends \MQ\Task\AsyncResult {
    private $result;
    private $number;

    public function __construct($number) {
        parent::__construct(uniqid());
        $this->number=$number;
        $this->result=null;
    }
    private function getFilename() {
        return "/tmp/".$this->getTaskId().".tmp";
    }
    private function writeData($data) {
        $fd=fopen($this->getFilename(),'w');
        if($fd===false) throw new ErrorException('Unable to open temp file');
        flock($fd, LOCK_EX);
        fwrite($fd,  $data);
        flock($fd,LOCK_UN);
        fclose($fd);
    }

    private function readData() {
        $fd=fopen($this->getFilename(),'r');
        if($fd===false) throw new ErrorException('Unable to open temp file');
        flock($fd, LOCK_EX);
        $data=fread($fd,1024);
        flock($fd,LOCK_UN);
        fclose($fd);
        return $data;
    }
    
    public function serialize() {
        $this->writeData(serialize(array($this->number,$this->result)));
        return $this->getTaskId();
    }

    public function unserialize($serialized) {
        $this->taskid=$serialized;
        list($this->number,$this->result)=unserialize($this->readData());
    }

    public function isCompleted() {
        echo ".";
        list($this->number,$this->result)=unserialize($this->readData());
        return ($this->result!=null);
    }

    /* Accessors to private properties, The actual Task DATA  */ 
    public function getResult() {
        return $this->result;
    }

    public function setResult($result) {
        $this->result=$result;
    }
    
    public function getNumber() {
        return $this->number;
    }
    public function setNumber($number) {
        $this->number=$number;
    }

}




try {
    if (isset($argv[1]) && $argv[1] == 'server') {
        $server = new FibonachiWorker(MQ_SERVER);
        $server->start();
    } else {
        echo "Creating Client\n";
        $client = new FibonachiTaskClient(MQ_SERVER);
        srand(time());
        $num = ceil(rand(1,20));
        echo "CLIENT:Calling fib($num)\n";
        
        $task = $client->beginTask('FibonachiWorker',array($num));
        echo "beginTask done\n";
        echo "Now client is doing something else that takes time (sleep 10secs)\n";
        $client->endTask($task,10);
        echo "Waking up\n";
        $r=$task->getResult();
        echo "CLIENT:Returned $r\n";
        
        
    }
} catch (Exception $e) {
    echo $e->getMessage()."\n";
}
