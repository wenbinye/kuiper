<?php

declare(strict_types=1);

namespace kuiper\db;

class PooledTransactionManager implements TransactionManagerInterface
{
    /**
     * @var ConnectionPoolInterface
     */
    private $connectionPool;

    public function __construct(ConnectionPoolInterface $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    /**
     * {@inheritdoc}
     */
    public function transaction(callable $callback)
    {
        $manager = new TransactionManager($this->connectionPool->take());

        return $manager->transaction($callback);
    }
}
