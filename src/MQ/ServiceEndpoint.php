<?php
namespace MQ;
use PhpAmqpLib\Message\AMQPMessage;
/**
 * Description of ServiceEndpoint
 *
 * @author javier
 */
class ServiceEndpoint  extends Endpoint {
    
    /**
     *
     * @var \SplObjectStorage 
     */
    protected $observers;
    
    /**
     *
     * @var boolean 
     */
    protected $keepRunning;

    public function __construct($connectionString, Configuration\EndpointConfig $config) {
        parent::__construct($connectionString, $config);
        $this->channel->basic_qos(null,10, null);
        $this->observers=new \SplObjectStorage();
        if(!empty($this->config->Queues)) {
            foreach($this->config->Queues as $queue) {
                $this->channel->basic_consume($queue->Name, '', false, false, false, false, array($this,'onMessage'));
            }
        }
    }

    public function attach(Delegate $observer) {
        $this->observers->attach($observer);
    }

    public function detach(Delegate $observer) {
        $this->observers->detach($observer);
    }

    protected function wait($time=100) {        
        while ($this->keepRunning && $time ===null) {    
            $this->channel->wait($time);
            gc_collect_cycles();
            usleep(100);
        }
    }
    
    public function __destruct() {
        $this->keepRunning=false;
        usleep(100);
        parent::__destruct();
        
    }

    public function start($block=true) {
        $this->keepRunning=true;
        usleep(100);
        if($block) {
            $this->wait(null);
        }else{
            register_tick_function(array($this,'wait'));
        }
    }
    
    public function stop() {
        $this->keepRunning=false;
        usleep(100);
    }
    
    /**
     * Default callback used when messages are received on the queue
     * This callback can be overriden to implement specific programming models.
     * Every Observer attached (callable) will be called in order of registration if the Event Handler returns TRUE
     * The event is assumed as handled and the callback process will break early.
     * @param AMQPMessage $item
     * @return void
     */
    public function onMessage(AMQPMessage $item) {
        error_log( "Processing Queue Item {$item->get('consumer_tag')}-{$item->get('delivery_tag')} Count: {$this->counter}\n");
        $data = $item->body;
        $headers = $item->get('application_headers')->getNativeData();
        try {
            /**
             * @var IConsumer Description
             */
            $obj=null;
            $resp=true;
            foreach($this->observers as $obj) { 
                // Each observer should be a valid callable (Delegate)
                $resp=call_user_func_array($obj,array($data,$headers));
                if($resp) {
                    // The event is assumed as handled and the callback process should break early.
                    break;
                }
            }
            
            $this->counter++;
            $this->channel->basic_ack($item->get('delivery_tag'));
        } catch (Exception $ex) {
            error_log($ex->getMessage());
            $this->channel->basic_nack($item->get('delivery_tag'));
        }
        return;
    }


    
    
}
