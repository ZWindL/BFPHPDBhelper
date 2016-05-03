# Strong and Easy DBHelper
****

## Overview
* Insert / Update data easily
* Transfer result set and parameters as array
* Create T-SQL string automatically
* Based on mysqli
  * Will support PDO class in feature version
* Support prepare statement

### TODO
* [x] Support prepare
* [ ] 'Where' clause also support prepare，but it seems not very importantly
* [ ] Support PDO (That should blame the devel PC which in my school，php version is too old that still not support PDO :confused: I'm angry :angry:)

----

Direction
====
Initialize
----

The construct function of `DBHelper` Class' argument list is form as below
```php
<?php
public function __construct($dbinfo_json_file=null, $dbinfo=null, $charset='utf8', $dbname=null);
```

It receives one string (`$dbinfo_json_file`),or a assoc array(`$dbinfo`) as its base config info.

* String is `your_config_file_name.json` path(include json file), format like below
```json
{
  "hostname":"your_host_name_such_as_localhost",
  "username":"your_username",
  "password":"your_password",
  "database":"database_name"
}
```
* The array's format is same as the result that json decoded
```php
<?php
$arrayName = array(
  'hostname' => 'your_host_name_such_as_localhost',
  'username' => 'your_username',
  'password' => 'your_password',
  'database' => 'database_name'
);
?>
```
The `database` is a optional field，you can use argument `$dbname` in construct function to specify the database's name

Function list
----
| Function name		| Visibility   | Argument list  | Type of return value | Return variable  |
| :-------------  | :-----: | :-------------     | :-------: | :-----:     |
| Change_db       | public  | $dbname(string)     | bool      |  Chang succesful or not |
| Prepared_query_complex|public|$sql(string)<br>$typeDef(string/bool)=flase<br>$pparams(array/bool)=false|bool/array|Query result(assoc array)，return flase when query failed|
|[Select](#select)|public|$table_name(string)<br>$search_field(array)=null<br>$const(array)=null<br>$filter_str(string)=null|mysqli_result|mysqli_result|
|[Select_assoc](#select_assoc)|public|same as above|array|Result set which stored as assoc array|
|Query_complex|public|$query_str(string)|mysqli_result|The result which is from Queried $query_str directly|
|Query_complex_assoc|public|Same as above|array|The assoc array which is query $query_str directly|
|[Insert](#insert)|public|$table(string)<br>$value_arr(array)<br>$value_type_str(string)<br>$prepare(bool)=true|integer|Affected rows' count|
|Update|public|$table(string)<br>$value_arr(array)<br>$value_type_str(string)<br>$const(array)=null<br>$filter_str(string)=null<br>$prepare(bool)=true|integer|Affected rows' count|
|Delete|public|$table(string)<br>$const(array)=null<br>$filter_str(string)=null|integer|Affected rows' count|

Examples
----
#### Select
```php
<?php
$db = new DBHelper('path_to_your_json_file');

$table_name = 'test';
// $const is the WHERE clause formated as assoc array
// The arary below will be translated to "WHERE `name`='abc' and `age`='12'"
// If a key's value is null will be translated to `KEY` 'is null'
$const = array(
  'name' => 'abc',
  'age' => 12,
);
$res = $db->Select($table, $const);
// If just give a $table , the function will return the result that is queried 'SELECT * FROM `$table`
?>
```
----

#### Select_assoc

It's same as functoin Select but it returns a assoc array, you can get value with that way `$res[index]['key_name']`

----
#### Insert

```php
<?php
$data = array(
  'name' => 'abc',
  'age' => 12,
  'stu_num' => '10000000';
);
$db->Insert($table, $data, 'sis');
// 'INSERT INTO $table(`name`,`age`,`stu_num`) VALUES('abc', 12, '10000000')'
// 'sis' is the type of fileds that you want insert (string int string)
// NOTE: If you want use prepare statment you must set that argument $value_type_str

$data_without_key = array(
  'abc',12,'10000'
);
$db->Insert($table, $data, 'sis');
// 'INSERT INTO $table VALUES('abc', 12, '10000000')'

$data_multi = array(
  array(
    'name' => 'abc',
    'age' => 12
  ),
  array('cde', 17),
  array('fde', 19)
)
$db->Insert($table, $data_multi, 'si');
// 'INSERT INTO $table(`name`,`age`) VALUES('abc', 12),
//    ('cde',17),
//    ('fde',19)'

$data_multi_without_key = array(
  array('abc', 12),
  array('cde', 17),
  array('fde', 19)
);
$db->Insert($table, $data_multi_without_key, 'si');
// 'INSERT INTO $table VALUES('abc', 12),
//    ('cde',17),
//    ('fde',19)'

?>
```

----
#### Update
----
#### Delete
