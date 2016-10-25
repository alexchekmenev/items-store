<?php
/**
 * Created by PhpStorm.
 * User: creed
 * Date: 24.10.16
 * Time: 19:10
 */

define('TAKE_LOCK_TIMEOUT', 30); // in ms

function take_lock($lock_key) {
    global $cache;
    $start = microtime(true) * 1000;
    do {
        $time_elapsed = microtime(true) - $start;
        if (intval($time_elapsed) > TAKE_LOCK_TIMEOUT) {
            throw new Exception("Timed out while taking lock on '$lock_key'");
        }

        $cas_token = 0.0;
        $value = $cache->get($lock_key, null, $cas_token);
        if ($cache->getResultCode() == Memcached::RES_NOTFOUND) {
            $cache->add($lock_key, 1);
        } else {
            $cache->cas($cas_token, $lock_key, 1);
        }
    } while ($cache->getResultCode() != Memcached::RES_SUCCESS);

//    echo "TAKE_LOCK: ";
//    echo "$lock_key = " . $cache->get($lock_key) . "\n";
}

function release_lock($lock_key) {
    global $cache;
    if (($value = $cache->get($lock_key)) != 1) {
        throw new Exception("Wrong value of '$lock_key' is $value but should be 1");
    }
    if (!$cache->set($lock_key, 0)) {
        throw new Exception('['.$cache->getResultCode().']: '.$cache->getResultMessage());
    }

//    echo "RELEASE_LOCK: ";
//    echo "$lock_key = " . $cache->get($lock_key) . "\n";

}