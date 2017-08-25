<?php
/**
 * Created by PhpStorm.
 * User: creed
 * Date: 24.10.16
 * Time: 4:35
 */

include_once "constants.php";

function get_state() {
    global $cache;
    global $state;

    $start = microtime(true);
    //$cache->set(STATE_VERSION, 0);

//    take_lock(STATE_VERSION_LOCK);
//        $state_version = $cache->get(STATE_VERSION);
//    release_lock(STATE_VERSION_LOCK);

    $state_version = 0;

    //echo "state_version = $state_version\n";

    $str = $cache->get(STATE.$state_version);
    if (strlen($str) == 0) {
        $state = [];
    } else {
        $state = explode('|', $str);
    }

    $time_elapsed_secs = microtime(true) - $start;
    echo '  get_state: ' . $time_elapsed_secs * 1000 . "\n";
}

function save_state($state_version, &$new_state) {
    global $cache;

    $start = microtime(true);

    $n = count($new_state);
    $str = "";
    for($i = 0; $i < $n; $i++) {
        if ($i != 0) {
            $str .= '|';
        }
        $str .= $new_state[$i];
    }

//    echo "length = " . strlen($str) . "\n";

    $cache->set(STATE.$state_version, $str, 2*60);

    $time_elapsed_secs = microtime(true) - $start;
    echo '  save_state: ' . $time_elapsed_secs * 1000 . "\n";
}