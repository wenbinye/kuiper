<?php

namespace kuiper\boot\providers;

use GuzzleHttp\Client as HttpClient;
use kuiper\boot\Events;
use kuiper\boot\Provider;
use kuiper\di;
use kuiper\helper\Arrays;
use kuiper\rpc\client\Client as RpcClient;
use kuiper\rpc\client\HttpHandler;
use kuiper\rpc\client\middleware\JsonRpc;
use kuiper\rpc\client\middleware\Normalize;
use kuiper\rpc\client\TcpHandler;
use kuiper\serializer\NormalizerInterface;
use kuiper\serializer\Serializer;
use ProxyManager\Configuration;
use ProxyManager\Factory\RemoteObjectFactory;
use Symfony\Component\EventDispatcher\GenericEvent as Event;

class RpcClientProvider extends Provider
{
    public function register()
    {
        $rpcServices = [];
        foreach ($this->settings['app.rpc.services'] as $group => $services) {
            if (is_string($services)) {
                $serviceName = $services;
                $rpcServices[$serviceName] = di\factory([$this, 'createProxy'], $serviceName);
            } else {
                foreach ($services as $serviceName) {
                    $rpcServices[$serviceName] = di\factory([$this, 'createProxy'], $serviceName, $group);
                }
            }
        }

        $this->services->addDefinitions(array_merge($rpcServices, [
            NormalizerInterface::class => di\get(Serializer::class),
        ]));
    }

    public function createProxy($serviceName, $group = null)
    {
        $config = $this->settings['app.rpc'];
        $server = $this->getServer($serviceName, $group);
        if (empty($server)) {
            throw new \InvalidArgumentException("Server uri for '$serviceName' should not be empty");
        }
        if (parse_url($server, PHP_URL_SCHEME) == 'tcp') {
            $handler = new TcpHandler([$server]);
        } else {
            $options = Arrays::fetch($config, 'http_client', $this->settings['app.http_client'] ?: []);
            $handler = new HttpHandler(new HttpClient($options), $server);
        }

        $client = new RpcClient($handler);
        $client->add($this->app->get(Normalize::class), 'before:start', 'normalize');
        $client->add(new JsonRpc(Arrays::fetch($config, 'aliases', [])), 'before:call', 'jsonrpc');
        if (!empty($config['middlewares'])) {
            foreach ($config['middlewares'] as $middleware) {
                $middleware = (array) $middleware;
                $client->add(
                    $this->app->get($middleware[0]),
                    $position = isset($middleware[1]) ? $middleware[1] : 'before:call',
                    $id = isset($middleware[2]) ? $middleware[2] : null
                );
            }
        }
        $this->app->getEventDispatcher()->dispatch(Events::BOOT_RPC_CLIENT, new Event($client));

        $proxyConfig = new Configuration();
        $proxyConfig->setProxiesTargetDir($this->settings['app.runtime_path']);

        $factory = new RemoteObjectFactory($client, $proxyConfig);

        return $factory->createProxy($serviceName);
    }

    /**
     * @param string $serviceName
     * @param string $group
     *
     * @return string
     */
    private function getServer($serviceName, $group)
    {
        $config = $this->settings['app.rpc.servers'];
        if (isset($config[$serviceName])) {
            return $config[$serviceName];
        }
        if (isset($group) && isset($config[$group])) {
            return $config[$group];
        }

        return $config['default'];
    }
}
