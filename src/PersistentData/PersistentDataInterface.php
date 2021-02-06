<?php

namespace MadBit\SDK\PersistentData;

/**
 * Interface PersistentDataInterface.
 */
interface PersistentDataInterface
{
    /**
     * Get a value from a persistent data store.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key);

    /**
     * Set a value in the persistent data store.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set(string $key, $value);
}
