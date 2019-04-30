<?php
namespace MQ\ConsProd;
/**
 * Description of Consumer
 *
 * @author javier
 */
class Consumer extends \MQ\ServiceEndpoint {
    
    public function __construct($connectionString,$queueName=null,$exchangeName='amq.direct' ) {
        
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
        $this->channel->basic_qos(null,10, null); 
    }
    
    /**
     * 
     * @param \MQ\ConsProd\callable $callback
     */
    public function consume(\MQ\Delegate $callback) {
        $this->attach($callback);
        $this->start();
    }
    
    
}
