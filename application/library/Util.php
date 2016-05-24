<?php
/**
 * 功能： 工具类
 * @date 2015-9-18 下午5:11:21
 * @author wangjian
 * @version 1.0.0
 */

class Util {	
	const FAIL_CONTENT = '执行任务失败了，请点击<a href="http://autotest.rong360.com/task/list">详情查看</a>';
	const REPORT_TITLE = '产品[%s]自动化测试执行结果报告';
	const FROM = 'autotest@rong360.com';
	public static function sendMail( $subject, $msg, $to) {
		$headers = 'MIME-Version: 1.0' . "\r\n";
    		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
	   	$headers .= 'From: ' . self::FROM. "\r\n";
		
		mail($to,$subject,$msg,$headers);
		
	}
	public static function filter_trace($path,$filter_dir) {
		$filter_ret = array(
			'errno'=>0,
			'msg'=>'',
		);
		if(empty($filter_dir)) {
			$filter_ret['errno']=2000;
			$filter_ret['msg']='empty filter dir';
			return $filter_ret;
		}
		if(!is_array($path)||empty($path)) {
			$filter_ret['errno']=1000;
			$filter_ret['msg']='error path';
			return $filter_ret;
		}
		$filter_dir_arr = explode(';', $filter_dir);
		$filter_ret['data'] = array();
		foreach($path as $key => $val) {
			foreach($filter_dir_arr as $dir_item) {
				if(substr($key, 0, strlen($dir_item)) == $dir_item ) {
					$filter_ret['data'][$key] = $val;
				}
			}
		}
		return $filter_ret;
	}
	
	
	public static function count_valid_code($source_file){
		if(!file_exists($source_file)) {
			return false;
		}
		
		$handle = fopen($source_file,"r");
		$line=0;
		$noteLine =array(
			'total'=>0,
			'note_count'=>0,
			'note_array'=>array(),
			);
		$isValid = false;
		while(!feof($handle)){
			$str = fgets($handle);
			$line++;
			if($isValid){
				$noteLine['note_array'][$line] =2 ;
				$noteLine['note_count']++;
				if(preg_match("/.*(\*\/)/",$str)){//多行*/注释结束
					$isValid = false;
				}			
				continue;
			}
			if(preg_match("/^[\s]*$/",$str)){//空行
				$noteLine['note_count']++;
				$noteLine['note_array'][$line] =2 ;
			}elseif(preg_match("/^[\s]*\/\//",$str)){//两杠注释
				$noteLine['note_count']++;
				$noteLine['note_array'][$line] =2 ;
			}elseif(preg_match("/^[\s]*(\/\*).*(\*\/)[\s]*$/",$str)){//单行注释
				$noteLine['note_count']++;
				$noteLine['note_array'][$line] =2 ;
			}elseif(preg_match("/^[\s]*(\/\*).*/",$str)){//多行/*注释开始
				$noteLine['note_count']++;
				$noteLine['note_array'][$line] =2 ;
				$isValid = true;
			}
		}
		$noteLine['total']=$line;
		return $noteLine;
	} 
}
