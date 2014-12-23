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
$sql_result=$db->query("select username,mail_host from `$userstable` where spam_flag>0");
while ($row=$db->fetch_assoc($sql_result)) {
	echo $row['username']." is listed as spammer on ".$row['mail_host']."\n";
}
