#!/usr/bin/env php 
<?php

define('INSTALL_PATH', realpath(dirname(__FILE__) . '/../..') . '/' );
require INSTALL_PATH.'program/include/clisetup.php';

// connect to DB
$RCMAIL = rcmail::get_instance();
$db = $RCMAIL->get_dbh();
$db->db_connect('w');

if (!$db->is_connected() || $db->is_error()) {
	rcube::raise_error("No DB connection", false, true);
}

$userstable = get_table_name('users');

if (!empty($_SERVER['argv']) && is_array($_SERVER['argv']) && count($_SERVER['argv'])>1) {
	$all=$_SERVER['argv'];
	array_shift($all);
	foreach ($all as $clear) {
		$sql_prep=$db->query("update `$userstable` set spam_flag=0 where username='$clear'");
	}
}
$userstable = get_table_name('users');
$sql_result=$db->query("select username from `$userstable` where spam_flag>0");
