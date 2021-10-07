<?php
/**
 * @author		Matthew Wang
 * @license		https://opensource.org/licenses/GPL-3.0
 * @Email		asakous@gmail.com
*/
namespace Session;
final class REDIS {
	public $expire = '';
	
	public function __construct($registry) {
		$this->expire = '3600';
        $this->cache = new \Redis();
        $this->cache->pconnect(CACHE_HOSTNAME, CACHE_PORT);

	}
	
	public function read($session_id) {
		return unserialize($this->cache->get(CACHE_PREFIX . $session_id));
	}
	
	public function write($session_id, $data) {
		
		$status = $this->cache->set(CACHE_PREFIX . $session_id, serialize ($data));
        if($status){
            @$this->cache->setTimeout(CACHE_PREFIX . $session_id, $this->expire);
        }
	
		return true;
	}
	
	public function destroy($session_id) {
		$this->cache->delete(CACHE_PREFIX . $session_id);
		return true;
	}
	
	public function gc($expire) {
		$this->cache->delete(CACHE_PREFIX . $expire);
		return true;
	}
}
