<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace MQ\Configuration;
/**
 * Description of Configuration
 *
 * @author javier
 */
class EndpointConfig {
    /**
     *
     * @var ExchangeConfig 
     */
    public $Exchange;
    /**
     *
     * @var QueuesCollection 
     */
    public $Queues;
    
    /**
     * 
     */
    public function __construct() {
        $this->Queues=null;
    }
    
    /**
     * 
     * @return array
     */
    public function jsonSerialize() {
        return array('Exchange'=>$this->Exchange,'Queues'=>$this->Queues);
    }
    
    /**
     * 
     * @param string $json
     * @return \MQ\Configuration\EndpointConfig
     * @throws \ErrorException
     */
    public static function create($json) {
        $stdclass=  json_decode($json);
        if(null == $stdclass) {
            throw new \ErrorException(json_last_error_msg());
        }
        $self=new EndpointConfig();
        $self->Exchange=new ExchangeConfig();
        foreach($stdclass->Exchange as $k=>$v) {
            $self->Exchange->$k=$v;
        }
        
        foreach($stdclass->Queues as $queue) {
            if(!isset($self->Queues)) {
                $self->Queues = new QueuesCollection();
            }
            $objQueue=new QueueConfig();
            foreach($queue as $k=>$v) {
                $objQueue->$k=$v;
            }
            $self->Queues->attach($objQueue);
        }
        return $self;
    }

    
}
