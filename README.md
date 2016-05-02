# 强壮又方便的 DBHelper
****

## 功能概述
* 简单方便地插入 / 修改数据
* 以数组的形式传递参数和结果集
* 自动构建 sql string
* 基于 mysqli
  * 未来版本将支持 PDO 类
* 支持 prepare

### TODO
* [x] 支持 prepare
* [ ] where 子句也支持 prepare，不过好像没什么必要
* [ ] 支持 PDO (必须要甩锅给学校的开发机，php 版本老得可怕，还不支持 PDO :confused: I'm angry :angry:)

----

使用说明
====
初始化
----

`DBHelper` 类的构造函数参数列表如下
```php
<?php
public function __construct($dbinfo_json_file=null, $dbinfo=null, $charset='utf8', $dbname=null);
```

接收 1 个字符串(`$dbinfo_json_file`),或者一个数组(`$dbinfo`)作为基本配置信息.

* 字符串是 `your_config_file_name.json` 所在的相对目录名称(包含 json 文件), 文件格式如下
```json
{
  "hostname":"your_host_name_such_as_localhost",
  "username":"your_username",
  "password":"your_password",
  "database":"database_name"
}
```
* 数组与 json 文件解析出来的格式一致，结构如下
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
其中，`database` 是可选字段，可以使用构造函数中的 `$dbname` 参数来指定

方法列表
----
| 方法名称         | 访问性   | 参数列表             | 返回值类型 | 返回值       |
| :-------------  | :-----: | :-------------     | :-------: | :-----:     |
| Change_db       | public  | $dbname(string)     | bool      |  是否切换成功 |
| Prepared_query_complex|public|$sql(string)<br>$typeDef(string/bool)=flase<br>$pparams(array/bool)=false|bool/array|查询结果(关系型数组)，失败返回flase|
|Select|public|$table_name(string)<br>$search_field(array)=null<br>$const(array)=null<br>$filter_str(string)=null|mysqli_result|mysqli_result结果集|
|Select_assoc|public|同上|array|以关系型数组形式存储的结果集|
|Query_complex|public|$query_str(string)|mysqli_result|直接查询 $query_str 得到的结果集|
|Query_complex_assoc|public|同上|array|直接查询 $query_str 得到的关系型数组|
|Insert|public|$table(string)<br>$value_arr(array)<br>$value_type_str(string)<br>$prepare(bool)=true|integer|受影响的行数|
|Update|public|$table(string)<br>$value_arr(array)<br>$value_type_str(string)<br>$const(array)=null<br>$filter_str(string)=null<br>$prepare(bool)=true|integer|受影响的行数|
|Delete|public|$table(string)<br>$const(array)=null<br>$filter_str(string)=null|integer|受影响的行数|

示例
----
## 待续
