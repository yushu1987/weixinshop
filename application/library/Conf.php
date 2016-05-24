<?php

/**
 *
 * @author Administrator
 *        
 */
class Conf {
	public static $config;
	public static function isApiUrl() {
		$pcUrls = array (
				'/product/home',
				'/product/add',
				'/product/all',
				'/product/update',
				'/product/pclist',
				'/product/pcinfo',
				'/product/modify',
				'/product/help',
				'/orders/pclist',
				'/orders/handle',
				'/orders/pcinfo',
				'/orders/finance' 
		);
		$uri = $_SERVER ['REQUEST_URI'];
		if (($pos = strpos ( $uri, "?" )) !== false) {
			$uri = substr ( $uri, 0, $pos );
		}
		return in_array ( $uri, $pcUrls );
	}
	public static function getDBConf() {
		self::$config = Yaf_Application::app ()->getConfig ();
		return self::$config->database->config->toArray ();
	}
	public static function getLogConf() {
		self::$config = Yaf_Application::app ()->getConfig ();
		return self::$config->log->config->toArray ();
	}
	public static function getProductConf() {
		self::$config = Yaf_Application::app ()->getConfig ();
		$products = self::$config->list->config->toArray ();
		return array(
				'list' => explode(',', $products['name']),
				'owner' => explode(',', $products['owner'])
		);
	}
	
	public static function getHelpConf() {
		return array(
				'title' => array (
						array (
								'key' => '系统名',
								'val' => '融360接口自动化执行平台'
						),
						array (
								'key' => '负责人',
								'val' => 'qa@rong360.com'
						),
						array (
								'key' => '功能',
								'val' => '通过平台发起任务，执行接口自动化测试，对代码进行回归测试'
						),
						array (
								'key' => '流程',
								'val' => '选择产品，勾选对应的case，提交；等待任务执行结束，邮件推送结果'
						)
				),
				'content' => array (
						array (
								'key' => '上传产品',
								'val' => '进入上传产品页面, 提交产品明细, 上传后可在产品列表中看到，并可以编辑'
						),
						array (
								'key' => '订单处理',
								'val' => '进入订单页面, 可见订单分为多组, 每一组可以做相应的处理'
						),
						array (
								'key' => '热门产品',
								'val' => '热门产品会在app首页显示, 并只显示10个, 设置超过10个热门, 会取最近的10个'
						),
						array (
								'key' => '财务分析',
								'val' => '当前未做'
						)
				),
				'notice' => array (
						array (
								'content' => '产品图片仅限一张, 且大小不能超过10M'
						),
						array (
								'content' => '下架的产品, 不会再产品列表中显示'
						),
						array (
								'content' => 'app显示产品详细, 仅显示一张图片'
						),
						array (
								'content' => '一张订单中, 可有多个产品'
						)
				)
		);
	}
}

?>
