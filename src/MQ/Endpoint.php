<?php
namespace MQ;
use PhpAmqpLib\Connection\AMQPConnection;
use MQ\Configuration\ExchangeConfig;
use MQ\Configuration\QueueConfig;
/**
 * Description of Endpoint
 *
 * @author javier
 */
class Endpoint {
    const DEFAULT_AMQP_PORT=5672;
    const DEFAULT_STOMP_PORT=61613;
    const DEFAULT_MQTT_PORT=8883;
    /**
     *
     * @var AMQPConnection 
     */
    protected $connection;
    /**
     *
     * @var AMQPChannel 
     */
    protected $channel;
    /**
     *
     * @var Configuration\EndpointConfig; 
     */
    protected $config;
    
     /**
     *
     * @var int 
     */
    protected $counter;

    /**
     *
     * @var type 
     */
    protected $serializer;

    /**
     * 
     * @param string $connectionString URL like connection string
     * @param \MQ\Configuration\EndpointConfig $config
     * @throws \ErrorException
     */
    public function __construct($connectionString, Configuration\EndpointConfig $config) {
        $this->counter=0;
        $connectionParams=parse_url($connectionString);
        if(false===$connectionParams) {
            throw new \ErrorException('Invalid connection string');
        }
        
        if(!isset($connectionParams['port'])) {
            switch($connectionParams['scheme']) {
                case 'amqp':
                    $connectionParams['port']=ServiceEndpoint::DEFAULT_AMQP_PORT;
                    break;
                case 'mqtt':
                    $connectionParams['port']=ServiceEndpoint::DEFAULT_MQTT_PORT;
                    break;
                case 'stomp':
                    $connectionParams['port']=ServiceEndpoint::DEFAULT_STOMP_PORT;
                    break;
            }
        }
        $this->config=$config;
        $this->connect($connectionParams);
        $this->serializer = new \Zumba\Util\JsonSerializer();
    }
    /**
     * 
     * @param array $connectionParams
     * @return type
     * @throws \InvalidArgumentException
     */
    public function connect(array $connectionParams) {
        error_log( "Connecting to MQ Server");
        switch ($connectionParams['scheme']){
            case 'amqp':
                return $this->connectAMQP($connectionParams);
            case 'mqtt':
            case 'stomp':
                throw new \InvalidArgumentException();
        }
        
    }
    
    /**
     * 
     * @param array $connectionParams
     * @throws \MQ\Exception
     */
    protected function connectAMQP(array $connectionParams) {
        try {
            $this->connection = new AMQPConnection($connectionParams['host'], $connectionParams['port'], $connectionParams['user'], $connectionParams['pass'],$connectionParams['path']);
            error_log( "Opening Channel");
            $this->channel = $this->connection->channel();
            $this->setup();
        }  catch (Exception $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    /**
     * 
     * @param QueueConfig $queue
     */
    protected function setupQueue(QueueConfig $queue) {
        error_log('Declaring queue '. ((empty($queue->Name))?"Anonymous":$queue->Name ));
        $queue_props=array();
        if(!empty($queue->Dead)) {
            $queue_prop['x-dead-letter-exchange']=array('S','amp.direct');
            $queue_prop['x-dead-letter-routing-key']=array('S',$queue->Dead);
        }
        if(empty($queue->Name)) {
            list($queue->Name)=$this->channel->queue_declare(null, false, false, false, true, false, $queue_props);
        }else{
            list($queue->Name)=$this->channel->queue_declare($queue->Name, true, true, false, false, false, $queue_props);
        }
    }
    
    /**
     * 
     * @param QueueConfig $queue
     * @param ExchangeConfig $exchange
     */
    protected function setupBind(QueueConfig $queue, ExchangeConfig $exchange) {
        error_log( "Binding Queue {$queue->Name} to Exchange {$exchange->Name}...\n");
        switch($this->config->Exchange->Type) {
            case ExchangeConfig::EXCHANGE_TYPE_DIRECT:
                $this->channel->queue_bind($queue->Name, $exchange->Name, $queue->Name);
                break;
            case ExchangeConfig::EXCHANGE_TYPE_FAN:
                $this->channel->queue_bind($queue->Name, $exchange->Name);
                break;
            case ExchangeConfig::EXCHANGE_TYPE_TOPIC:
            case ExchangeConfig::EXCHANGE_TYPE_HEADERS:
                $this->channel->queue_bind($queue->Name, $exchange->Name, $queue->Routing);
                break;
        }
    }
    
    /**
     * 
     * @param ExchangeConfig $exchange
     */
    protected function setupExchange(ExchangeConfig $exchange) {
        $this->channel->exchange_declare($exchange->Name, $exchange->Type, true, false, false);   
    }


    /**
     * Declares exchanges and queues, setup binds
     */
    protected function setup() {
        error_log( "Declaring Exchange\n");
        $this->setupExchange($this->config->Exchange);
        error_log( "Declaring Queues\n");
        if(isset($this->config->Queues)) {
            foreach($this->config->Queues as $queue) {
                $this->setupQueue($queue);
                $this->setupBind($queue,$this->config->Exchange);
            }
        }else{
            
        }
    }
    
    /**
     * Closes and cleans up connections
     */
    public function __destruct() {
        $this->channel->close();
        $this->connection->close();
    }
}
