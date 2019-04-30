<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MQ\PubSub;

/**
 * Description of Subscriber
 *
 * @author javier
 */
class Subscriber extends \MQ\ServiceEndpoint {
    public function __construct($connectionString,$exchangeName='amq.topic' ) {
        $baseJsonConfig = <<<EOT
        {
            "Exchange":{ "Name":"{$exchangeName}","Type":"topic"},
            "Queues":[]
        }
EOT;
        $config=  \MQ\Configuration\EndpointConfig::create($baseJsonConfig);
        parent::__construct($connectionString, $config);
        $this->channel->basic_qos(null,10, null); // Enable fair Queueing between workers
    }
    
    public function subscribe(\MQ\Delegate $callback,$topic='#'){
        $tmpQueue = new \MQ\Configuration\QueueConfig();
        $tmpQueue->Routing=$topic;
        $this->setupQueue($tmpQueue);
        $this->setupBind($tmpQueue, $this->config->Exchange);
        $this->channel->basic_consume($tmpQueue->Name, '', false, false, false, false, array($this,'onMessage'));
        
        $this->observers->attach($callback);
        
    }
    public function unsubscribe(\MQ\Delegate $callback) {
        $this->observers->detach($callback);
    }
}
