<?php

    namespace Application\Events;
    
    use Wrench\Util\Event;

    class StatusEvents extends Event
    {
        public function clientConnected($data)
        {
            $this->parent->clientConnected($data[1]->getIp(), $data[1]->getPort());
        }

        public function clientDisconnected($data)
        {
            $this->parent->clientDisconnected($data[1]->getIp(), $data[1]->getPort());
        }
    }