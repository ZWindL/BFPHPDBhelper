<?php
/**
 * Created by PhpStorm.
 * User: zwindl
 * Date: 16-5-1
 * Time: 上午12:20
 */
require_once 'DBHelper.class.php';

$db = new DBHelper('./db_config.json');

$stu = [
    'StuNo'=>'1435050490',
    'StuName'=>'god2',
    'GroupNo'=>'1',
    'ClassNo'=>'1',
    'Sex'=>'2',
    'PhotoPath'=>'kdjflkdf',
    'OccupationNo'=>'3',
    'Password'=>'djlfkdjfl'
];
$db->Insert('StuInfo',$stu,'ssiiisis');
$res = $db->Select_assoc('StuInfo');
echo '<pre>';
var_dump($res);
echo '</pre>';