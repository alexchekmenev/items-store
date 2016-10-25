<?php
/**
 * Created by PhpStorm.
 * User: creed
 * Date: 24.10.16
 * Time: 4:11
 */

include_once "constants.php";
include_once "locks.php";

function get_oplog(&$oplog_version) {
    global $oplog;
    global $cache;

    take_lock(OPLOG_LOCK);
        $str = $cache->get(OPLOG);
    release_lock(OPLOG_LOCK);

    $oplog = (strlen($str) == 0 ? [] : explode('|', $str));
    $len = count($oplog);
    for($i = 0; $i < $len; $i += 4) {
        if ($oplog_version < $oplog[$i + 2]) {
            $oplog_version = $oplog[$i + 2];
        }
    }

    return $oplog;
}

function add_to_oplog($op) {
    global $cache;

    $start = microtime(true);

    take_lock(OPLOG_LOCK);
        $value = $cache->get(OPLOG);
        $value .= (strlen($value) != 0 ? '|' : '')
            . $op->id . '|'
            . $op->price . '|'
            . $op->t . '|'
            . $op->action;
        $cache->set(OPLOG, $value);
    release_lock(OPLOG_LOCK);

//    $time_elapsed_secs = microtime(true) - $start;
//    echo 'add_to_oplog: ' . $time_elapsed_secs * 1000 . "\n";
}