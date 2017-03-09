<?php

namespace Maghead\Sharding;

use ArrayAccess;
use IteratorAggregate;
use ArrayIterator;
use Maghead\Sharding\Hasher\FlexihashHasher;
use Maghead\Sharding\Hasher\Hasher;

class ShardCollection implements ArrayAccess, IteratorAggregate
{
    protected $shards;

    protected $mapping;

    public function __construct(array $shards, ShardMapping $mapping = null)
    {
        $this->shards = $shards;
        $this->mapping = $mapping;
    }

    public function getMapping()
    {
        return $this->mapping;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->shards);
    }
    
    public function offsetSet($name,$value)
    {
        $this->shards[ $name ] = $value;
    }
    
    public function offsetExists($name)
    {
        return isset($this->shards[ $name ]);
    }
    
    public function offsetGet($name)
    {
        return $this->shards[ $name ];
    }
    
    public function offsetUnset($name)
    {
        unset($this->shards[$name]);
    }

    public function createDispatcher(Hasher $hasher = null)
    {
        if (!$hasher) {
            $hasher = new FlexihashHasher($this->mapping);
        }
        return new ShardDispatcher($this->mapping, $hasher, $this);
    }
}
