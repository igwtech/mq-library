<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MQ\ReqResp;

/**
 * Description of Replier
 *
 * @author javier
 */
abstract class Replier extends \MQ\ServiceEndpoint {
    public function __construct($connectionString,$queueName, $exchangeName='amq.direct') {
    $baseJsonConfig = <<<EOT
        {
            "Exchange":{ "Name":"{$exchangeName}","Type":"direct"},
            "Queues":[
            { "Name":"{$queueName}","Routing":null,"Dead":"{$queueName}-dead" }
                ]
        }
EOT;
        $config=  EndpointConfig::create($baseJsonConfig);
        parent::__construct($connectionString, $config);
        
    }
    
    
    abstract public function onRequest($body,$headers);
    
    public function onMessage(AMQPMessage $item) {
        error_log( "Processing Queue Item {$item->get('consumer_tag')}-{$item->get('delivery_tag')} Count: {$this->counter}\n");
        $data = $item->body;
        $headers = $item->get('application_headers')->getNativeData();
        try {
            $resp = $this->onRequest($data, $headers);
            $msg = new AMQPMessage($this->serializer->serialize($resp),array('correlation_id' => $item->get('correlation_id')));
            $item->delivery_info['channel']->basic_publish($msg, '', $item->get('reply_to'));
            $this->channel->basic_ack($item->get('delivery_tag'));
        } catch (Exception $ex) {
            error_log($ex->getMessage());
            $this->channel->basic_nack($item->get('delivery_tag'));
        }
        $this->counter++;
    }
}
