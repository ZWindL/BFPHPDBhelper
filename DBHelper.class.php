<?php

/**
 * Class DBHelper
 */
class DBHelper {
    /**
     * @var mysqli mysqli|null mysqli object
     */
	private $mysqli = NULL;
    /**
     * @var string null|string 保存数据库信息的 json 文件
     * 格式如下
     * {
     *      "hostname":"your_host_name",
     *      "username":"your_mysql_username",
     *      "password":"your_mysqli_password",
     *      "database":"optional database name, you also can use method Change_db to choose database.
     *                                          or just give parameter $dbname a value;
     * }
     */
    private $dbinfo_json_file;

	private function throw_exception($exception_str) {
		throw new Exception($exception_str);
	}

    /**
     * DBHelper constructor.
     * @param string $dbinfo_json_file
     * @param array $dbinfo array
     * @param string $charset
     * @param string $dbname database name
     * @throws Exception
     */
	function __construct($dbinfo_json_file=null, $dbinfo=null, $charset='utf8', $dbname=null){
		//保存连接信息
		$config_info = null;
		if (isset($dbinfo)){
			$config_info = $dbinfo;
		} elseif (isset($dbinfo_json_file)) {
			if(!file_exists($dbinfo_json_file)) {
				$this->throw_exception('dbconfig file not found');
				return null;
			}
			$json_origin = file_get_contents($dbinfo_json_file);
			$config_info = json_decode($json_origin, true);
			/*if(json_last_error() != JSON_ERROR_NONE) {
				$this->throw_exception('decode config file error');
				return null;
			}*/
		} else {
			$this->throw_exception('lack dbinfo');
		}
		try {
			if (isset($dbname)) {
				$this->mysqli = new mysqli(
					$config_info['hostname'],
					$config_info['username'],
					$config_info['password'],
					$dbname
				);
			} else {
				$this->mysqli = new mysqli(
					$config_info['hostname'],
					$config_info['username'],
					$config_info['password'],
					$config_info['database']
				);
			}
			if(!$this->mysqli->set_charset($charset)) {
				$this->throw_exception('Error on set charset');
			}
		} catch (Exception $e) {
			throw $e;
		}
		if(mysqli_connect_errno()) {
			$this->throw_exception('connect_error'.$this->mysqli->connect_errno.$this->mysqli->connect_error);
			return null;
		}
        $this->dbinfo_json_file = $dbinfo_json_file;
    }
	function __destruct(){
		if (isset($this->mysqli))
			$this->mysqli->close();
	}

    /**
     * 切换数据库
     * @param $dbname
     * @return bool
     * @throws Exception
     */
    function Change_db($dbname){
        try {
            return $this->mysqli->select_db($dbname);
        } catch (Exception $e) {
            throw $e;
        }
    }

	// 来自 php manual 的一个方法
	public function Prepared_query_complex($sql,$typeDef=false,$params=false){
		if($stmt = $this->mysqli->prepare($sql)){
			if(count($params) == count($params,1)){
				$params = array($params);
				$multiQuery = FALSE;
			} else {
				$multiQuery = TRUE;
			}

			if($typeDef){
				$bindParams = array();
				$bindParamsReferences = array();
				$bindParams = array_pad($bindParams,(count($params,1)-count($params))/count($params),"");
				foreach($bindParams as $key => $value){
					$bindParamsReferences[$key] = &$bindParams[$key];
				}
				array_unshift($bindParamsReferences,$typeDef);
				$bindParamsMethod = new ReflectionMethod('mysqli_stmt', 'bind_param');
				$bindParamsMethod->invokeArgs($stmt,$bindParamsReferences);
			}

			$result = array();
			foreach($params as $queryKey => $query){
				foreach($bindParams as $paramKey => $value){
					$bindParams[$paramKey] = $query[$paramKey];
				}
				$queryResult = array();
				if($stmt->execute()){
					$resultMetaData = $stmt->result_metadata();
					if($resultMetaData){
						$stmtRow = array();
						$rowReferences = array();
						while ($field = $resultMetaData->fetch_field()) {
							$rowReferences[] = &$stmtRow[$field->name];
						}
						mysqli_free_result($resultMetaData);
						$bindResultMethod = new ReflectionMethod('mysqli_stmt', 'bind_result');
						$bindResultMethod->invokeArgs($stmt, $rowReferences);
						while($stmt->fetch()){
							$row = array();
							foreach($stmtRow as $key => $value){
								$row[$key] = $value;
							}
							$queryResult[] = $row;
						}
						$stmt->free_result();
					} else {
						$queryResult[] = $stmt->affected_rows;
					}
				} else {
					$queryResult[] = FALSE;
				}
				$result[$queryKey] = $queryResult;
			}
			$stmt->close();
		} else {
			$result = FALSE;
		}

		if($multiQuery){
			return $result;
		} else {
			return $result[0];
		}
	}

	//不过好像没什么必要
    /**
     * 将数组自动转换为 where 子句
     * @param array $const
     * @param string $filter_str
     * @return string
     */
    private function create_where_str($const=null, $filter_str=null){
        $const_str = "";
        if(isset($const)) {
            $const_str .= " WHERE ";
            $index = 0;
            foreach ($const as $key=>$value){
				$key = "`$key`";
                if($index > 0)
                    $const_str .= ' AND ';
                if($value == null) {
                    $const_str .= ($key . 'is NULL');
                } else {
                    //判断键是否为数组，如果是数组则使用 in 子句
                    if(is_array($value)){
                        $const_str .= $key." in ('".implode("','", $value)."')";
                    } else
                        //NOTE: 无论是否为字符串，统一加单引号，让数据库自己处理类型转换
                        $const_str .= ($key . "='" . $value ."'");
                }
            }
        }
        return $const_str.' '.$filter_str;
    }

	/**
     * 将 mysqli_result 转换为 assoc 数组
	 * @param mysqli_result $res mysqli_result
	 * @return array
     */
	private function result_2_assoc($res) {
		$result = array();
		if (!($res instanceof mysqli_result)) {
			$result = array(false);
			return $result;
		}
		while($tmp = $res->fetch_assoc())
			array_push($result, $tmp);
		return $result;
	}
	// Using a prepared statement is not always the most efficient way of executing a statement.
	// A prepared statement executed only once causes more client-server round-trips than a non-prepared statement.
	// This is why the SELECT is not run as a prepared statement above.
	// 根据以上，不建议在 select 语句中使用 prepare
    /**
     * 根据给定条件执行 select, 如果只给出表名则执行 'SELECT * FROM $table_name
     * 返回一个 mysqli_result 变量
     * @param string $table_name
     * @param null|array $search_field 查询的字段名称
     * @param null|array $const where 子句中的条件，关系数组形式，一个键值对就是一个条件
     * @param null|string $filter_str 其他过滤选项，例如 SORT BY
     * @return bool|mysqli_result 返回一个 mysqli_result 类型的变量
     * @throws Exception
     */
	function Select($table_name, $search_field=null, $const=null, $filter_str=null) {
		try {
			$query_str = "SELECT ";
			$search_field_str = '*';

			if (isset($search_field)) {
				$search_field_str = implode(',',$search_field);
			}
			$const_and_filter_str = $this->create_where_str($const, $filter_str);
			
			$query_str .= ($search_field_str.' FROM '.$table_name.$const_and_filter_str);
			
			//debug
			//echo $query_str;
			//debug
		} catch (Exception $e) {
			throw $e;
		}
		return $this->mysqli->query($query_str);
	}

    /**
     * 根据给定条件执行 select, 如果只给出表名则执行 'SELECT * FROM $table_name
     * 以数组形式返回结果
     * @param string $table_name
     * @param null|array $search_field 查询的字段名称
     * @param null|array $const where 子句中的条件，关系数组形式，一个键值对就是一个条件
     * @param null|string $filter_str 其他过滤选项，例如 SORT BY
     * @return array 返回一个关系型数组，键名即字段名称
     * @throws Exception
     */
	function Select_assoc($table_name, $search_field=null, $const=null, $filter_str=null) {
		try {
			$res = $this->Select($table_name, $search_field, $const, $filter_str);
		} catch (Exception $e) {
			throw $e;
		}
		return $this->result_2_assoc($res);
	}

    /**
     * 非 prepare 直接执行 sql_str
     * @param string $sql_str
     * @return bool|mysqli_result
     * @throws Exception
     */
	function Query_complex($sql_str) {
		try {
			$res = $this->mysqli->query($sql_str);
		} catch (Exception $e) {
			throw $e;
		}
		return $res;
	}
	function Query_complex_assoc($sql_str) {
		try {
			$res = $this->Query_complex($sql_str);
		} catch (Exception $e) {
			throw $e;
		}
		return $this->result_2_assoc($res);
	}

    /**
     * 判断一个数组是否为关系型数组
     * @param $arr
     * @return bool
     */
	private function is_assoc($arr){
		return array_keys($arr) !== range(0, count($arr)-1);
	}
    /**
     * 检测一个数组是否为多维数组
     * @param $arr
     * @return bool
     */
    private function is_multiple_arr($arr){
        return count($arr) !== count($arr, COUNT_RECURSIVE);
    }

	private function create_keystr($arr){
        $key_str = '(`'.implode('`,`', array_keys($arr)).'`)';
		return $key_str;
	}

	/**
	 * 根据少量信息生成 prepare 语句
	 * @param string $table 目标表的名称
	 * @param array $arr 包含插入数据的数组，如果执行多次查询请使用多维数组，执行多次查询只需要在多维数组的第一个子数组中显示写出键名
	 * ，其他子数组只需要 count 和第一个子数组相同即可，无需包含键名
	 * @param string $insert_or_update 想要执行的操作，insert 或是 update
	 * @param bool $prepare 是否使用 prepare, 默认为 true
	 * @return array|string 如果不使用 prepare 返回 string(insert 为完整的 insert 语句，update 仅包含 update 部分不包含 where
	 * 子句) 如果使用 prepare 则返回包含 3 个元素的数组，结构如下
	 * [
	 * 		insert / update 值的数量 $count,
	 * 		insert / update 的 prepare 语句 (update 不包含 where 子句)
	 * 		value array(多维) [
	 * 			array(v11, v12, v13...),
	 * 			array(v21, v22, v23...),
	 * 			array(v31, v32, v33...),
	 * 			...
	 * 		]
	 * ]
	 * @throws Exception
	 */
    private function prepare_prepare($table, $arr, $insert_or_update, $prepare=true){
		$table = "`$table`";
        if (!$prepare) {
            // 如果不需要 prepare 语句，则返回一个插入语句
            // 或是一个不带 where 子句的 update 语句
            switch ($insert_or_update) {
                case 'insert':
					$insert_str = 'INSERT INTO '.$table;
                    $key_str = ''; $value_str = '';

                    // 检测是否为多维数组
                    if($this->is_multiple_arr($arr)) {
                        if($this->is_assoc($arr[0])) {
                            // 取第一行的 keys 并 implode
							$key_str = $this->create_keystr($arr[0]);
                        }
                        // 将每个次级数组取出
                        foreach ($arr as $item)
                            $value_str .= ("VALUES('".implode("','", array_values($item)) . "'),");
                        $value_str = substr($value_str, 0, -1);
                    }
                    // 检测是否为关系数组
                    // 一维关系型数组，包含keys 和 values
                    else if($this->is_assoc($arr)) {
                        $key_str = $this->create_keystr(array_keys($arr));
                        $value_str = " VALUES('".implode("','", array_values($arr))."')";
                    }
                    // 一维关非系型数组，只有值列表
                    else {
                        $value_str = ' VALUES('.implode("','",$arr)."')";
                    }
                    $insert_str .= ($key_str.' '.$value_str);
                    // debug
                    // echo $insert_str;
                    // debug
                    return $insert_str;
                    break;
                case 'update':
                    $update_str = 'UPDATE '.$table.' SET ';
                    // 判断是否为多维数组，如果是多维数组抛出异常
                    if ($this->is_multiple_arr($arr)) {
                        $this->throw_exception("update string can't accept a multiple array");
                        return false;
                    }
                    if (!$this->is_assoc($arr)) {
                        $this->throw_exception('update need an assoc array');
                        return false;
                    }
                    $set_str = '';
                    foreach ($arr as $key => $value)
                        $set_str .= ("`$key`"."='$value',");
                    $set_str = substr($set_str, 0, -1);
                    $update_str .= $set_str;
                    //debug
                    //echo $update_str;
                    //debug
                    return $update_str;
                    break;
				default:
					$this->throw_exception('unknown parameter');
					return false;
            }
        } else {
			// 使用 prepare
            switch ($insert_or_update) {
                case 'insert':
					// 检测是否为多维数组，如果是多维数组，则第一个数组包含 key
                    if ($this->is_multiple_arr($arr)) {
                        $data_arr = $arr[0];
                        $return_arr = array();
                        foreach ($arr as $item){
                            array_push($return_arr, array_values($item));
                        }
                    } else {
                        $data_arr = $arr;
                        $return_arr = array(array_values($arr));
                    }
                    $prepare_str = 'INSERT INTO '.$table;
                    $count = count($data_arr);
                    $value_str = ' VALUES(';
                    for ($i=0; $i<$count; $i++)
                        $value_str .= '?,';
                    $value_str = substr($value_str, 0, -1);
                    $value_str .= ')';
                    
                    //构造键
                    $key_str = '';
                    if($this->is_assoc($data_arr)) {
//                        $key_str = implode(',', array_keys($data_arr));
//                        $key_str = '('.$key_str.')';
                        $key_str = $this->create_keystr($data_arr);
                    }

                    $prepare_str .= ($key_str.$value_str);

                    return array(
                        'count'=>$count,
                        'prepare'=>$prepare_str,
                        'data'=>$return_arr
                    );
                    break;
                case 'update':
                    $update_str = 'UPDATE '.$table.' SET ';
                    // 判断是否为多维数组，如果是多维数组抛出异常
                    if ($this->is_multiple_arr($arr)) {
                        $this->throw_exception("update string can't accept a multiple array");
                        return false;
                    }
                    // 生成带有 '?' 的字符串
                    // 在循环中顺便生成值 value array 和 count
                    $set_str = ''; $count = 0; $return_arr=array();
                    foreach ($arr as $key => $value) {
                        $count ++;
                        $set_str .= ("`$key`".'=?,');
                        array_push($return_arr, $value);
                    }
                    return array(
                        'count'=>$count,
                        'prepare'=>$update_str.$set_str,
                        'data'=>$return_arr
                    );
                    break;
                default:
                    $this->throw_exception('error occurred in $insert_or_update parameter');
					return false;
            }
        }
    }

    /**
     * 绑定并执行 prepare 语句，返回受影响的行数
     * @param integer $count 需要 execute 的内容的字段数量(换句话说，就是 '?' 的数量);
     * @param string $prepare_str
     * @param string $value_type_str 绑定变量时需要的类型字符串
     * @param array $data 包含值的数组(多维普通数组，每一个子数组只需要包含要插入的值即可)
     * @return int 返回受影响的行数
     * @throws Exception
     */
	private function binding_and_execute($count, $prepare_str, $value_type_str, $data) {
		$stmt = $this->mysqli->prepare($prepare_str);
        //debug
        //print_r($stmt);
		if (!$stmt) {
			$this->throw_exception('prepare error');
			return false;
		}
        
        //创建变量数组 每一个变量都是 p1,p2,p3... 的形式
        $key_arr = array($value_type_str);
		for ($i=0; $i<$count; $i++) {
            // bind_params() 只接收引用类型
            $key_arr[] = &${'p'.$i};
		}
        //NOTE: bind_param 不能一次只绑定一个变量
        //NOTE: 所以这里要用到反射
        $bind_params = new ReflectionMethod('mysqli_stmt', 'bind_param');
        $bind_params->invokeArgs($stmt, $key_arr);
        $affected_rows = 0;
		foreach ($data as $row) {
			for ($i=0; $i<$count; $i++){
				${'p'.$i} = $row[$i];
			}
			$stmt->execute();
               //throw new Exception('execute error');
            $affected_rows += $stmt->affected_rows;
		}

		$stmt->close();
		return $affected_rows;
	}
    /**
     * 功能：向 $table 中插入一条或多条数据
     * 如果 $value_arr 是多维数组则代表需要插入多条数据
     * 那么传入的数组只需要第一个子数组包含 key 即可，后面的数组
     * 统一使用类似 array_values() 的方法获得插入的值
     *
     * @param string $table 目标表
     * @param array $value_arr 包含数据的数组，一维或多维
     * @param string $value_type_str 绑定参数时需要的参数类型
     * @param bool $prepare 是否使用 prepare 语句
     * @throws Exception
     * @return int 受影响的行数
     */
	function Insert($table, $value_arr, $value_type_str, $prepare=true) {
		$res = $this->prepare_prepare($table, $value_arr, 'insert', $prepare);
		//debug
		//print_r($res);
		if ($prepare) {
			$count = $res['count'];
			$prepare_str = $res['prepare'];
			$data = $res['data'];
			$affected = $this->binding_and_execute($count, $prepare_str, $value_type_str, $data);
			return $affected;
		} else {
			$this->mysqli->query($res);
			return $this->mysqli->affected_rows;
		}
	}

	function Update($table, $value_arr, $value_type_str, $const=null, $filter_str=null, $prepare=true) {
		$res = $this->prepare_prepare($table, $value_arr, 'update', $prepare);
		$const_and_filter_str = $this->create_where_str($const, $filter_str);
		if($prepare) {
			$prepare_str = $res['prepare'].$const_and_filter_str;
			$count = $res['count'];
			$data = $res['data'];
			$affected = $this->binding_and_execute($count, $prepare_str, $value_type_str, $data);
			return $affected;
		} else {
			$this->mysqli->query($res);
			return $this->mysqli->affected_rows;
		}
	}
	
	function Delete($table, $const, $filter_str=null) {
		$delete_str = "DELETE FROM `$table`".$this->create_where_str($const, $filter_str);
		$this->mysqli->query($delete_str);
		return $this->mysqli->affected_rows;
	}

    /**
     * @return mixed 最后插入记录的 id
     */
    function insert_id(){
        return $this->mysqli->insert_id;
    }
}
