<?php
namespace kuiper\di;

use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use RuntimeException;
use Closure;

/**
 * Creates proxy classes.
 *
 * Wraps Ocramius/ProxyManager LazyLoadingValueHolderFactory.
 */
class ProxyFactory
{
    /**
     * @var LazyLoadingValueHolderFactory|null
     */
    private $proxyManager;

    /**
     * Creates a new lazy proxy instance of the given class with
     * the given initializer.
     *
     * @param string   $className   name of the class to be proxied
     * @param Closure $initializer initializer to be passed to the proxy
     *
     * @return \ProxyManager\Proxy\LazyLoadingInterface
     */
    public function createProxy($className, Closure $creator)
    {
        return $this->getProxyManager()->createProxy(
            $className,
            function (& $wrappedObject, $proxy, $method, $params, & $initializer) use ($creator) {
                $initializer = null;
                $wrappedObject = $creator();
                return true;
            }
        );
    }

    private function getProxyManager()
    {
        if ($this->proxyManager !== null) {
            return $this->proxyManager;
        }

        if (! class_exists('ProxyManager\Configuration')) {
            throw new RuntimeException('The ocramius/proxy-manager library is not installed. Lazy injection requires that library to be installed with Composer in order to work. Run "composer require ocramius/proxy-manager".');
        }
        return $this->proxyManager = new LazyLoadingValueHolderFactory();
    }

    public function setProxyManager(LazyLoadingValueHolderFactory $factory)
    {
        $this->proxyManager = $factory;
        return $this;
    }
}
