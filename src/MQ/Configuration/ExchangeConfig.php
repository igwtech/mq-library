<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace MQ\Configuration;
/**
 * Description of ExchangeConfig
 *
 * @author javier
 */
class ExchangeConfig {
    
    const EXCHANGE_TYPE_DIRECT='direct';
    const EXCHANGE_TYPE_FAN='fan';
    const EXCHANGE_TYPE_TOPIC='topic';
    const EXCHANGE_TYPE_HEADERS='headers';
    
    /**
     *
     * @var string 
     */
    protected $Name;
    /**
     *
     * @var string Enum of valid Exchange types
     */
    protected $Type;
    
    
    /**
     * 
     * @param type $name
     * @param type $value
     * @return $this
     * @throws \RuntimeException
     */
    public function __set($name, $value) {
        $var_names=get_object_vars($this);
        if(!in_array($name, $var_names)) {
            throw new \RuntimeException('Invalid Property Name:'.$name.' in object '.__CLASS__);
        }
        if($name ==='Type') {
            switch (strtolower($value)) {
                case ExchangeConfig::EXCHANGE_TYPE_DIRECT:
                    $this->Type=ExchangeConfig::EXCHANGE_TYPE_DIRECT;
                    break;
                case ExchangeConfig::EXCHANGE_TYPE_FAN:
                    $this->Type=ExchangeConfig::EXCHANGE_TYPE_FAN;
                    break;
                case ExchangeConfig::EXCHANGE_TYPE_TOPIC:
                    $this->Type=ExchangeConfig::EXCHANGE_TYPE_TOPIC;
                    break;
                case ExchangeConfig::EXCHANGE_TYPE_HEADERS:
                    $this->Type=ExchangeConfig::EXCHANGE_TYPE_HEADERS;
                    break;
                default:
                    throw new \RuntimeException('Invalid '.$name.' value: '.$value.' for Object '.__CLASS__);
            }
        }else{
            $this->$name=$value;
        }
        return $this;
    }
    
    /**
     * 
     * @param type $name
     * @return type
     * @throws \RuntimeException
     */
    public function __get($name) {
        $var_names=get_object_vars($this);
        if(!in_array($name, $var_names)) {
            throw new \RuntimeException('Invalid Property Name:'.$name.' in object '.__CLASS__);
        }
        return $this->$name;
    }
    
}
