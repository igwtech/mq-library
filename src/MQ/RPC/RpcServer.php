<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MQ\RPC;
use PhpAmqpLib\Message\AMQPMessage;
use \MQ\Configuration\EndpointConfig;
/**
 * Description of RpcServer
 *
 * @author javier
 */
abstract class RpcServer extends \MQ\ServiceEndpoint {
    private $queue_template;
    public function __construct($connectionString, $exchangeName='amq.direct',$queue_name_template='rpc.{class}.{method}') {
    $baseJsonConfig = <<<EOT
        {
            "Exchange":{ "Name":"{$exchangeName}","Type":"direct"},
            "Queues":[]
        }
EOT;
        $this->queue_template=$queue_name_template;
        $api= self::getAPI($this);
        $config=  EndpointConfig::create($baseJsonConfig);
        $config->Queues= new \MQ\Configuration\QueuesCollection();
        foreach($api as $method=>$queueName) {
            echo "Setting up $method\n";
            $queue =new \MQ\Configuration\QueueConfig();
            $queue->Name=$queueName;
            $queue->Routing=$method;
            $queue->Dead=$queue->Name."-dead";
            $config->Queues->attach($queue);
        }
        parent::__construct($connectionString, $config);
        
    }
    public static function getAPI($class) {
        $out=array();
        $methods= self::get_this_class_methods($class);
        foreach($methods as $method) {
            $queueName= str_replace('{class}', get_class($class), $this->queue_template);
            $queueName= str_replace('{method}', $method, $queueName );
            $out[$method]=$queueName;
        }
        return $out;
    }
    private static function get_this_class_methods($class){
        $array1 = get_class_methods($class);
        if($parent_class = get_parent_class($class)){
            $array2 = get_class_methods($parent_class);
            $array3 = array_diff($array1, $array2);
        }else{
            $array3 = $array1;
        }
        return($array3);
    }
    public function onMessage(AMQPMessage $item) {
        error_log( "Processing Queue Item {$item->get('consumer_tag')}-{$item->get('delivery_tag')} Count: {$this->counter}\n");
        $data = $item->body;
        $headers = $item->get('application_headers')->getNativeData();
        try {
            $method_name=(isset($headers['method']))?$headers['method']:'call';
            if(method_exists($this, $method_name)) {
                $resp=call_user_func_array(array($this,$method_name),  $this->serializer->unserialize($data));
            }  else {
                $resp = new \BadMethodCallException('Invalid method called '.$method_name);
            }
        } catch (Exception $ex) {
            error_log($ex->getMessage());
            $resp=$ex;
        }
        $msg = new AMQPMessage($this->serializer->serialize($resp),array('correlation_id' => $item->get('correlation_id')));
        $item->delivery_info['channel']->basic_publish($msg, '', $item->get('reply_to'));
        $this->channel->basic_ack($item->get('delivery_tag'));
        $this->counter++;
    }
}
