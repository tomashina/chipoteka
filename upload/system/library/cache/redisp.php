<?php
namespace Cache;

class Redisp
{

    private $expire;
    private $cache;

    public function __construct($expire)
    {
        if (! extension_loaded('redis')) {
            die('The server does not support redis extension!');
        }

        $this->expire = $expire;

        $this->cache = new \Redis();
        $this->cache->pconnect(CACHE_HOSTNAME, CACHE_PORT);

        if (! empty(CACHE_PASSWORD)) {
            if (! $this->cache->auth(CACHE_PASSWORD)) {
                die('redis: wrong password！');
            }
        }
    }

    public function get($key)
    {
        $data = $this->cache->get(CACHE_PREFIX . $key);
        return json_decode($data, true);
    }

    public function set($key, $value)
    {
        $status = $this->cache->set(CACHE_PREFIX . $key, json_encode($value));
        if ($status) {
            $this->cache->expire(CACHE_PREFIX . $key, $this->expire);
        }
        return $status;
    }

    public function delete($key)
    {
        $this->cache->del(CACHE_PREFIX . $key);
    }
}