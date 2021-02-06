<?php

namespace MadBit\SDK\PersistentData;

use MadBit\SDK\Exceptions\MadBitSDKException;

class MadBitSessionPersistentDataHandler implements PersistentDataInterface
{
    /**
     * @var string prefix to use for session variables
     */
    protected $sessionPrefix = 'MADBIT_';

    /**
     * Init the session handler.
     *
     * @param bool $enableSessionCheck
     *
     * @throws MadBitSDKException
     */
    public function __construct($enableSessionCheck = true)
    {
        if ($enableSessionCheck && PHP_SESSION_ACTIVE !== session_status()) {
            throw new MadBitSDKException(
                'Sessions are not active. Please make sure session_start() is at the top of your script.',
                720
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key)
    {
        if (isset($_SESSION[$this->sessionPrefix.$key])) {
            return $_SESSION[$this->sessionPrefix.$key];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value)
    {
        $_SESSION[$this->sessionPrefix.$key] = $value;
    }
}
