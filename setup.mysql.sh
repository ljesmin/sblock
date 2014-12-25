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
	ADD `spam_flag` bool DEFAULT '0';
	");

$db->query("CREATE TABLE `email_times` (
  `time` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `mailcount` int UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
   CONSTRAINT `user_id_fk_email_times` FOREIGN KEY (`user_id`)
   REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
   ) /*!40000 ENGINE=INNODB */ /*!40101 CHARACTER SET utf8 COLLATE utf8_general_ci */;
");
?>
