<?php
/**
 * Created by PhpStorm.
 * User: creed
 * Date: 20.10.16
 * Time: 18:00
 */

$start = microtime(true);

/* initializes $config */
$config_file_content = file_get_contents('./config.json');
$config = json_decode($config_file_content);

/* connects to db */
$pdo = new PDO($config->mysql->db_uri, $config->mysql->db_user, $config->mysql->db_pass);
$statement = $pdo->query("SHOW TABLES;");
$row = $statement->fetchAll(PDO::FETCH_ASSOC);

$time_elapsed_secs = microtime(true) - $start;

echo 'elapsed: ' . $time_elapsed_secs . "\n";