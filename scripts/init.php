<?php
/**
 * Created by PhpStorm.
 * User: creed
 * Date: 24.10.16
 * Time: 2:35
 */

include_once "../src/constants.php";

/* initializes $config */
$config_file_content = file_get_contents('../config.json');
$config = json_decode($config_file_content);

/* connects to db */
$pdo = new PDO($config->mysql->db_uri, $config->mysql->db_user, $config->mysql->db_pass);
$pdo->exec("SET NAMES utf8;");
$pdo->exec("TRUNCATE items;");

$cache = new Memcached();
$cache->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
//$cache->setOption(Memcached::OPT_COMPRESSION, false);
$cache->addServer('localhost', 11211);

function init_db() {
    $N = 100000;
    global $pdo;

    for($q = 0; $q < 10; $q++) {
        $start = microtime(true);

        $query = "INSERT INTO items (name, price) VALUES ";
        for ($i = 0; $i < $N; $i++) {
            $name = 'item' . $i;
            $price = $i + 1;
            if ($i == 0) {
                $query .= "('$name', $price)";
            } else {
                $query .= ",('$name', $price)";
            }
        }
        $pdo->exec($query);

        $time_elapsed_secs = microtime(true) - $start;
        echo 'insertion: ' . $time_elapsed_secs * 1000 . "\n";
    }
}

function init_cache() {
    global $cache;
    global $pdo;

    $start = microtime(true);

    $rows = $pdo
        ->query("SELECT `id`,`price` FROM `items` WHERE 1 ORDER BY `price` ASC;")
        ->fetchAll(PDO::FETCH_OBJ);
    $str = "";
    $len = count($rows);
    for($i = 0; $i < $len; $i++) {
        if ($i != 0) {
            $str .= '|';
        }
        $str .= $rows[$i]->id . '|' . $rows[$i]->price;
    }

    $cache->set(STATE_VERSION, 0);
    $cache->set(STATE.'0', $str);
    print_r($cache->getResultMessage());
    echo "\n";

    $time_elapsed_secs = microtime(true) - $start;
    echo 'init_cache: ' . $time_elapsed_secs * 1000 . "\n";
}

function init_oplog() {
    global $cache;
    $cache->set(OPLOG, '');
}

init_db();

init_cache();

init_oplog();