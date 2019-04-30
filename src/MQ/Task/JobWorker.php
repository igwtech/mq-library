<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace MQ\Task;
use PhpAmqpLib\Message\AMQPMessage;
/**
 * Description of JobServer
 * Many JobWorkers connect to the same Queue that is declared as a direct binding of an Exchange
 * The MQ server load balances the Workers
 * Results of the Work are persisted using and OOB channel (Database, Files,API, etc)
 * You should override the AsyncResult class to keep track of the status of your Tasks
 * @author javier
 */
abstract class JobWorker extends \MQ\ServiceEndpoint {

    public function __construct($connectionString,$exchangeName='amq.direct',$queueName=null ) {
        if($queueName === null ){
            $queueName=camel2dashed(get_class($this));
        }
    $baseJsonConfig = <<<EOT
        {
            "Exchange":{ "Name":"{$exchangeName}","Type":"direct"},
            "Queues":[
                { "Name":"{$queueName}","Routing":null,"Dead":"{$queueName}-dead" }
            ]
        }
EOT;
        $config=  \MQ\Configuration\EndpointConfig::create($baseJsonConfig);
        parent::__construct($connectionString, $config);
        $this->channel->basic_qos(null,1, null); // Enable fair Queueing between workers
    }
    
    public function onMessage(AMQPMessage $item) {
        error_log( "Processing Queue Item {$item->get('consumer_tag')}-{$item->get('delivery_tag')} Count: {$this->counter}\n");
        $data = $this->serializer->unserialize($item->body);
        $headers = $item->get('application_headers')->getNativeData();
        try {
            $method_name=(isset($headers['method']))?$headers['method']:'call';
            $result = (isset($data['result']))?$data['result']:false;
            if($result && is_object($result)) {
                $this->channel->basic_ack($item->get('delivery_tag'));
            }else{
                $this->channel->basic_nack($item->get('delivery_tag'));
                return;
            }
            error_log('Invoking method:'.$method_name);
            if(method_exists($this, $method_name)) {
                $resp=call_user_func_array(array($this,$method_name), array($result));
            }  else {
                $resp = new \BadMethodCallException('Invalid method called '.$method_name);
            }
            error_log('Completed:'.$method_name);
            return $this->serializer->serialize($result);
        } catch (Exception $ex) {
            error_log($ex->getMessage());
        }
        
        $this->counter++;
    }
}
