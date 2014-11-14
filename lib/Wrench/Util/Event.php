<?php

    namespace Wrench\Util;

    abstract class Event
    {
        protected $parent;
        
        public function __construct($parent)
        {
            $this->parent = $parent;
        }
    }