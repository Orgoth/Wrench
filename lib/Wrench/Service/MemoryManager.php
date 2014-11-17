<?php

    namespace Wrench\Service;
    
    use Wrench\Exception\Exception;
    
    use Wrench\Server;
    
    class MemoryManager
    {
        /** @var integer **/
        private $currentMemory;
        /** @var integer **/
        private $maxMemory;
        
        const DEFAULT_MEMORY_LIMIT = '128M';
        
        public function init()
        {
            $this->currentMemory = memory_get_usage(true);
            $this->initMaxMemory();
            Server::getInstance()->log("Current RAM : {$this->currentMemory}", 'info');
            Server::getInstance()->log("Max RAM : {$this->maxMemory}", 'info');
        }
        
        public function initMaxMemory()
        {
            $maxMemory = ini_get('memory_limit');
            
            if($maxMemory === '-1')
            {
                $maxMemory = static::DEFAULT_MEMORY_LIMIT;
                ini_set('memory_limit', $maxMemory);
            }
            $this->maxMemory = $maxMemory;
        }
        
        public function refreshMemory()
        {
            $this->currentMemory = memory_get_usage(false);
        }
        
        public function getCurrentMemory()
        {
            return $this->currentMemory;
        }
        
        public function setMaxMemory($maxMemory)
        {
            $memory = "$maxMemory\M";
            ini_set('memory_limit', $memory);
            
            if(ini_get('memory_limit') !== $memory)
            {
                throw new Exception('Cannot set more memory');
            }
            
            $this->maxMemory = $maxMemory;
            
            Server::getInstance()->log("Set max RAM : $memory", 'info');
        }
        
        public function getMaxMemory()
        {
            return $this->maxMemory;
        }
        
        public function getFreeMemory()
        {
            
        }
        
        public function getMemoryPeak()
        {
            return memory_get_peak_usage(false);
        }
    }