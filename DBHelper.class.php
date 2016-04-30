<?php
class DBHelper {
	private $mysqli = NULL;
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
     * @throws Exception
     */
    function Change_db($dbname){
        try {
            $this->mysqli->select_db($dbname);
        } catch (Exception $e) {
            throw $e;
        }
    }
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
                if($index > 0)
                    $const_str .= ' AND ';
                if($value == null) {
                    $const_str .= ($key . 'is NULL');
                } else {
                    $const_str .= ($key . '=' . $value);
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
	function Select_assoc($table_name, $search_field=null, $const=null, $filter_str=null) {
		try {
			$res = $this->Select($table_name, $search_field, $const, $filter_str);
		} catch (Exception $e) {
			throw $e;
		}
		return $this->result_2_assoc($res);
	}
	
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
	//FIXME: should test if it's multiple array
    private function prepare_prepare($table, $arr, $insert_or_update, $prepare=true){
        if (!$prepare) {
            // 如果不需要 prepare 语句，则返回一个插入语句
            // 或是一个不带 where 子句的 update 语句
            switch ($insert_or_update) {
                case 'insert':
                    break;
                case 'update':
                    break;
            }
        } else {
            // 如果是 prepare 语句，返回一个数组
            // 数组的结构
            // [
            //      插入 / 修改 值的数量 $count
            //      插入 / 修改 prepare 语句 (修改语句不带有 where 子句)
            //      值数组 (多维)
            //          [
            //              array(v11, v12, v13),
            //              array(v21, v22, v23),
            //              ...
            //          ]
            // ]
            switch ($insert_or_update) {
                case 'insert':
                    if ($this->is_multiple_arr($arr)) {
                        $data_arr = $arr[0];
                        $return_arr = array();
                        foreach ($arr as $item){
                            array_push($return_arr, array_values($item));
                        }
                    } else {
                        $data_arr = $arr;
                        $return_arr = array($arr);
                    }
                    $prepare_str = 'INSERT INTO '.$table;
                    $count = count($data_arr);
                    $value_str = ' VALUES(';
                    for ($i=0; $i<$count; $i++)
                        $value_str .= '?,';
                    $value_str = substr($value_str, 0, -1);
                    $value_str .= ')';

                    $key_str = implode(',', array_keys($data_arr));
                    $key_str = '('.$key_str.')';

                    $prepare_str .= ($key_str.$value_str);

                    return array(
                        $count,
                        $prepare_str,
                        $return_arr
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
                        $set_str .= ($key.'=?,');
                        array_push($return_arr, $value);
                    }
                    return array(
                        $count,
                        $update_str.$set_str,
                        $return_arr
                    );
                    break;
                default:
                    $this->throw_exception('error occurred in $insert_or_update parameter');
            }
        }

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
        //FIXME: 这个函数需要重构
		$insert_str = 'INSERT INTO '.$table;
		if($this->is_assoc($value_arr))
			//prepare params
			$params = '('.implode(',',array_keys($value_arr)).') ';
		else
			//not assoc params
			$params = '';
		$insert_str .= $params.' VALUES(';
		if($prepare) {
			//prepare values
			$value_str = '';
			$value_count = count($value_arr);
			for ($i=0; $i<$value_count; $i++)
				$value_str .= '?,';
			$value_str = substr($value_str, 0, -1);
			$value_str .= ')';
			
			$insert_str .= $value_str;
			//debug
			//echo $insert_str;
			//debug
			if(!($stmt = $this->mysqli->prepare($insert_str)))
				$this->throw_exception($stmt->error);
			//binding params
			//each param is like 'p1', 'p2', 'p3'...
			for ($i=0; $i<$value_count; $i++){
				if(!($stmt->bind_param(substr($value_type_str,$i,1), ${'p'.$i})))
					$this->throw_exception($stmt->error);
			}
			$index = 0;
			foreach ($value_arr as $value) {
				${'p'.$index} = $value;
				$index ++;
			}
			if(!($stmt->execute()))
				$this->throw_exception($stmt->error);
		} else {
			$value_str = implode(',', array_values($value_arr));
			$insert_str .= $value_str;
			try {
				$this->mysqli->query($insert_str);
			} catch (Exception $e) {
				throw $e;
			}
		}
        return $this->mysqli->affected_rows;
		//debug
			
		//debug
	}
	
	function Update() {
	}
	
	function Delete() {
	}
}