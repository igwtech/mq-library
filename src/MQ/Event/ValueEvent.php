<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MQ\Event;

/**
 * Description of ValueEvent
 * Simplistic concrete implementation of IEvent interface for a simple key-value event
 *
 * @author javier
 */
class ValueEvent implements IEvent {
    protected $value;
    protected $key;
    
    public static function getEventName(){
        return strtolower(preg_replace_callback(
            '/(^|[a-z])([A-Z])/', 
            function($m) { return strtolower(strlen($m[1]) ? "{$m[1]}_{$m[2]}" : $m[2]); },
            str_replace("\\", ".", get_called_class()) 
          )); 
                
    }
    public function __construct($value,$key=null) {
        if($key === null ){
            $key = self::getEventName();
        }
        $this->value=$value;
        $this->key=$key;
    }

    public function getEventType() {
        return $this->key;
    }

    public function getSender() {
        return __FILE__;
    }
    public function getValue() {
        return $this->value;
    }
}
