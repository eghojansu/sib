<?php

namespace Eghojansu\Bundle\SetupBundle\Utils;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class ArrayHelper
{
    /** @var array array to modify */
    private $array;

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    /**
     * Create static instance
     * @param  array  $array
     * @return this
     */
    public static function create(array $array)
    {
        return new static($array);
    }

    /**
     * Flatten multidimensional array
     * @return this
     */
    public function flatten()
    {
        $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($this->array));
        $this->array = iterator_to_array($it);

        return $this;
    }

    /**
     * Swap numeric key with its value
     * @param  mixed $fillValue
     * @return this
     */
    public function swapNumericKeyWithValue($fillValue = null)
    {
        foreach ($this->array as $key => $value) {
            if (is_numeric($key) && !is_array($value)) {
                $this->array[$value] = $fillValue;
                unset($this->array[$key]);
            }
        }

        return $this;
    }

    /**
     * Add element
     * @param string $key
     * @param mixed $value
     */
    public function add($key, $value)
    {
        $this->array[$key] = $value;

        return $this;
    }

    /**
     * Get processed value
     * @return array
     */
    public function getValue()
    {
        return $this->array;
    }
}
