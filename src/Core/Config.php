<?php

namespace Vela\Core;

/**
 * Config class
 *
 * Simple class to store or get elements from configuration registry
 */

class Config
{

     /** @var array $data Data configuration array */
    private $data = [];

    /**
     * Class constructor
     * @param array $data List of values to add to the configuration registry
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Retrieves elements from config array
     *
     * @param string $key
     * @return mixed returns a config value
     * @throws Exception when no $key found
     */
    public function get($key)
    {
        $data = $this->data;
        $parts = explode('.', $key);
        
        foreach ($parts as $part)
        {
            if (isset($data[$part]))
            {
               $data = $data[$part];
            } else {
                throw new \InvalidArgumentException ('Cannot find configuration key: ' . $key);
            } 
            
        }

        return $data;
    }

    /**
     * Return true if value is empty for given key
     *
     * @param string $key 
     * @return bool
     */
    public function isEmpty($key)
    {
        return empty($this->data[$key]);
    }

} 
