<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace MQ\Event;
use MQ\Configuration\EndpointConfig;
/**
 * Description of EventSender
 *
 * @author javier
 */
class EventSender extends \MQ\ClientEndpoint {
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
    
    public function fireEvent(IEvent $event) {
        $this->send($this->serializer->serialize($event),$event->getEventType());
    }
}
