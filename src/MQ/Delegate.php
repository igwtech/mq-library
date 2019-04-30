<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace MQ;
/**
 * Description of Delegate
 * This class abstracts a callable
 * @author javier
 */
class Delegate {
    /**
     *
     * @var callable 
     */
    private $callback;
    /**
     * 
     * @param callable $callback
     * @throws Exception
     */
    public function __construct($callback) {
        if(!is_callable($callback)) {
            throw new Exception('Delegates must be instanciated with a valid Callback');
        }
        $this->callback = $callback;
    }
    /**
     * 
     * @return mixed
     */
    public function __invoke() {
        return call_user_func_array($this->callback, func_get_args());
    }
}
