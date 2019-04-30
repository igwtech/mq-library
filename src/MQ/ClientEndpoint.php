<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MQ;
use PhpAmqpLib\Message\AMQPMessage;
/**
 * Description of ClientEndpoint
 *
 * @author javier
 */
class ClientEndpoint extends Endpoint {
    /**
     * 
     * @param string|byte $data
     * @param string $routing_key
     * @param array $headers
     * @param array $properties
     */
    protected function send($data,$routing_key='',array $headers=null,$properties=array('delivery_mode' => 2)) {
        $mq_headers=array();
        if(!empty($headers)) {
            foreach($headers as $k=>$v) {
                    $mq_headers[$k]=array('S',$v);
            }
        }
        $mq_msg = new AMQPMessage($data,$properties);


        $mq_msg->set('application_headers',  $mq_headers);
        $this->channel->basic_publish( $mq_msg, $this->config->Exchange->Name, $routing_key);
        error_log(" [x] Sent ".$routing_key.':'.strlen($data)." bytes");
    }
}
