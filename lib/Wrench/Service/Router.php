<?php

    namespace Wrench\Service;
    
    class Router
    {
        public function __construct()
        {
            
        }
        
        public function load($applicationName, $configFile)
        {
            return json_decode(file_get_contents("Application/$applicationName/Config/$configFile"), true);
        }
    }