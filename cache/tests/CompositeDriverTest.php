<?php

namespace kuiper\cache;

use kuiper\cache\driver\Composite;
use kuiper\cache\driver\Memory;
use kuiper\cache\driver\Redis;

class CompositeDriverTest extends BaseDriverTestCase
{
    public function setUp()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('extension redis is required');
        }
    }

    protected function createCachePool()
    {
        $redis = new Redis([
            'servers' => [['host' => getenv('REDIS_PORT_6379_TCP_ADDR') ?: 'localhost']],
            'database' => 15,
        ]);
        $redis->getRedis()->flushdb();

        return new Pool(new Composite([
            new Memory(),
            $redis,
        ]));
    }
}
