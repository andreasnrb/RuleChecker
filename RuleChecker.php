<?php
/*
Plugin Name: Rule Checker
Plugin URI: http://artofwp.com/rule-checker
Description: ssss
Version: 9.09
Author: Cyonite Systems
Author URI: http://cyonitesystems.com/
*/

class RuleChecker{
	function RuleChecker(){
//		parent::ApplicationBase('RuleChecker',dirname(__FILE__),false,false);
		if(is_admin())
			add_action('admin_menu',array(&$this,'on_admin_menu'));
	}
	function on_admin_menu(){
		add_submenu_page('plugins.php','Rule Checker','Rule Checker',10,'rulechecker',array(&$this,'scanpage'));		
	}
	function scanpage(){
		include('check.php');
	}
}
$rc = new RuleChecker();

?>