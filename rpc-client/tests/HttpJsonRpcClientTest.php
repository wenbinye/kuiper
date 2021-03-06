<?php

namespace kuiper\rpc\client;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use kuiper\annotations\AnnotationReader;
use kuiper\annotations\DocReader;
use kuiper\rpc\client\fixtures\ApiServiceInterface;
use kuiper\rpc\client\fixtures\Item;
use kuiper\rpc\client\fixtures\Request;
use kuiper\rpc\client\middleware\JsonRpc;
use kuiper\rpc\client\middleware\Normalize;
use kuiper\serializer\Serializer;
use PHPUnit_Framework_TestCase as TestCase;
use ProxyManager\Factory\RemoteObjectFactory;

class HttpJsonRpcClientTest extends TestCase
{
    public function createClient()
    {
        $docReader = new DocReader();
        $serializer = new Serializer(new AnnotationReader(), $docReader);

        $requests = [];
        $history = Middleware::history($requests);
        $mock = new MockHandler();
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $client = new HttpClient(['handler' => $handler]);
        $this->mockHandler = $mock;
        $this->requests = &$requests;

        $client = new Client(new HttpHandler($client));
        $client->add(new Normalize($serializer, $docReader), 'before:start');
        $client->add(new JsonRpc(), 'before:call');

        $factory = new RemoteObjectFactory($client);

        return $factory->createProxy(ApiServiceInterface::class);
    }

    public function testClient()
    {
        $client = $this->createClient();
        $this->mockHandler->append(new Response(200, [], '{"id":"1","result":[{"name":"foo"}]}'));
        $result = $client->query($this->createRequest('foo'));
        // print_r($result);
        $this->assertTrue(is_array($result));
        $this->assertInstanceOf(Item::class, $result[0]);

        $request = $this->requests[0]['request'];
        $this->assertEquals([
            "method" => "kuiper\\rpc\\client\\fixtures\\ApiServiceInterface.query",
            "id" => 1,
            "params" => [
                ["query" => "foo"]
            ]
        ], json_decode((string)$request->getBody(), true));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage invalid query
     */
    public function testException()
    {
        $client = $this->createClient();
        $this->mockHandler->append(new Response(200, [], '{"id":"1","error":{"code":-32000,"message":"invalid query","data":"YTozOntzOjU6ImNsYXNzIjtzOjI0OiJJbnZhbGlkQXJndW1lbnRFeGNlcHRpb24iO3M6NzoibWVzc2FnZSI7czoxMzoiaW52YWxpZCBxdWVyeSI7czo0OiJjb2RlIjtpOjA7fQ=="}}'));
        $client->query($this->createRequest('foo'));
    }

    public function createRequest($query)
    {
        $request = new Request();
        $request->setQuery($query);

        return $request;
    }
}
