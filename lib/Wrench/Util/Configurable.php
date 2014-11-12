<?php

namespace Wrench\Util;

use Wrench\Protocol\Protocol;
use Wrench\Protocol\Rfc6455Protocol;
use \InvalidArgumentException;

/**
 * Configurable base class
 */
abstract class Configurable
{
    /**
     * @var []
     */
    protected $options = [];

    /**
     * @var Protocol
     */
    protected $protocol;

    /**
     * Configurable constructor
     *
     * @param []  $options (optional)
     *   Options:
     *     - protocol             => Wrench\Protocol object, latest protocol
     *                                 version used if not specified
     */
    public function __construct(
        array $options = []
    ) {
        $this->configureOptions($options);
        $this->configureProtocol();
    }

    /**
     * Configures the options
     *
     * @param array $options
     */
    protected function configureOptions(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Configures the protocol option
     *
     * @throws InvalidArgumentException
     */
    protected function configureProtocol()
    {
        $this->protocol = new Rfc6455Protocol();

        if (!$this->protocol || !($this->protocol instanceof Protocol)) {
            throw new InvalidArgumentException('Invalid protocol option');
        }

    }
    
    /**
     * Set a defined option
     * 
     * @param string $name
     * @param mixed $value
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }
    
    /**
     * Gets all the options
     * 
     * @return []
     */
    public function getOptions()
    {
        return $this->options;
    }
    
    /**
     * Gets a requested option
     * 
     * @param string $name
     * @return mixed|boolean
     */
    public function getOption($name)
    {
        if($this->hasOption($name))
        {
            return $this->option[$name];
        }
        return false;
    }
    
    /**
     * Removes an option
     * 
     * @param string $name
     * @return boolean
     */
    public function removeOption($name)
    {
        if($this->hasOption($name))
        {
            unset($this->options[$name]);
            return true;
        }
        return false;
    }
    
    /**
     * Checks if a given option exists
     * 
     * @param string $name
     * @return boolean
     */
    public function hasOption($name)
    {
        return isset($this->options[$name]);
    }
}