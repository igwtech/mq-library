<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace MQ\Task;
use PhpAmqpLib\Message\AMQPMessage;
use \MQ\Configuration\EndpointConfig;
/**
 * Description of JobClient
 * Many JobWorkers connect to the same Queue that is declared as a direct binding of an Exchange
 * The MQ server load balances the Workers
 * Results of the Work are persisted using and OOB channel (Database, Files,API, etc)
 * You should override the AsyncResult class to keep track of the status of your Tasks
 * @author javier
 */
abstract class JobClient extends \MQ\ClientEndpoint {
    protected $methodQueueMap;
    
    public function __construct($connectionString,$exchangeName) {
        $baseJsonConfig = <<<EOT
        {
            "Exchange":{ "Name":"{$exchangeName}","Type":"direct"},
            "Queues":[]
        }
EOT;
        $config=EndpointConfig::create($baseJsonConfig);
        parent::__construct($connectionString, $config);
    }
    
    /**
     * @return AsyncResult New AsyncResult Object
     */
    abstract protected function createAsyncResult();
    
    /**
     * @param AsyncResult $res Object to be freed
     */
    abstract protected function freeAsyncResult(AsyncResult $res);
    
    
    public function beginTask($task_name,array $arguments,$routing_queue_name=null,$properties=array('delivery_mode' => 2)) {
        if($routing_queue_name === null) {
            $routing_queue_name = $task_name;
        }
        $task = call_user_func_array(array($this,'createAsyncResult'),$arguments);
        $this->send($this->serializer->serialize(array('result'=>$task)),$routing_queue_name,array('method'=>$task_name),$properties);
        return $task;
    }
    
    public function checkTask(AsyncResult $result) {
        if(!$result->isCompleted() ) {
            sleep(0.1);
        }
        return $result;
    }
    
    public function endTask(AsyncResult $result,$waitTime=0) {
        // TODO: Include Timeout for RPC call
        $startTime = microtime(true);
        $elapsed=0;
        while(!$result->isCompleted() && ($elapsed <= $waitTime)) {
            usleep(($elapsed)*1000); 
            $elapsed=microtime(true)-$startTime;
        }
        $this->freeAsyncResult($result);
        return $result;
        
    }
}
