<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace MQ\Event;
use PhpAmqpLib\Message\AMQPMessage;
/**
 * Description of EventListener
 *
 * @author javier
 */
class EventDispatcher extends \MQ\ServiceEndpoint{
    /**
     * Creates an Event Dispatcher for the event queue. 
     * @param string $connectionString URI like connection string
     * @param string $eventType Event name used to post notifications
     * @param string $queueName Optional Queue name used to store the notifications. This should be used when multiple consumers are needed on the same queue.
     * @param string $exchangeName Optional Exchange name to be used to post notifications
     * @throws \ErrorException Error during initialization
     */
    public function __construct($connectionString,$eventType,$queueName=null,$exchangeName='amq.topic' ) {
        if(empty($eventType) ){
            throw new \ErrorException('Undefined Event Type');
        }
    $baseJsonConfig = <<<EOT
        {
            "Exchange":{ "Name":"{$exchangeName}","Type":"topic"},
            "Queues":[
                { "Name":"{$queueName}","Routing":"{$eventType}","Dead":"{$queueName}-dead" }
            ]
        }
EOT;
        $config=  \MQ\Configuration\EndpointConfig::create($baseJsonConfig);
        parent::__construct($connectionString, $config);
        $this->channel->basic_qos(null,10, null); // Enable fair Queueing between workers
    }
}
