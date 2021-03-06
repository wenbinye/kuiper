<?php

namespace kuiper\di;

use Psr\Container\ContainerInterface as PsrContainer;

trait ContainerAwareTrait
{
    /**
     * @var PsrContainer
     */
    protected $container;

    /**
     * Sets the container.
     *
     * @param PsrContainer $container
     */
    public function setContainer(PsrContainer $container)
    {
        $this->container = $container;
    }
}
