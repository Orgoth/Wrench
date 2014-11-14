<?php

    namespace Application\Events;
    
    use Wrench\Util\Event;

    class StatusEvents extends Event
    {
        public function clientConnected($data)
        {
            $this->parent->_sendServerInfo();
        }

        public function clientDisconnected($data)
        {
            $this->parent->_sendServerInfo();
        }
    }