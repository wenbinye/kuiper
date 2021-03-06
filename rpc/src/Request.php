<?php

namespace kuiper\rpc;

use function GuzzleHttp\Psr7\stream_for;

class Request implements RequestInterface
{
    use MessageTrait;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $parameters;

    public function __construct($body, $method = null, array $parameters = [])
    {
        $this->stream = stream_for($body);
        $this->method = $method;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function withMethod($method)
    {
        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function withParameters(array $parameters)
    {
        $new = clone $this;
        $new->parameters = $parameters;

        return $new;
    }
}
