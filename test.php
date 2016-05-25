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
        'no'=>'13232244',
        'name'=>'gojkf2',
    ],
    [
        '1331131231','abac'
    ],
    [
        '1450404044','dedc'
    ]
];

echo $db->Insert('Students',$stu,'ss');
$res = $db->Select_assoc('Students');
echo '<br><pre>';
var_dump($res);
echo '</pre>';
