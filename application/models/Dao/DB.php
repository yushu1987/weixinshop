<?php
/*
 * 数据库基类，对mysqli进行封装了
 */
define ( 'INVALID_SQL', 10008 );
define ( 'QUERY_ERROR', 10009 );
class Dao_DBModel {
	const T_NUM = 'n';
	const T_NUM2 = 'd';
	const T_STR = 's';
	const T_RAW = 'S';
	const T_RAW2 = 'r';
	const V_ESC = '%';
	
	// query result types
	const FETCH_RAW = 0; // 返回raw原生格式数据
	const FETCH_ROW = 1; // 返回数组
	const FETCH_ASSOC = 2; // 返回关联数组
	const FETCH_OBJ = 3; // 返回 Bd_DBResult 对象
	const LOG_SQL_LENGTH = 30;
	private $mysql = NULL;
	private $dbConf = NULL;
	private $isConnected = false;
	private $lastSQL = NULL;
	private $sqlAssember = NULL;
	private $_error = NULL;
	public function __construct() {
		$this->mysql = mysqli_init ();
	}
	public function __destruct() {
		$this->close ();
	}
	
	/**
	 * @brief 设置mysql连接选项
	 *
	 * @param $optName 选项名字        	
	 * @param $value 选项值        	
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function setOption($optName, $value) {
		return $this->mysql->options ( $optName, $value );
	}
	
	/**
	 * @brief 设置连接超时
	 *
	 * @param $seconds 超时时间        	
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function setConnectTimeOut($seconds) {
		if ($seconds <= 0) {
			return false;
		}
		return $this->setOption ( MYSQLI_OPT_CONNECT_TIMEOUT, $seconds );
	}
	
	/**
	 * @brief 获取mysql参数变量
	 *
	 * @param unknown_type $name        	
	 */
	public function __get($name) {
		switch ($name) {
			case 'error' :
				return $this->mysql->error;
			case 'errno' :
				return $this->mysql->errno;
			case 'insertID' :
				return $this->mysql->insert_id;
			case 'affectedRows' :
				return $this->mysql->affected_rows;
			case 'lastSQL' :
				return $this->lastSQL;
			case 'isConnected' :
				return $this->isConnected;
			case 'db' :
				return $this->mysql;
			default :
				return NULL;
		}
	}
	
	/**
	 * @brief 连接方法
	 *
	 * @param $host 主机        	
	 * @param $uname 用户名        	
	 * @param $passwd 密码        	
	 * @param $dbname 数据库名        	
	 * @param $port 端口        	
	 * @param $flags 连接选项        	
	 * @return true：成功；false：失败
	 */
	public function connect($host, $uname = NULL, $passwd = NULL, $dbname = NULL, $port = NULL, $flags = 0) {
		$port = intval ( $port );
		if (! $port) {
			$port = 3306;
		}
		$this->dbConf = array (
				'host' => $host,
				'port' => $port,
				'uname' => $uname,
				'passwd' => $passwd,
				'flags' => $flags,
				'dbname' => $dbname 
		);
		$this->isConnected = $this->mysql->real_connect ( $host, $uname, $passwd, $dbname, $port, NULL, $flags );
		$arrInfo = array (
				'ns' => $dbname,
				'query' => 'connect',
				'retry' => 1,
				'local_ip' => empty ( $_SERVER ['SERVER_ADDR'] ) ? '127.0.0.1' : $_SERVER ['SERVER_ADDR'],
				'remote_ip' => $host . ':' . $port,
				'res_len' => intval ( $this->isConnected ) 
		);
		
		return $this->isConnected;
	}
	
	/**
	 * @brief 重新连接
	 *
	 * @return true：成功；false：失败
	 */
	public function reconnect() {
		if ($this->dbConf === NULL) {
			return false;
		}
		$conf = $this->dbConf;
		$this->isConnected = $this->mysql->real_connect ( $conf ['host'], $conf ['uname'], $conf ['passwd'], $conf ['dbname'], $conf ['port'], NULL, $conf ['flags'] );
		$arrInfo = array (
				'ns' => $conf ['dbname'],
				'query' => 'reconnect',
				'retry' => 1,
				'local_ip' => empty ( $_SERVER ['SERVER_ADDR'] ) ? '127.0.0.1' : $_SERVER ['SERVER_ADDR'],
				'remote_ip' => $conf ['host'] . ':' . $conf ['port'],
				'res_len' => intval ( $this->isConnected ) 
		);
		return $this->isConnected;
	}
	
	/**
	 * @brief 关闭连接
	 *
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function close() {
		if (! $this->isConnected) {
			return;
		}
		$this->isConnected = false;
		$this->mysql->close ();
	}
	
	/**
	 * @brief 是否连接，注意，此时mysqli.reconnect需要被关闭
	 *
	 * @param
	 *        	$bolCheck
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function isConnected($bolCheck = false) {
		if ($this->isConnected && $bolCheck && ! $this->mysql->ping ()) {
			$this->isConnected = false;
		}
		return $this->isConnected;
	}
	
	/**
	 * @brief 查询接口
	 *
	 * @param $sql 查询sql        	
	 * @param $fetchType 结果集抽取类型        	
	 * @param $bolUseResult 是否使用MYSQLI_USE_RESULT        	
	 * @return 结果数组：成功；false：失败
	 */
	public function query($sql, $fetchType = self::FETCH_ASSOC, $bolUseResult = false) {
		$logPara = array (
				'db_host' => $this->dbConf ['host'],
				'db_port' => $this->dbConf ['port'],
				'default_db' => $this->dbConf ['dbname'] 
		);
		
		if (! is_string ( $sql )) {
			$this->_error ['errno'] = INVALID_SQL;
			$this->_error ['error'] = 'Input SQL is not valid,please use string or ISQL instance';
			CLogger::warning ( 'error:' . $this->_error ['error'] . ' errno:' . $this->_error ['errno'] );
			return false;
		}
		
		$this->lastSQL = $sql;
		$res = $this->mysql->query ( $sql, $bolUseResult ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT );
		$ret = false;
		$pos = strpos ( $sql, "\n" );
		if ($pos) {
			$logPara ['sql'] = strstr ( $sql, array (
					"\n",
					' ' 
			) );
		} else {
			$logPara ['sql'] = $sql;
		}
		// res is NULL if mysql is disconnected
		if (is_bool ( $res ) || $res === NULL) {
			$arrInfo = array (
					'ns' => $this->dbConf ['dbname'],
					'query' => $logPara ['sql'],
					'retry' => 1,
					'local_ip' => empty ( $_SERVER ['SERVER_ADDR'] ) ? '127.0.0.1' : $_SERVER ['SERVER_ADDR'],
					'remote_ip' => $this->dbConf ['host'] . ':' . $this->dbConf ['port'],
					'res_len' => 0,
					'errno' => QUERY_ERROR 
			);
			$ret = ($res == true);
			// call fail handler
			if (! $ret) {
				$this->_error ['errno'] = QUERY_ERROR;
				$this->_error ['error'] = 'Query failed';
				CLogger::warning ( 'error:' . $this->_error ['error'] . ' errno:' . $this->_error ['errno'] );
				if ($this->onfail !== NULL) {
					call_user_func_array ( $this->onfail, array (
							$this,
							&$ret 
					) );
				}
			}
		} else {
			$logPara ['affected_rows'] = $this->mysql->affected_rows;
			$arrInfo = array (
					'ns' => $this->dbConf ['dbname'],
					'query' => $logPara ['sql'],
					'retry' => 1,
					'local_ip' => empty ( $_SERVER ['SERVER_ADDR'] ) ? '127.0.0.1' : $_SERVER ['SERVER_ADDR'],
					'remote_ip' => $this->dbConf ['host'] . ':' . $this->dbConf ['port'],
					'res_len' => $logPara ['affected_rows'] 
			);
			switch ($fetchType) {
				case self::FETCH_ASSOC :
					$ret = array ();
					while ( $row = $res->fetch_assoc () ) {
						$ret [] = $row;
					}
					$res->free ();
					break;
				case self::FETCH_ROW :
					$ret = array ();
					while ( $row = $res->fetch_row () ) {
						$ret [] = $row;
					}
					$res->free ();
					break;
				default :
					$ret = $res;
					break;
			}
		}
		return $ret;
	}
	
	/**
	 * @brief 格式化查询接口
	 *
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function queryf() {
		$arrArgs = func_get_args ();
		if (($argNum = count ( $arrArgs )) == 0) {
			return false;
		}
		
		$fmt = $arrArgs [0];
		$fmtLen = strlen ( $fmt );
		$sql = '';
		$cur = 1;
		$next_pos = 0;
		
		while ( true ) {
			$esc_pos = strpos ( $fmt, self::V_ESC, $next_pos );
			if ($esc_pos === false) {
				$sql .= substr ( $fmt, $next_pos );
				break;
			}
			
			$sql .= substr ( $fmt, $next_pos, $esc_pos - $next_pos );
			$esc_pos ++;
			$next_pos = $esc_pos + 1;
			
			if ($esc_pos == $fmtLen) {
				return false;
			}
			
			$type_char = $fmt {$esc_pos};
			if ($type_char != self::V_ESC) {
				if ($argNum <= $cur) {
					return false;
				}
				$arg = $arrArgs [$cur ++];
			}
			
			switch ($type_char) {
				case self::T_NUM :
				case self::T_NUM2 :
					$sql .= intval ( $arg );
					break;
				case self::T_STR :
					$sql .= $this->escapeString ( $arg );
					break;
				case self::T_RAW :
				case self::T_RAW2 :
					$sql .= $arg;
					break;
				case self::V_ESC :
					$sql .= self::V_ESC;
					break;
				default :
					return false;
			}
		}
		
		$fetchType = self::FETCH_ASSOC;
		$bolUseResult = false;
		if ($argNum > $cur) {
			$fetchType = $arrArgs [$cur ++];
		}
		if ($argNum > $cur) {
			$bolUseResult = $arrArgs [$cur ++];
		}
		return $this->query ( $sql, $fetchType, $bolUseResult );
	}
	private function __getSQLAssember() {
		if ($this->sqlAssember == NULL) {
			$this->sqlAssember = new Dao_SQLAssemberModel ( $this );
		}
		return $this->sqlAssember;
	}
	
	/**
	 * @brief select接口
	 *
	 * @param $tables 表名        	
	 * @param $fields 字段名        	
	 * @param $conds 条件        	
	 * @param $options 选项        	
	 * @param $appends 结尾操作        	
	 * @param $fetchType 获取类型        	
	 * @param $bolUseResult 是否使用MYSQL_USE_RESULT        	
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function select($tables, $fields, $conds = NULL, $options = NULL, $appends = NULL, $fetchType = self::FETCH_ASSOC, $bolUseResult = false) {
		$this->__getSQLAssember ();
		$sql = $this->sqlAssember->getSelect ( $tables, $fields, $conds, $options, $appends );
		if (! $sql) {
			return false;
		}
		return $this->query ( $sql, $fetchType, $bolUseResult );
	}
	
	/**
	 * @brief select count(*)接口
	 *
	 * @param $tables 表名        	
	 * @param $conds 条件        	
	 * @param $options 选项        	
	 * @param $appends 结尾操作        	
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function selectCount($tables, $conds = NULL, $options = NULL, $appends = NULL) {
		$this->__getSQLAssember ();
		$fields = 'COUNT(*)';
		$sql = $this->sqlAssember->getSelect ( $tables, $fields, $conds, $options, $appends );
		if (! $sql) {
			return false;
		}
		$res = $this->query ( $sql, self::FETCH_ROW );
		if ($res === false) {
			return false;
		}
		return intval ( $res [0] [0] );
	}
	
	/**
	 * @brief Insert接口
	 *
	 * @param $table 表名        	
	 * @param $row 字段        	
	 * @param $options 选项        	
	 * @param $onDup 键冲突时的字段值列表        	
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function insert($table, $row, $options = NULL, $onDup = NULL) {
		$this->__getSQLAssember ();
		$sql = $this->sqlAssember->getInsert ( $table, $row, $options, $onDup );
		if (! $sql || ! $this->query ( $sql )) {
			return false;
		}
		return $this->mysql->affected_rows;
	}
	
	/**
	 * @brief Update接口
	 *
	 * @param $table 表名        	
	 * @param $row 字段        	
	 * @param $conds 条件        	
	 * @param $options 选项        	
	 * @param $appends 结尾操作        	
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function update($table, $row, $conds = NULL, $options = NULL, $appends = NULL) {
		$this->__getSQLAssember ();
		$sql = $this->sqlAssember->getUpdate ( $table, $row, $conds, $options, $appends );
		if (! $sql || ! $this->query ( $sql )) {
			return false;
		}
		return $this->mysql->affected_rows;
	}
	
	/**
	 * @brief delete接口
	 *
	 * @param $table 表名        	
	 * @param $conds 条件        	
	 * @param $options 选项        	
	 * @param $appends 结尾操作        	
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function delete($table, $conds = NULL, $options = NULL, $appends = NULL) {
		$this->__getSQLAssember ();
		$sql = $this->sqlAssember->getDelete ( $table, $conds, $options, $appends );
		if (! $sql || ! $this->query ( $sql )) {
			return false;
		}
		return $this->mysql->affected_rows;
	}
	
	/**
	 * @brief 获取上一次SQL语句
	 *
	 * @return string
	 */
	public function getLastSQL() {
		return $this->lastSQL;
	}
	
	/**
	 * @brief 获取Insert_id
	 *
	 * @return int
	 */
	public function getInsertID() {
		return $this->mysql->insert_id;
	}
	
	/**
	 * @brief 获取受影响的行数
	 *
	 * @return int
	 */
	public function getAffectedRows() {
		return $this->mysql->affected_rows;
	}
	
	/**
	 * @brief 查询、设置和移除失败处理句柄
	 *
	 * @param string $func        	
	 * @return boolean
	 */
	public function onFail($func = 0) {
		if ($func === 0) {
			return $this->onfail;
		}
		if ($func === NULL) {
			$this->onfail = NULL;
			return true;
		}
		if (! is_callable ( $func )) {
			return false;
		}
		$this->onfail = $func;
		return true;
	}
	public function escapeString($string) {
		return $this->mysql->real_escape_string ( $string );
	}
	
	/**
	 * @brief 选择db库
	 *
	 * @return boolean
	 * @param unknown_type $dbname        	
	 */
	public function selectDB($dbname) {
		if ($this->mysql->select_db ( $dbname )) {
			$this->dbConf ['dbname'] = $dbname;
			return true;
		}
		return false;
	}
	
	/**
	 * @brief 获取当前db中存在的表
	 *
	 * @param $pattern 表名Pattern        	
	 * @param $dbname 数据库        	
	 * @return array
	 */
	public function getTables($pattern = NULL, $dbname = NULL) {
		$sql = 'SHOW TABLES';
		if ($dbname !== NULL) {
			$sql .= ' FROM ' . $this->escapeString ( $dbname );
		}
		if ($pattern !== NULL) {
			$sql .= ' LIKE \'' . $this->escapeString ( $pattern ) . '\'';
		}
		
		if (! ($res = $this->query ( $sql, false ))) {
			return false;
		}
		$ret = array ();
		while ( $row = $res->fetch_row () ) {
			$ret [] = $row [0];
		}
		$res->free ();
		return $ret;
	}
	
	/**
	 * @brief 检查数据表是否存在
	 *
	 * @param string $name
	 *        	表名
	 * @param string $dbname
	 *        	数据库名
	 * @return NULL boolean
	 */
	public function isTableExists($name, $dbname = NULL) {
		$tables = $this->getTables ( $name, $dbname );
		if ($tables === false) {
			return NULL;
		}
		return count ( $tables ) > 0;
	}
	
	/**
	 * @brief 设置和查询当前连接的字符集
	 *
	 * @param string $name
	 *        	字符串编码设置
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function charset($name = NULL) {
		if ($name === NULL) {
			return $this->mysql->character_set_name ();
		}
		return $this->mysql->set_charset ( $name );
	}
	
	/**
	 * @brief 获取连接参数
	 *
	 * @return array
	 */
	public function getConnConf() {
		if ($this->dbConf == NULL) {
			return NULL;
		}
		return array (
				'host' => $this->dbConf ['host'],
				'port' => $this->dbConf ['port'],
				'uname' => $this->dbConf ['uname'],
				'dbname' => $this->dbConf ['dbname'] 
		);
	}
	
	/**
	 * @brief 获取当前mysqli错误码
	 *
	 * @return int
	 */
	public function errno() {
		return $this->mysql->errno;
	}
	
	/**
	 * @brief 获取当前mysqli错误描述
	 *
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function error() {
		return $this->mysql->error;
	}
	
	/**
	 * @brief 获取db库错误码
	 *
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function getErrno() {
		return $this->_error ['errno'];
	}
	
	/**
	 * @brief 获取db库错误描述
	 *
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function getError() {
		return $this->_error ['error'];
	}
	
	/**
	 * @brief 事务开始
	 *
	 * @return
	 *
	 *
	 *
	 */
	public function startTransaction() {
		$sql = 'START TRANSACTION';
		return $this->query ( $sql );
	}
	
	/**
	 * @brief 提交事务
	 *
	 * @return
	 *
	 *
	 */
	public function commit() {
		$ret = $this->mysql->commit ();
		return $ret;
	}
	
	/**
	 * @brief 回滚
	 *
	 * @return
	 *
	 */
	public function rollback() {
		$ret = $this->mysql->rollback ();
		return $ret;
	}
}
?>
