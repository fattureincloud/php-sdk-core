<?php

namespace MadBit\SDK\Url;

class MadBitUrlDetectionHandler implements UrlDetectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getCurrentUrl(): string
    {
        return $this->getHttpScheme().'://'.$this->getHostName().$this->getServerVar('REQUEST_URI');
    }

    /**
     * Get the currently active URL scheme.
     *
     * @return string
     */
    protected function getHttpScheme(): string
    {
        return $this->isBehindSsl() ? 'https' : 'http';
    }

    /**
     * Tries to detect if the server is running behind an SSL.
     *
     * @return bool
     */
    protected function isBehindSsl(): bool
    {
        // Check for proxy first
        $protocol = $this->getHeader('X_FORWARDED_PROTO');
        if ($protocol) {
            return $this->protocolWithActiveSsl($protocol);
        }

        $protocol = $this->getServerVar('HTTPS');
        if ($protocol) {
            return $this->protocolWithActiveSsl($protocol);
        }

        return '443' === (string) $this->getServerVar('SERVER_PORT');
    }

    /**
     * Detects an active SSL protocol value.
     *
     * @param string $protocol
     *
     * @return bool
     */
    protected function protocolWithActiveSsl(string $protocol): bool
    {
        $protocol = strtolower((string) $protocol);

        return in_array($protocol, ['on', '1', 'https', 'ssl'], true);
    }

    /**
     * Tries to detect the host name of the server.
     *
     * @return string
     */
    protected function getHostName(): string
    {
        // Check for proxy first
        $header = $this->getHeader('X_FORWARDED_HOST');
        if ($header && $this->isValidForwardedHost($header)) {
            $elements = explode(',', $header);
            $host = $elements[count($elements) - 1];
        } elseif (!$host = $this->getHeader('HOST')) {
            if (!$host = $this->getServerVar('SERVER_NAME')) {
                $host = $this->getServerVar('SERVER_ADDR');
            }
        }

        // trim and remove port number from host
        // host is lowercase as per RFC 952/2181
        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));

        // Port number
        $scheme = $this->getHttpScheme();
        $port = $this->getCurrentPort();
        $appendPort = ':'.$port;

        // Don't append port number if a normal port.
        if (('http' == $scheme && '80' == $port) || ('https' == $scheme && '443' == $port)) {
            $appendPort = '';
        }

        return $host.$appendPort;
    }

    protected function getCurrentPort(): string
    {
        // Check for proxy first
        $port = $this->getHeader('X_FORWARDED_PORT');
        if ($port) {
            return (string) $port;
        }

        $protocol = (string) $this->getHeader('X_FORWARDED_PROTO');
        if ('https' === $protocol) {
            return '443';
        }

        return (string) $this->getServerVar('SERVER_PORT');
    }

    /**
     * Returns the a value from the $_SERVER super global.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getServerVar(string $key): string
    {
        return isset($_SERVER[$key]) ? $_SERVER[$key] : '';
    }

    /**
     * Gets a value from the HTTP request headers.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getHeader(string $key): string
    {
        return $this->getServerVar('HTTP_'.$key);
    }

    /**
     * Checks if the value in X_FORWARDED_HOST is a valid hostname
     * Could prevent unintended redirections.
     *
     * @param string $header
     *
     * @return bool
     */
    protected function isValidForwardedHost(string $header): bool
    {
        $elements = explode(',', $header);
        $host = $elements[count($elements) - 1];

        return preg_match('/^([a-z\\d](-*[a-z\\d])*)(\\.([a-z\\d](-*[a-z\\d])*))*$/i', $host) //valid chars check
            && 0 < strlen($host) && strlen($host) < 254 //overall length check
            && preg_match('/^[^\\.]{1,63}(\\.[^\\.]{1,63})*$/', $host); //length of each label
    }
}
