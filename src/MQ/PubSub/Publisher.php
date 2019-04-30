<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MQ\PubSub;
use MQ\Configuration\EndpointConfig;
/**
 * Description of Publisher
 *
 * @author javier
 */
class Publisher extends \MQ\ClientEndpoint {
    public function __construct($connectionString,$exchangeName='amq.topic') {
        $baseJsonConfig = <<<EOT
        {
            "Exchange":{ "Name":"{$exchangeName}","Type":"topic"},
            "Queues":[]
        }
EOT;
        $config=EndpointConfig::create($baseJsonConfig);
        parent::__construct($connectionString, $config);
    }
    
    public function publish($body,$topic='#',array $headers=array()) {
        $this->send($body, $topic, $headers);
    }
}
