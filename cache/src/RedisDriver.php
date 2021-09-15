<?php

declare(strict_types=1);

namespace kuiper\cache;

use kuiper\helper\Text;
use kuiper\swoole\pool\PoolInterface;
use Stash\Driver\AbstractDriver;
use Stash\Utilities;

/**
 * The Redis driver is used for storing data on a Redis system. This class uses
 * the PhpRedis extension to access the Redis server.
 *
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class RedisDriver extends AbstractDriver
{
    public const GROUP_KEY_PREFIX = 'group.';

    /**
     * The Redis drivers.
     *
     * @var PoolInterface
     */
    protected $redisPool;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * RedisDriver constructor.
     */
    public function __construct(PoolInterface $redisPool, array $options = [])
    {
        $this->redisPool = $redisPool;
        parent::__construct($options);
    }

    protected function setOptions(array $options = []): void
    {
        if (!isset($options['redisPool'])) {
            throw new \InvalidArgumentException('redisPool is required');
        }
        $this->prefix = $options['prefix'] ?? '';
        $this->redisPool = $options['redisPool'];
    }

    private function getRedis(): \Redis
    {
        return $this->redisPool->take();
    }

    /**
     * {@inheritdoc}
     */
    public function getData($key)
    {
        $data = $this->getRedis()->get($this->makeKeyString($key));
        if (false === $data) {
            return [];
        }

        return unserialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration)
    {
        $store = serialize(['data' => $data, 'expiration' => $expiration]);
        if (is_null($expiration)) {
            return $this->getRedis()->set($this->makeKeyString($key), $store);
        } else {
            $ttl = $expiration - time();

            // Prevent us from even passing a negative ttl'd item to redis,
            // since it will just round up to zero and cache forever.
            if ($ttl < 1) {
                return true;
            }

            return $this->getRedis()->setex($this->makeKeyString($key), $ttl, $store);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear($key = null)
    {
        if (is_null($key)) {
            $this->getRedis()->flushDB();

            return true;
        }

        $redis = $this->getRedis();
        $keyReal = $this->makeKeyString($key);
        $redis->del($keyReal); // remove direct item.
        if ($this->isGroupKey($key)) {
            $keyString = $this->makeKeyString($key, true);
            $redis->incr($keyString); // increment index for children items
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function isAvailable()
    {
        return class_exists('Redis', false);
    }

    /**
     * 优化：对于一级 key 不做索引。如果需要使用级联删除，必须使用多级路径.
     *
     * Turns a key array into a key string. This includes running the indexing functions used to manage the Redis
     * hierarchical storage.
     *
     * When requested the actual path, rather than a normalized value, is returned.
     */
    protected function makeKeyString(array $key, bool $path = false): string
    {
        $keyString = 'cache:::';
        $pathKey = ':pathdb::';
        $nodeList = Utilities::normalizeKeys($key);
        if (!$path && !$this->isGroupKey($key)) {
            return $this->prefix.md5($nodeList[0]);
        }

        $redis = $this->getRedis();
        foreach ($nodeList as $i => $name) {
            //a. cache:::name
            //b. cache:::name0:::sub
            $keyString .= $name;

            //a. :pathdb::cache:::name
            //b. :pathdb::cache:::name0:::sub
            $pathKey = ':pathdb::'.$keyString;
            $pathKey = $this->prefix.md5($pathKey);

            $index = $redis->get($pathKey);
            $this->keyCache[$pathKey] = $index;

            //a. cache:::name0:::
            //b. cache:::name0:::sub1:::
            $keyString .= '_'.$index.':::';
        }

        return $path ? $pathKey : $this->prefix.md5($keyString);
    }

    private function isGroupKey(array $key): bool
    {
        return count($key) > 1 || Text::startsWith($key[0], self::GROUP_KEY_PREFIX);
    }

    /**
     * {@inheritdoc}
     */
    public function isPersistent()
    {
        return true;
    }
}
