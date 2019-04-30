<?php
namespace MQ\ConsProd;
/**
 * Description of Producer
 *
 * @author javier
 */
class Producer extends MQ\ClientEndpoint {
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
    
    public function publish($body,array $headers=array()) {
        $this->send($body, '', $headers);
    }
}
