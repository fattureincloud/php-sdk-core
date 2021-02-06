<?php

namespace MadBit\SDK\Url;

class MadBitUrlManipulator
{
    /**
     * Remove params from a URL.
     *
     * @param string $url            the URL to filter
     * @param array  $paramsToFilter the params to filter from the URL
     *
     * @return string the URL with the params removed
     */
    public static function removeParamsFromUrl(string $url, array $paramsToFilter): string
    {
        $parts = parse_url($url);

        $query = '';
        if (isset($parts['query'])) {
            $params = [];
            parse_str($parts['query'], $params);

            // Remove query params
            foreach ($paramsToFilter as $paramName) {
                unset($params[$paramName]);
            }

            if (count($params) > 0) {
                $query = '?'.http_build_query($params, null, '&');
            }
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $host = isset($parts['host']) ? $parts['host'] : '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = isset($parts['path']) ? $parts['path'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $scheme.$host.$port.$path.$query.$fragment;
    }

    /**
     * Gracefully appends params to the URL.
     *
     * @param string $url       the URL that will receive the params
     * @param array  $newParams the params to append to the URL
     *
     * @return string
     */
    public static function appendParamsToUrl(string $url, array $newParams = []): string
    {
        if (empty($newParams)) {
            return $url;
        }

        if (false === strpos($url, '?')) {
            return $url.'?'.http_build_query($newParams, null, '&');
        }

        list($path, $query) = explode('?', $url, 2);
        $existingParams = [];
        parse_str($query, $existingParams);

        // Favor params from the original URL over $newParams
        $newParams = array_merge($newParams, $existingParams);

        // Sort for a predicable order
        ksort($newParams);

        return $path.'?'.http_build_query($newParams, null, '&');
    }

    /**
     * Returns the params from a URL in the form of an array.
     *
     * @param string $url the URL to parse the params from
     *
     * @return array
     */
    public static function getParamsAsArray(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (!$query) {
            return [];
        }
        $params = [];
        parse_str($query, $params);

        return $params;
    }

    /**
     * Adds the params of the first URL to the second URL.
     *
     * Any params that already exist in the second URL will go untouched.
     *
     * @param string $urlToStealFrom the URL harvest the params from
     * @param string $urlToAddTo     the URL that will receive the new params
     *
     * @return string the $urlToAddTo with any new params from $urlToStealFrom
     */
    public static function mergeUrlParams(string $urlToStealFrom, string $urlToAddTo): string
    {
        $newParams = static::getParamsAsArray($urlToStealFrom);
        // Nothing new to add, return as-is
        if (!$newParams) {
            return $urlToAddTo;
        }

        return static::appendParamsToUrl($urlToAddTo, $newParams);
    }

    /**
     * Check for a "/" prefix and prepend it if not exists.
     *
     * @param null|string $string
     *
     * @return null|string
     */
    public static function forceSlashPrefix(string $string): string
    {
        if (!$string) {
            return $string;
        }

        return 0 === strpos($string, '/') ? $string : '/'.$string;
    }
}
