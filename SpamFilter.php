<?php

/**
 *     Бывает, что на какое-то событие приходит много одинаковых пакетов,
 *     что не удобно, если на это событие надо создать какую-то сущность.
 *     
 *     Компонент останавливает код, если в redis уже записан идентификатор, по которому
 *     мы можем идентифицировать, что это дубликат пакеты.
 *     Какой укажем ttl, в течении такого времени будут блокироваться.
 *    
 *     Пример:
 *     $redis = new Redis;
 *     $spamFilter = new SpamFilter(redis: $redis, ttl: 1);
 *     $spamFilter->stopDuplicate($dealId);
 */

class SpamFilter
{
    protected Redis $redis;
    protected int $ttl = 0;

    public function __construct(Redis $redis, int $ttl)
    {
        $this->redis = $redis;
        $this->ttl = $ttl;
        $this->redis->connect('localhost', 6379);
    }

    public function stopDuplicate(int | string $data): void
    {
        ($this->isDataLocked($data)) ? exit : null;
        $this->lockData($data);
    }

    private function lockData(int | string $data): void
    {
        $this->redis->setex($data, $this->ttl, 'the task is begining processed');
    }

    public function isDataLocked(int | string $data): bool
    {
        $allLocks = $this->getAllLocks();

        if ($allLocks === false) {
            return false;
        }

        foreach ($allLocks as $key => $value) {
            if ($key == $data) {
                return true;
            }
        }

        return false;
    }

    public function getAllLocks(): array | bool
    {
        $keys = $this->redis->keys('*');
        $array = $this->redis->mget($keys);

        if (is_array($array)) {
            return array_combine($keys, $array);
        }

        return false;
    }
}
