<?php
namespace kuiper\web\annotation\filter;

use Interop\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class DeleteOnly extends AbstractFilter
{
    /**
     * @inheritDoc
     */
    public function createMiddleware(ContainerInterface $container)
    {
        return new \kuiper\web\middlewares\DeleteOnly();
    }
}
