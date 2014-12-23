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
$db->query("alter table `$userstable`
        ADD `send_times` text,
	ADD `spam_flag` bool DEFAULT '0';
	")
?>
