<?php
/**
 * Created by PhpStorm.
 * User: creed
 * Date: 20.10.16
 * Time: 18:00
 */

include_once "src/main.php";

/* initializes $config */
$config_file_content = file_get_contents('./config.json');
$config = json_decode($config_file_content);

/* connects to db */
$pdo = new PDO($config->mysql->db_uri, $config->mysql->db_user, $config->mysql->db_pass);
$pdo->exec("SET NAMES utf8;");

$cache = new Memcached();
$cache->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
//$cache->setOption(Memcached::OPT_COMPRESSION, false);
$cache->addServer('localhost', 11211);

$state = [];
$oplog = [];
//$cache->set(OPLOG, '');

$item = (object)[
    "price" => 1.99,
    "description" => "It's a new description",
    "name" => "Lolkaaaaa"
];

$first_page = items_get(0, 30);
//print_r($first_page);

//items_update($first_page[0]->id, $item);
//items_remove($first_page[1]->id);
//items_remove($first_page[3]->id);
//items_remove($first_page[5]->id);
//items_remove($first_page[7]->id);
//items_remove($first_page[9]->id);

//$start = microtime(true);
//
//for($i = 1000; $i < 2000; $i++) {
//    items_add((object)[
//        "price" => 0.5 + ($i * 1.0) / 1000,
//        "name" => "Inserted$i",
//        "description" => "Empty description$i"
//    ]);
//}
//
//$time_elapsed_secs = microtime(true) - $start;
//echo 'insertion: ' . $time_elapsed_secs * 1000 . "\n";