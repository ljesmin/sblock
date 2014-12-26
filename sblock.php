<?php

/**
 * Block bots sending spam through RoundCube
 *
 * @version 29.11.2014
 * @author Markus Arro
 * @author Lauri Jesmin
 * @licence GNU GPL
 *
 **/
 
class sblock extends rcube_plugin
{
    public $task = 'mail';

    function init()
    {
        $this->add_hook('message_before_send', array($this,'check_spammer'));
    }

/**
 *
 * Count addresses and mark spammers, callback for hook message_before_send
 * 
 * @param array $args array of header fields
 *
 **/
    function check_spammer($args) {

        $rcmail = rcmail::get_instance();
        $this->load_config();
        $db = $rcmail->get_dbh();
	
	$total=0;
	
	$headers=$args['message']->headers();
	if (isset($headers['To'])) $total+=1 + substr_count($headers['To'],',');
	if (isset($headers['Cc'])) $total+=1 + substr_count($headers['Cc'],',');
	if (isset($headers['Bcc'])) $total+=1 + substr_count($headers['Bcc'],',');
	
	
        $whitelist = $rcmail->config->get('spam_whitelist', NULL);
        $interval = $rcmail->config->get('spam_interval', NULL);
        $threshold = $rcmail->config->get('spam_threshold', NULL);

        $uid = $rcmail->user->ID;
        $username = $rcmail->user->get_username(local);


        // user is in whitelist, or configuration not set
        if (in_array($username, $whitelist) || !isset($interval) || !isset($threshold)) {
            return;
        } 
	
            // get e-mail send times from database
        $sql_result = $db->query('SELECT spam_flag FROM ' . get_table_name('users') . ' WHERE `user_id`=?', $uid);
        $user_table = $db->fetch_assoc($sql_result);
	    
	if ($user_table['spam_flag']>0) {
		// user is spammer do not let it send mail
            	write_log('sblock', "SPAM blocked user tries to send mail: " . $_SESSION['username'] . ", from " . $_SERVER['REMOTE_ADDR']); 
            	$this->add_texts('localization/');
            	$rcmail->output->command('display_message',$this->gettext('sblock.user_blocked'),'error');
            	$rcmail->output->send('iframe');
		$args['abort']=true;
		return $args;
	}
	

        $sql_result = $db->query('DELETE from `email_times` where `user_id`=? and `time` < date_sub(NOW(), interval ? SECOND)' ,$uid,$interval);
        $sql_result = $db->query('INSERT into `email_times` (`time`,`mailcount`,`user_id`) values ( NOW(),?,?)', $total,$uid);
        $sql_result = $db->query('SELECT sum(`mailcount`) spamsum from  `email_times` where `user_id`=?', $uid);

        $spam_count = $db->fetch_assoc($sql_result);

	if (!$spam_count) {
		// it actually should return something, but ignore it
		return;
	}

	if ($spam_count['spamsum'] > $threshold) {
		// big enough spammer
        	$db->query('UPDATE ' . get_table_name('users') . ' SET `spam_flag`=? WHERE `user_id`=?', true, $uid);
            	write_log('sblock', "SPAM spammer detected: " . $_SESSION['username'] . ", from " . $_SERVER['REMOTE_ADDR']); 
            	$this->add_texts('localization/');
            	$rcmail->output->command('display_message',$this->gettext('sblock.user_blocked'),'error');
            	$rcmail->output->send('iframe');
		$args['abort']=true;
		return $args;
	}
	
    }
}
