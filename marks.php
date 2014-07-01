<?php
/**
 * MongoDB CONFIGURATION
 */
$mongoHost = 'localhost';
$mongoUser = 'admin';
$mongoPasswd = 'qwerty';
$mongoDb = 'road_dev';
$mongoPrefix = '';

/**
 * CONNECT TO MONGO
 *
 * @param String $mongoHost
 * @param String $mongoUser
 * @param String $mongoPasswd
 * @param String $mongoDb
 *
 * @return MongoDB
 */
function connect_mongo($mongoHost, $mongoUser, $mongoPasswd, $mongoDb) {
    $mongo = new Mongo('mongodb://' . $mongoUser . ':' . $mongoPasswd . '@' . $mongoHost, array('db' => $mongoDb));

    return new MongoDB($mongo, $mongoDb);
}

$mongoDB = connect_mongo($mongoHost, $mongoUser, $mongoPasswd, $mongoDb);

$routesCol = $mongoDB->selectCollection('routes');
$marksCol = $mongoDB->selectCollection('marks');
$sectorCol = $mongoDB->selectCollection('sector');

$routesList = $routesCol->find(array('gone' => array('$exists' => false)));

function beetwen($x1, $y1, $x2, $y2, $xd, $yd){
    $res = 0;
    if (($x1 > $xd && $xd > $x2) || ($x1 < $xd && $xd < $x2)) {
        $res++;
    }

    if (($y1 > $yd && $yd > $y2) || ($y1 < $yd && $yd < $y2)) {
        $res++;
    }

    return $res;
}

function updateSector($sector, $route_id)
{
    global $sectorCol;
    $dbSector = $sectorCol->findOne(array('_id' => $sector['_id']));

    if ($dbSector) {
        $pitCount = 1;
        if ($dbSector['pit_cnt']) {
            $pitCount = $dbSector['pit_cnt'];
        }

        $routes = [];
        if ($dbSector['route']) {
            $routes = $dbSector['route'];
        }

        if (!in_array($route_id, $routes)) {
            $routes[] = $route_id;
        }

        //$sectorCol->insert(array('_id' => $dbSector['_id']), array('pit_cnt' => $pitCount, 'route' => $routes));
        //$sectorCol->update(array('_id' => $dbSector['_id']), array('$set' => array('pit_cnt' => $pitCount, 'route' => $routes)));
    }
}


while ($route = $routesList->getNext()) {
    $marksList = $marksCol->find(array('r' => $route['_id'], 'p' => array( '$gt' => $route['pit_avg'] * 1.5)));
    $marksCount = 0;
    while ($mark = $marksList->getNext()) {
        $marksCount++;
        echo('|');
        $startSector = $sectorCol->findOne(array('start' => array('$near' => [$mark['g']['x'], $mark['g']['y']])));
        $endSector = $sectorCol->findOne(array('end' => array('$near' => [$mark['g']['x'], $mark['g']['y']])));

        if (!$startSector && !$endSector) {
            continue;
        }

        if ($endSector['_id']->__toString() == $startSector['_id']->__toString()) {
            updateSector($startSector, $route['_id']);
        } else {
            $res1 = beetwen($startSector['start']['x'],
                $startSector['start']['y'],
                $startSector['end']['x'],
                $startSector['end']['y'],
                $mark['g']['x'],
                $mark['g']['y']);

            $res2 = beetwen($endSector['start']['x'],
                $endSector['start']['y'],
                $endSector['end']['x'],
                $endSector['end']['y'],
                $mark['g']['x'],
                $mark['g']['y']);


            updateSector($res1 >= $res2 ? $startSector : $endSector, $route['_id']);
        }
    }

    echo $route['_id'] . " [{$marksCount}] " . date('H:i:s') . PHP_EOL;
    //$routesCol->update(array('_id' => $route['_id']), array('$set' => array('gone' => true)));
}


/**
 * IMG MIGRATION
 */

/**
 * Prepare array of new category Ids
 */
$cursor = $catMongo->find();
//$converter = array();
//foreach ($cursor as $obj) {
//   if (isset($obj['oldId'])) {
//       $converter[$obj['oldId']] = $obj['_id'];
//   }
//}
//$cursor = $pcatMongo->find();
//foreach ($cursor as $obj) {
//    if (isset($obj['oldId'])) {
//        $converter[$obj['oldId']] = $obj['newId'];
//    }
//}
//
///**
// * Prepare array of tags Id
// */
//$cursor = $tagMongo->find();
//$converterTag = array();
//foreach ($cursor as $obj) {
//    if (isset($obj['name'])) {
//        $converterTag[$obj['name']] = $obj['_id'];
//    }
//}

