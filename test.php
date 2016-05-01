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
    [
        'StuNo'=>'13232244',
        'StuName'=>'gojkf2',
        'GroupNo'=>'1',
        'ClassNo'=>'1',
        'Sex'=>'2',
        'PhotoPath'=>'kdjkdf',
        'OccupationNo'=>'3',
        'Password'=>'djlfkdjfl'
    ],
    [
        '1331131231','abac',2,2,1,'jalkdjf',2,'jkdsfjlas'
    ],
    [
        '1450404044','dedc',2,3,1,'jalkdj',2,'jkdsfjlas'
    ]
];

echo $db->Insert('StuInfo',$stu,'ssiiisis');
$res = $db->Select_assoc('StuInfo');
echo '<br><pre>';
var_dump($res);
echo '</pre>';