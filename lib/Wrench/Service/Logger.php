<?php

    namespace Wrench\Service;
    
    use Wrench\Server;
    use Wrench\Exception\Exception;
    
    class Logger
    {
        private $colors = [
            'blue'          => '0;34',
            'light_blue'    => '1;34',
            'green'         => '0;32',
            'light_green'   => '1;32',
            'red'           => '0;31',
            'light_red'     => '1;31',
            'yellow'        => '1;33'
        ];
        
        private $types = [
            'info'      => 'light_blue',
            'important' => 'blue',
            'validate'  => 'green',
            'warning'   => 'light_red',
            'warn'      => 'light_red',
            'error'     => 'red',
            'notice'    => 'yellow',
            'debug'     => 'yellow',
        ];
        
        /**
         * Displays a message in the server shell
         * 
         * @param string $type
         * @param string $message
         * @throws Exception
         */
        public function log($type, $message)
        {
            if($this->checkType($type) === false)
            {
                throw new Exception("The given type $type is not referenced.");
            }
            
            $this->display($this->colors[$this->types[$type]], $message);
        }
        
        public function display($color, $message)
        {
            echo date('[H:G:i]')." \033[{$color}m $message \033[0m".PHP_EOL;
        }
        
        /**
         * @param string $type
         */
        public function checkType($type)
        {
            return (isset($this->types[$type]) && isset($this->colors[$this->types[$type]]));
        }
    }