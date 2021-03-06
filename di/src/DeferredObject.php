<?php

namespace kuiper\di;

/**
 * Initialize object later.
 */
class DeferredObject
{
    /**
     * @var mixed
     */
    private $instance;

    /**
     * @var callable
     */
    private $initializer;

    public function __construct($instance, callable $initializer)
    {
        $this->instance = $instance;
        $this->initializer = $initializer;
    }

    public function getInstance()
    {
        return $this->instance;
    }

    public function initialize()
    {
        call_user_func($this->initializer, $this->instance);
    }
}
