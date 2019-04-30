<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MQ\Event;

/**
 * Description of IEvent
 *
 * @author javier
 */
interface IEvent {
    public function getSender();
    public function getEventType();
}
