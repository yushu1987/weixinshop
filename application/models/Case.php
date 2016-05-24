<?php
/** 
 * @author Administrator
 * 
 */
class CaseModel {
	const SVN_BASE_URL = 'http://svn.rong360.com/svn/qa_tools/autoTest/';
	const HOME_PATH = '/home/rong/';
	const RESULT_PATH = '/home/rong/autoTest/taskspace/%s/%d/result';
	private static $caselist = array ();
	public function getCaseList($product, $pn = 0, $type = 1) {
		if (empty ( self::$caselist )) {
			$save_path = self::HOME_PATH . $product . 'Test';
			if (is_dir ( $case_path )) {
				$svn_url = self::SVN_BASE_URL . $product;
				exec ( "svn co $svn_url $save_path" ); // 不想这样 ，没办法，不得已的,觉得太low了
			}
			$case_path = $save_path . '/case';
			$this->_readCase ( $case_path, $type );
		}
		if($type == 1) {
			return array_slice ( self::$caselist, $pn, 10 );
		}else {
			return self::$caselist;
		}
	}
	private function _readCase($path, $type) {
		$dh = opendir ( $path ); // 打开目录
		while ( ($d = readdir ( $dh )) !== false ) {
			if ($d == '.' || $d == '..') { // 判断是否为.或..，默认都会有
				continue;
			}
			if (is_dir ( $path . '/' . $d )) { // 如果为目录
				self::_readCase ( $path . '/' . $d, $type ); // 继续读取该目录下的目录或文件
			} else {
				$filename = $path . '/' . $d;
				$pattern = "/\S+Test\.php$/";
				preg_match_all ( $pattern, $filename, $matches );
				if (! empty ( $matches [0] )) {
					if ($type != 0) {
						$detail = self::_analyseFile ( $filename );
						self::$caselist [] = array (
								'file' => basename ( $filename ),
								'class' => $detail ['class'],
								'func' => $detail ['func'] 
						);
					} else {
						self::$caselist [] = array (
								'file' => basename ( $filename ) 
						);
					}
				}
			}
		}
	}
	private function _analyseFile($file) {
		$detail = [ 
				'class' => [ ],
				'func' => [ ] 
		];
		$i = 0; // testFunc计数
		$fp = fopen ( $file, 'r' );
		$classArr = [ 
				'auth',
				'Date',
				'Time',
				'Desc',
				'case' 
		];
		$funcArr = [ 
				'测试点',
				'优先级',
				'进度',
				'步骤',
				'预期',
				'备注' 
		];
		$funcPattern = '/function.*(test.*)\(/';
		while ( ! feof ( $fp ) ) {
			$buffer = trim ( fgets ( $fp, 4096 ) );
			if (strlen ( $buffer ) == 0) {
				continue;
			}
			foreach ( $classArr as $key ) {
				$val = self::_getKeywords ( $buffer, $key );
				if ($val) {
					$detail ['class'] [$key] = $val;
				}
			}
			// 计算testfunction start
			foreach ( $funcArr as $key ) {
				$val = self::_getKeywords ( $buffer, $key );
				if ($val) {
					$detail ['func'] [$i] [$key] = $val;
				}
			}
			// 计算testfunction end
			preg_match ( $funcPattern, $buffer, $matches );
			if (count ( $matches ) == 2) {
				$detail ['func'] [$i] ['func'] = $matches [1];
				$i ++;
			}
		}
		fclose ( $fp );
		foreach ( $detail ['func'] as $key => $item ) {
			if (empty ( $item ['func'] )) {
				unset ( $detail ['func'] [$key] );
			}
		}
		return $detail;
	}
	private function _getKeywords($line, $word) {
		$wordPattern = "/^\*.*$word.*:(.*)$/";
		$line = str_replace ( "：", ":", trim ( $line ) );
		preg_match ( $wordPattern, $line, $matches );
		if ($matches) {
			return trim ( $matches [1] );
		}
		return '';
	}
}

?>
