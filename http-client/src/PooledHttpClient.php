<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use kuiper\swoole\pool\PoolFactoryInterface;
use Psr\Http\Message\RequestInterface;

class PooledHttpClient implements ClientInterface
{
    /**
     * @var \kuiper\swoole\pool\PoolInterface
     */
    private $httpClientPool;

    public function __construct(PoolFactoryInterface $poolFactory, array $options = [])
    {
        $this->httpClientPool = $poolFactory->create($options['pool'] ?? 'http-client',
            static function () use ($options) {
                return new Client($options);
            });
    }

    public function __call($method, $args)
    {
        return $this->httpClientPool->take()->$method(...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function send(RequestInterface $request, array $options = [])
    {
        return $this->httpClientPool->take()->send($request, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function sendAsync(RequestInterface $request, array $options = [])
    {
        return $this->httpClientPool->take()->sendAsync($request, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function request($method, $uri, array $options = [])
    {
        return $this->httpClientPool->take()->request($method, $uri, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function requestAsync($method, $uri, array $options = [])
    {
        return $this->httpClientPool->take()->requestAsync($method, $uri, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig($option = null)
    {
        return $this->httpClientPool->take()->getConfig($option);
    }
}