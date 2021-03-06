<?php
/**
 * Kima Cache Void
 * @author Steve Vega
 */
namespace Kima\Cache;

/**
 * Implementation of Null Object design pattern for Kima Cache
 */
class Void implements ICache
{

    /**
     * Construct
     * @param array $options the config options
     */
    public function __construct(array $options = []) {}

    /**
     * Gets a cache key
     * @param  string $key the cache key
     * @return mixed
     */
    public function get($key) {}

    /**
     * Gets a cache key using the file last mofication
     * as reference instead of the cache expiration
     * @param  string $key       the cache key
     * @param  string $file_path the file path
     * @return mixed
     */
    public function get_by_file($key, $file_path) {}

    /**
     * Gets the timestamp of a cache key
     * @param  string $key the cache key
     * @return mixed
     */
    public function get_timestamp($key) {}

    /**
     * Sets the cache key
     * @param  string  $key        the cache key
     * @param  mixed   $value
     * @param  time    $expiration
     * @return boolean
     */
    public function set($key, $value, $expiration = 0) {}

}
