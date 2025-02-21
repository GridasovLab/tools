<?php

/**
 *     Бывает, что на какое-то событие приходит много одинаковых пакетов,
 *     что не удобно, если на это событие надо создать какую-то сущность.
 *     
 *     Компонент останавливает код, если в redis уже записан идентификатор, по которому
 *     мы можем идентифицировать, что это дубликат пакеты.
 *     Какой укажем ttl (секунды), в течении такого времени будут блокироваться.
 *    
 *     Пример:
 *     $spamFilter = new SpamFilter(ttl: 1);
 *     $spamFilter->stopDuplicate($dealId);
 */


class SpamFilter
{
    protected Redis $redis;
    protected int $ttl = 0;

    public function __construct(int $ttl)
    {
        $this->ttl = $ttl;
        $this->redis = new Redis;
        $this->redis->connect('localhost');
    }

    public function stopDuplicate(int | string $data): void
    {
        ($this->isDataLocked($data)) ? exit : null;
        $this->lockData($data);
    }

    private function lockData(string $data): void
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

    public function getRedis()
    {
        return $this->redis;
    }

    public function deleteFromFilter(int|string $value): bool
    {
            return $this->redis->del($value) != 0;
    }
}
