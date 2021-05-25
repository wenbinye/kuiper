<?php

declare(strict_types=1);

namespace kuiper\web\session;

use Psr\Http\Message\ResponseInterface;

class EphemeralSession implements SessionInterface
{
    use SessionTrait;

    /**
     * @var array
     */
    private $data;

    public function start(): void
    {
    }

    public function get($index, $defaultValue = null)
    {
        return $this->data[$index] ?? $defaultValue;
    }

    public function set($index, $value): void
    {
        $this->data[$index] = $value;
    }

    public function has($index): bool
    {
        return array_key_exists($index, $this->data);
    }

    public function remove($index): void
    {
        unset($this->data[$index]);
    }

    public function getId(): string
    {
        return '';
    }

    public function isStarted(): bool
    {
        return true;
    }

    public function destroy($removeData = false): bool
    {
        return true;
    }

    public function regenerateId($deleteOldSession = true): void
    {
    }

    public function setCookie(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }

    public function current()
    {
        return current($this->data);
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        next($this->data);
    }

    public function key()
    {
        return key($this->data);
    }

    public function valid(): bool
    {
        return null !== key($this->data);
    }

    public function rewind(): void
    {
        reset($this->data);
    }
}
