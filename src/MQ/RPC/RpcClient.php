<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace MQ\RPC;
use PhpAmqpLib\Message\AMQPMessage;
use MQ\Configuration\EndpointConfig;
/**
 * Description of RpcClient
 *
 * @author javier
 */
abstract class RpcClient extends \MQ\ClientEndpoint{
    private $callback_queue;
    private $response;
    private $corr_id;

    protected $methodQueueMap;
    
    public function __construct($connectionString,$apiInterface,$exchangeName='amq.direct') {
    $baseJsonConfig = <<<EOT
        {
            "Exchange":{ "Name":"{$exchangeName}","Type":"direct"},
            "Queues":[]
        }
EOT;
        $config=EndpointConfig::create($baseJsonConfig);
        $this->methodQueueMap=\MQ\RPC\RpcServer::getAPI($apiInterface);
        parent::__construct($connectionString, $config);
        list($this->callback_queue, ,) = $this->channel->queue_declare(
            "", false, false, true, false);
        $this->channel->basic_consume(
            $this->callback_queue, '', false, false, false, false,
            array($this, 'on_response'));
        
    }
    
    public function __call($method_name, $arguments) {
        error_log($method_name . "(...) called.");
        if($method_name==='on_response') {
            if($arguments[0]->get('correlation_id') == $this->corr_id) {
                $this->response = $arguments[0]->body;
            }
            return;
        }
        if(!isset($this->methodQueueMap[$method_name])) {
            throw new \UnexpectedValueException("Undefined method");
        }
        $this->response = null;
        $this->corr_id = uniqid();
        $this->send($this->serializer->serialize($arguments),$this->methodQueueMap[$method_name],array('method'=>$method_name),array('correlation_id' => $this->corr_id,
                  'reply_to' => $this->callback_queue));
        
        // TODO: Include Timeout for RPC call
        while(!$this->response) {
            $this->channel->wait();
        }
        
        $returned=$this->serializer->unserialize($this->response);
        if($returned instanceof \Exception) {
            throw $returned;
        }
        return $returned;
    }

}
