<?php

namespace MadBit\SDK\Authentication;

use DateTime;

class AccessToken
{
    /**
     * The access token value.
     *
     * @var string
     */
    protected $value = '';

    /**
     * Date when token expires.
     *
     * @var null|DateTime
     */
    protected $expiresAt;

    /**
     * Create a new access token entity.
     *
     * @param string $accessToken
     * @param int    $expiresAt
     */
    public function __construct(string $accessToken, $expiresAt = 0)
    {
        $this->value = $accessToken;
        if ($expiresAt) {
            $this->setExpiresAtFromTimeStamp($expiresAt);
        }
    }

    /**
     * Returns the access token as a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getValue();
    }

    /**
     * Getter for expiresAt.
     *
     * @return null|DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * Determines whether or not this is an app access token.
     *
     * @return bool
     */
    public function isAppAccessToken(): bool
    {
        return false !== strpos($this->value, '|');
    }

    /**
     * Determines whether or not this is a long-lived token.
     *
     * @return bool
     */
    public function isLongLived(): bool
    {
        if ($this->expiresAt) {
            return $this->expiresAt->getTimestamp() > time() + (60 * 60 * 2);
        }

        if ($this->isAppAccessToken()) {
            return true;
        }

        return false;
    }

    /**
     * Checks the expiration of the access token.
     *
     * @return null|bool
     */
    public function isExpired(): bool
    {
        if ($this->getExpiresAt() instanceof DateTime) {
            return $this->getExpiresAt()->getTimestamp() < time();
        }

        if ($this->isAppAccessToken()) {
            return false;
        }

        return false;
    }

    /**
     * Returns the access token as a string.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Setter for expires_at.
     *
     * @param int $timeStamp
     */
    protected function setExpiresAtFromTimeStamp(int $timeStamp)
    {
        $dt = new DateTime();
        $dt->setTimestamp($timeStamp);
        $this->expiresAt = $dt;
    }
}
