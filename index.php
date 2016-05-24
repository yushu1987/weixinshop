<?php 
/**
 * 入口文件
 * @date 2016年1月23日12:39:18
 * @author wangjian
 * @version 1.0.0
 */
define("APP_PATH",  dirname(__FILE__));
define('APPLICATION_PATH', APP_PATH . '/application');
$app = new Yaf_Application(APP_PATH."/conf/application.ini");
$app->bootstrap()->run();