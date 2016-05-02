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
* [ ] 支持 PDO
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

# * 待续
