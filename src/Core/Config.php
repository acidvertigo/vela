<?php

namespace Vela\Core;

/**
 * Config class
 *
 * Simple class to store or get elements from configuration registry
 */

class Config implements \ArrayAccess, \Countable, \IteratorAggregate
{

    use ArrayAccess;

    /** @var array $data Data configuration array */
    private $data = [];

    /**
     * Class constructor
     * @param array $data List of values to add to the configuration registry
     */
    public function __construct(array $data = [])
    {
        if(!empty($data)) {
            foreach ($data as $key => $value) {
                $this->set($key, $value);
            }
        }
    }

    /**
     * Adds element to config array
     *
     * @param string $key - Config Key
     * @param mixed $value - Config Value
     * @throws Exception When there is a duplicate $key
     */
    public function set($key, $value)
    {
        if (isset($this->data[$key]))
        {
            throw new \Exception('There is already an entry for key: ' . $key);
        }

        $this->data[$key] = $value;
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
        if (!isset($this->data[$key]))
        {
            throw new \Exception('There is no entry for key: ' . $key);
        }

        return $this->data[$key];
    }

    /**
     * Remove an entry from the Config
     *
     * @param string $key
     * @return void
     */
    public function remove($key)
    {      
        unset($this->data[$key]);
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

    /**
     * Reset Config container
     */
    public function reset() {
        $this->data = [];
    }

    /**
     * Return total number of data elements
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * IteratorAggregate interface required method
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

} 
