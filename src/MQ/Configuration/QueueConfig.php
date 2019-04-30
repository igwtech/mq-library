<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MQ\Configuration;

/**
 * Description of QueueConfig
 *
 * @author javier
 */
class QueueConfig {
    /**
     *
     * @var string 
     */
    public $Name=null;
    /**
     *
     * @var string 
     */
    public $Routing='#';
    /**
     *
     * @var string 
     */
    public $Dead=null;
}
