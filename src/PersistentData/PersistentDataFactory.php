<?php

namespace MadBit\SDK\PersistentData;

use InvalidArgumentException;

class PersistentDataFactory
{
    /**
     * PersistentData generation.
     *
     * @param null|PersistentDataInterface|string $handler
     *
     * @throws InvalidArgumentException if the persistent data handler isn't "session", "memory", or an instance of MadBit\Core\PersistentData\PersistentDataInterface
     *
     * @return PersistentDataInterface
     */
    public static function createPersistentDataHandler($handler)
    {
        if (!$handler) {
            return PHP_SESSION_ACTIVE === session_status()
                ? new MadBitSessionPersistentDataHandler()
                : new MadBitMemoryPersistentDataHandler();
        }

        if ($handler instanceof PersistentDataInterface) {
            return $handler;
        }

        if ('session' === $handler) {
            return new MadBitSessionPersistentDataHandler();
        }
        if ('memory' === $handler) {
            return new MadBitMemoryPersistentDataHandler();
        }

        throw new InvalidArgumentException('The persistent data handler must be set to "session", "memory", or be an instance of MadBit\Core\PersistentData\PersistentDataInterface');
    }
}
