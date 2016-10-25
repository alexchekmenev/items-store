<pre>
<?php
/**
 * Created by PhpStorm.
 * User: creed
 * Date: 24.10.16
 * Time: 20:45
 */

include_once "oplog.php";
include_once "state.php";

/* Main methods for mapping with REST API */

function items_get($offset, $count) {
    global $state;
    global $oplog;

    $start = microtime(true);

    get_state();
    echo "state size = " . count($state) . "\n";

    $oplog_version = 0;
    get_oplog($oplog_version);
    echo "oplog size = " . count($oplog) . "\n";
    echo "oplog version = " . $oplog_version . "\n";

    $ids = [];
    if (count($oplog) > 0) {
        $new_state = &_merge_state_and_oplog();
        //save_state(1, $new_state);
        for($i = $offset * 2; $i < ($offset + $count) * 2; $i += 2) {
            $ids[] = $new_state[$i];
        }
    } else {
        for($i = $offset * 2; $i < ($offset + $count) * 2; $i += 2) {
            echo "$i: $state[$i]\n";
            $ids[] = $state[$i];
        }
    }

    print_r($ids);

    $response = _get_items_by_ids($ids);

    //print_r($ids);

    $time_elapsed_secs = microtime(true) - $start;
    echo '<- get_items: ' . $time_elapsed_secs * 1000 . "\n";

    return $response;
}

function items_update($id, $item) {
    $start = microtime(true);

    $item->id = $id;
    //$old_item = _update_item_in_db($item);

    //if ($old_item->price != $item->price) {
        add_to_oplog((object)[
            "id" => $id,
            "price" => $item->price,
            "t" => intval($start * 1000),
            "action" => "update"
        ]);
    //}

    $time_elapsed_secs = microtime(true) - $start;
    //echo '<- update_items: ' . $time_elapsed_secs * 1000 . "\n";
}

function items_add($item) {
    $start = microtime(true);

    _add_item_to_db($item);

    add_to_oplog((object)[
        "id" => $item->id,
        "price" => $item->price,
        "t" => intval($start * 1000),
        "action" => "add"
    ]);

    $time_elapsed_secs = microtime(true) - $start;
    //echo '<- add_items: ' . $time_elapsed_secs * 1000 . "\n";
}

function items_remove($id) {
    $start = microtime(true);

    _remove_item_from_db($id);

    add_to_oplog((object)[
        "id" => $id,
        "price" => 0,
        "t" => intval($start * 1000),
        "action" => "remove"
    ]);

    $time_elapsed_secs = microtime(true) - $start;
    echo '<- remove_items: ' . $time_elapsed_secs * 1000 . "\n";
}

/* Other methods */

function _get_items_by_ids($ids) {
    global $pdo;

    $start = microtime(true);

    $stmt = $pdo->query("SELECT * FROM items WHERE id IN (".implode(',', $ids).") ORDER BY `price` ASC, `created` ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

    $time_elapsed_secs = microtime(true) - $start;
    echo '  _get_items_by_ids: ' . $time_elapsed_secs * 1000 . "\n";

    return $rows;
}

function &_merge_state_and_oplog() {
    global $state;
    global $oplog;

    $start = microtime(true);

    $oplog_len = count($oplog);

    /* constructing array */
    $temp = [];
    for($i = 0; $i < $oplog_len; $i += 4) {
        $temp[] = (object)[
            "id" => $oplog[$i],
            "price" => $oplog[$i + 1],
            "t" => $oplog[$i + 2],
            "action" => $oplog[$i + 3],
        ];
    }

    /* make unique id's */
    usort($temp, "_cmp1");
    $unique = [];
    $temp_len = count($temp);
    for($i = 0; $i < $temp_len; ) {
        $ptr = $i + 1;
        $unique[] = $temp[$i];
        while($ptr < $temp_len && $temp[$i]->id == $temp[$ptr]->id) {
            $ptr++;
        }
        $i = $ptr;
    }
    //$temp = $unique;

    /* order by price */
    usort($unique, "_cmp2");

    /* create map */

    $state_len = count($state);
    $unique_len = count($unique);
    $new_state_len = $state_len;
    $map = [];
    for($i = 0; $i < $unique_len; $i++) {
        $map[$unique[$i]->id] = true;
        if ($unique[$i]->action == 'add') {
            $new_state_len += 2;
        }
        if ($unique[$i]->action == 'remove') {
            $new_state_len -= 2;
        }
        //$new_state_len += 2;
    }

    $unique_light = [];
    $count = 0;
    for($i = 0; $i < $unique_len; $i++) {
        if ($unique[$i]->action != 'remove') {
            $count += 2;
            $unique_light[] = $unique[$i]->id;
            $unique_light[] = $unique[$i]->price;
        }
    }

//    $count = 0;
//    $j = 0;
//    for($i = 0; $i < $state_len; $i += 2) {
//        if ($j >= $unique_len) {
//            $count += 2;
//            continue;
//        }
//        $t = 1;
//        $v = 1;
//        if ($v <= $t && $v >= $t) {
//            //$new_state[$pos] = $state[$i];
//            //$new_state[$pos + 1] = $state[$i + 1];
//            $count += 1;
//            $count += 1;
//            //echo "from state\n";
//        } else {
//            //$new_state[$pos] = $unique[$j]->id;
//            //$new_state[$pos + 1] = $unique[$j]->price;
//            $count += 2;
//            $count += 2;
//            //echo "from oplog\n";
//        }
//    }

    $time_elapsed_secs = microtime(true) - $start;
    echo '  _count: ' . $time_elapsed_secs * 1000 . "\n";

    $pos = 0;
    $new_state = new SplFixedArray($new_state_len);

    $start = microtime(true);
    $j = 0;
    for($i = 0; $i + 1 < $state_len; ) {
//        if ($pos % 200000 == 0 && $pos != 0) {
//            $time_elapsed_secs = microtime(true) - $start;
//            echo '  _iter: ' . $time_elapsed_secs * 1000 . "\n";
//            $start = microtime(true);
//        }
//        if ($i < 100 && $j < 100) {
//            echo "i: $i, j: $j\n";
//        } else {
//            break;
//        }
        if (isset($map[$state[$i]])) {
            $i += 2;
            //echo "in map\n";
            continue;
        } else if ($j >= $unique_len || $state[$i + 1] <= $unique_light[$j + 1]) {
            $new_state[$pos] = $state[$i];
            $new_state[$pos + 1] = $state[$i + 1];
            $i += 2;
            $pos += 2;
            //echo "from state\n";
        } else {
            $new_state[$pos] = $unique_light[$j];
            $new_state[$pos + 1] = $unique_light[$j + 1];
            $j += 2;
            $pos += 2;
            //echo "from oplog\n";
        }

//        if ($i + 1 < $state_len) {
//
//        } else {
//            $new_state[$pos] = $unique_light[$j];
//            $new_state[$pos + 1] = $unique_light[$j + 1];
//            $j += 2;
//            $pos += 2;
//            //echo "from oplog\n";
//        }

//        if ($j < $unique_len && $unique[$j]->action == "remove") {
//            $j++;
//            //echo "removed\n";
//            continue;
//        }
//        if ($i + 1 < $state_len && ()) { // $unique[$j]->price) {
//
//        } else {
//            $new_state[] = $unique_light[$j];
//            $new_state[] = $unique_light[$j + 1];
//            $j += 2;
//            //$pos += 2;
//            //echo "from oplog\n";
//        }

    }

    while($j < $count) {
        $new_state[$pos] = $unique_light[$j];
        $new_state[$pos + 1] = $unique_light[$j + 1];
        $j += 2;
        $pos += 2;
        //echo "from oplog\n";
    }

    echo "new_state_len = $new_state_len, pos = $pos\n";

    //echo "oplog: \n";
    //print_r($oplog);

    //echo "map: \n";
    //print_r($map);

    //echo "\nunique: \n";
    //print_r($unique);

    $time_elapsed_secs = microtime(true) - $start;
    echo '  _merge_state_and_oplog: ' . $time_elapsed_secs * 1000 . "\n";

    return $new_state;
}
function _cmp1($a, $b) {
    if ($a->id < $b->id) {
        return -1;
    } else if ($a->id > $b->id) {
        return 1;
    } else {
        if ($a->t > $b->t) {
            return -1;
        } else {
            return 1;
        }
    }
}

function _cmp2($a, $b) {
    if ($a->price < $b->price) {
        return -1;
    } else if ($a->price > $b->price) {
        return 1;
    } else {
        if ($a->t < $b->t) {
            return -1;
        } else {
            return 1;
        }
    }
}

function contains(&$arr, $id) {
    $l = 0;
    $r = count($arr);
    while($l < $r) {
        $m = ($l + $r) / 2;
        if ($arr[$m]->id < $id) {
            $l = $m + 1;
        } else {
            $r = $m;
        }
    }
    return ($l < count($arr) && $arr[$l]->id == $id);
}

function _update_item_in_db(&$item) {
    global $pdo;

    $query1 = "SELECT * FROM `items` WHERE id=$item->id;";

    $query2 = "UPDATE `items` SET ";
    $query2_has_columns = false;
    if (property_exists($item, 'name')) {
        if ($query2_has_columns) {
            $query2 .= ',';
        }
        $query2 .= "name='".addslashes($item->name)."'";
        $query2_has_columns = true;
    }
    if (property_exists($item, 'price')) {
        if ($query2_has_columns) {
            $query2 .= ',';
        }
        $query2 .= "price=$item->price";
        $query2_has_columns = true;
    }
    if (property_exists($item, 'description')) {
        if ($query2_has_columns) {
            $query2 .= ',';
        }
        $query2 .= "description='".addslashes($item->description)."'";
        $query2_has_columns = true;
    }

    $query = $query1 . ($query2_has_columns ? $query2 . " WHERE id=$item->id;" : '');
    //echo "query = " . $query . "\n";

    $response = $pdo->query($query)->fetch(PDO::FETCH_OBJ);
    if ($pdo->errorCode() != PDO::ERR_NONE) {
        throw new Exception($pdo->errorInfo());
    }
    return $response;
}

function _add_item_to_db(&$item) {
    global $pdo;
    $query = "INSERT INTO `items` (`name`, `price`, `description`) "
        . "VALUES ('$item->name', $item->price, '$item->description')";
    //echo $query . "\n";
    $pdo->exec($query);
    $item->id = $pdo->lastInsertId();
}

function _remove_item_from_db($id) {
    global $pdo;
    $query = "DELETE FROM `items` WHERE id=$id;";
    echo $query . "\n";
    $pdo->exec($query);
}
?>
    </pre>
