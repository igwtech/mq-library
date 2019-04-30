<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MQ\ReqResp;

/**
 * Description of Requestor
 *
 * @author javier
 */
class Requestor extends \MQ\ClientEndpoint{
    private $callback_queue;
    private $corr_id;
    private $response;
    
    public function __construct($connectionString,$exchangeName='amq.direct') {
    $baseJsonConfig = <<<EOT
        {
            "Exchange":{ "Name":"{$exchangeName}","Type":"direct"},
            "Queues":[]
        }
EOT;
        $config=EndpointConfig::create($baseJsonConfig);
        parent::__construct($connectionString, $config);
        list($this->callback_queue, ,) = $this->channel->queue_declare(
            "", false, false, true, false);
        $this->channel->basic_consume(
            $this->callback_queue, '', false, false, false, false,
            array($this, 'on_response'));
        
    }
    public function request($body,array $headers=array(),$timeout=1000) {
        $this->response = null;
        $this->corr_id = uniqid();
        $this->send($this->serializer->serialize($body),'',$headers,array('correlation_id' => $this->corr_id, 'reply_to' => $this->callback_queue));
        
        // TODO: Include Timeout for call
        while(!$this->response) {
            $this->channel->wait($timeout);
        }
        
        $returned=$this->serializer->unserialize($this->response);
        if($returned instanceof \Exception) {
            throw $returned;
        }
        return $returned;
    }
    
    public function __call($method_name, $arguments) {
        error_log($method_name . "(...) called.");
        if($method_name==='on_response') {
            if($arguments[0]->get('correlation_id') == $this->corr_id) {
                $this->response = $arguments[0]->body;
            }
            return;
        }
    }
}
