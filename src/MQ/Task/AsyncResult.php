<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MQ\Task;
use PhpAmqpLib\Message\AMQPMessage;
/**
 * Description of AsyncResult
 *
 * @author javier
 */
abstract class AsyncResult implements \SplSubject,  \Serializable {
    /**
     *
     * @var int  Unique TaskID
     */
    protected $taskid;
    
    /**
     *
     * @var \SplObjectStorage 
     */
    protected $observers;
    public function __construct($taskId=null) {
        $this->taskid=($taskId===null)?uniqid():$taskId;
        $this->observers = new \SplObjectStorage();
    }
    
    /**
     * Register listeners
     * @param \SplObserver $observer
     */
    public function attach(\SplObserver $observer) {
        $this->observers->attach($observer);
    }
    /**
     * Unregister listeners
     * @param \SplObserver $observer
     */
    public function detach(\SplObserver $observer) {
        $this->observers->detach($observer);
    }
    
    abstract public function isCompleted();
    
    /**
     * 
     */
    public function notify() {
        foreach($this->observers as $o) {
            $o->update($this);
        }
    }
    
    /**
     * Return unique id
     * @return string
     */
    public function getTaskId() {
        return $this->taskid;
    }

}
