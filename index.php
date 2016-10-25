<?php
/**
 * Created by PhpStorm.
 * User: creed
 * Date: 20.10.16
 * Time: 18:00
 */

$start = microtime(true);

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

$time_elapsed_secs = microtime(true) - $start;
echo 'total: ' . $time_elapsed_secs * 1000 . "\n";

$state = [];
$oplog = [];
//$cache->set(OPLOG, '');

$item = (object)[
    "price" => random_int(1, 1000000),
    "description" => "It's a new description",
    "name" => "Lolkaaaaa"
];



//print_r($_GET);

$first_page = items_get(0, 30);
print_r($first_page);

//items_update(1, $item);
//items_remove($first_page[1]->id);
//items_remove($first_page[3]->id);
//items_remove($first_page[5]->id);
//items_remove($first_page[7]->id);
//items_remove($first_page[9]->id);



//for($i = 0; $i < 1001; $i++) {
//    items_update(1, $item);
//    items_add((object)[
//        "price" => 1000.5 + ($i * 1.0) / 1000,
//        "name" => "Inserted$i",
//        "description" => "Empty description$i"
//    ]);
//}


$time_elapsed_secs = microtime(true) - $start;
echo 'total: ' . $time_elapsed_secs * 1000 . "\n";