<?php
require_once '/home/rong/spikephpcoverage/src/util/Line.php';
require_once '/home/rong/ReadLog.php';
Class FilterController extends BaseController{
	

	//合并所有报告
	public static function mergeReport(){
		
		$report  = glob("/home/rong/spikephpcoverage/src/result/"."*");
		$ret = array();
		foreach ($report as $reportPath){
			include($reportPath);
			$arr = $hwn;
			$uKey = array_intersect_key($ret, $arr);
			$ret += $arr;
			foreach ($uKey as $key=>$value){
				$ret[$key] += $arr[$key];
			}
		}
		return $ret;

	}
	//包含或排除的目录和文件$include，$exclude
	public function filterreportAction($taskid,$include,$exclude){

//		$include = array($include);
//		$exclude = array($exclude);
//var_dump($include);exit;

		$includefile = array();		
		foreach($include as $includepath){
			if(is_dir($includepath)&& isset($includepath)){
				$includefile = array_merge($includefile,FilterController::extracts($includepath));
			}else{
				$includefile[] = $includepath;
			}
		}
		$includefile = array_flip($includefile);

		$excludefile=array();
		foreach($exclude as $excludePath){
			if(is_dir($excludePath)){
				$excludefile = array_merge($excludefile,FilterController::extracts($excludefile));
			}else{
				$excludefile[] = $excludePath;
			}

		}
		$excludefile = array_flip($excludefile);
		//剔除要排除的文件
		$tmp = array_intersect_key($includefile, $excludefile);
		foreach($tmp as $key=>$value){
			unset($includefile[$key]);
		}
		//得到整合过的报告
		$allReport = FilterController::mergeReport();//var_dump($allReport);exit;
		//将需要统计的报告过滤出来
		$finalReport = array();
		foreach($includefile as $items=>$value){// var_dump($items);exit;
			if(array_key_exists($items,$allReport)){ 
				$finalReport = array_merge($finalReport,array($items=>$allReport[$items]));
			}

		}
		//统计包含文件个数和文件的有效行数，统计每个文件和所有文件
		$obj = new Line();
		$fileNum = 0;	
	//	foreach($finalReport as $k=>$v){

	//		$realNums += $obj->getTotalLines($k)['realNums'];
	//		$fileNum ++;

	//	}
		//统计覆盖行数

		$covNum = 0;
	//	foreach( $finalReport as $line ){
	//		$covNum += count($line);
		
	//	}

		foreach ($finalReport as $key => $value){
			$perValidNum = $obj->getTotalLines($key)['realNums'];
			$perTotalNum = $obj->getTotalLines($key)['total'];
			$perCovNum = count($value);
			$perUnCovNum = $perValidNum  - $perCovNum;
			$coveragePer = sprintf("%.2f",$perCovNum/$perValidNum*100);
			var_dump($key);
			var_dump(count($value));
			$covNum += count($value);
			//$realNums += $obj->getTotalLines($key)['realNums'];
			$realNums += $perValidNum;
			var_dump($obj->getTotalLines($key)['realNums']);
			var_dump($coveragePer);
                        $fileNum ++;
			
			$summary[$key]['detail'] = $value;
			$summary[$key]['summary'] = array('totalNum'=>$perTotalNum,'validNum'=>$perValidNum,'covNum'=>$perCovNum,'unCovNum'=>$perUnCovNum,'coverage'=>$coveragePer);
			//var_dump($summary);echo "\r\n++++\r\n";
			

		}
		echo "\r\n----------------------\r\n";
		
		//var_dump($covNum);
		//var_dump($realNums);
		//未覆盖总行数
		$uncoverNum = $realNums - $covNum;
		//计算覆盖率
		$coverRate = sprintf("%.2f",$covNum/$realNums*100);
		var_dump($coverRate);
		//将过滤后的结果输入到指定task里
		$report = array(
			//	'data' => $finalReport,
				'filenum' => $fileNum,
				'totalnum' => $realNums,
				'covnum' => $covNum,
				'uncovnum' => $uncoverNum,
				'rate' => $coverRate
			       );
		$taskPath = $taskid;var_dump($taskPath);
		$tmp = explode("/",$taskid);
		$length = sizeof($tmp);
		if(!$tmp[$length-1]){
			$taskid = $tmp[$length-2];
		}else{
			$taskid = $tmp[$length-1];
		}
		
		$data = array(
				'taskid' => '1',//$taskid,
				'report' => $report
			     );
		$addData = new CoverModel();
		$addData->addCoverageData($data);

		ReadLog::writeLog(var_export($summary,true));
		$fn = $taskPath.'/result.log';
                $fp = fopen($fn,"w");
                if($fp){
           	$flag=fwrite($fp,var_export($summary,true));
                fclose($fp);
    	    }else{
        	    echo "open file failed\n";
       		 }


		return $coverRate;


	}


	//获取目录下所有已.php结尾的文件  
	public static function extracts($dirPath){

		$tmp =array();
		$files = glob($dirPath."*");
		foreach($files as $item){
			if(is_dir($item)){
				$t = $item . '/';
				$tmp = array_merge($tmp, FilterController::extracts($t));
			}else{
				if(preg_match('/.php$/i',$item)===1){
					$tmp[] = $item;
				}

			}
		}
		return $tmp;	
	}




}


# //$fun = $argv[1];
# $p1  = $argv[1];
# $p2  = $argv[2];
# $p3  = $argv[3];
# 
# call_user_func(array(new FilterController(), 'filterReport'), $p1, array($p2), array($p3));
