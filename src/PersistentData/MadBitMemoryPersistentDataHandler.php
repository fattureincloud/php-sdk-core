<?php

namespace MadBit\SDK\PersistentData;

class MadBitMemoryPersistentDataHandler implements PersistentDataInterface
{
    /**
     * @var array the session data to keep in memory
     */
    protected $sessionData = [];

    /**
     * {@inheritdoc}
     */
    public function get(string $key)
    {
        return isset($this->sessionData[$key]) ? $this->sessionData[$key] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value)
    {
        $this->sessionData[$key] = $value;
    }
}
