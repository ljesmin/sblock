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

	syslog(LOG_WARNING,'Spammer: '.json_encode($args));

        $rcmail = rcmail::get_instance();
        $this->load_config();
        $db = $rcmail->get_dbh();
	
	$total=0;
	
	$headers=$args['message']->headers();
	if (isset($headers['To'])) $total+=1 + substr_count($headers['To'],',');
	syslog(LOG_WARNING,'Spammer kokku To: '.$total);
	if (isset($headers['Cc'])) $total+=1 + substr_count($headers['Cc'],',');
	syslog(LOG_WARNING,'Spammer kokku Cc: '.$total);
	if (isset($headers['Bcc'])) $total+=1 + substr_count($headers['Bcc'],',');
	
	syslog(LOG_WARNING,'Spammer kokku: '.$total);
	
        $whitelist = $rcmail->config->get('spam_whitelist', NULL);
        $interval = $rcmail->config->get('spam_interval', NULL);
        $threshold = $rcmail->config->get('spam_threshold', NULL);

        $uid = $rcmail->user->ID;
        $username = $rcmail->user->get_username(local);
        $now = time();


        // user is in whitelist, or configuration not set
        if (in_array($username, $whitelist) || !isset($interval) || !isset($threshold)) {
            return;
        } else {
	    $lastTime = $now-$interval;
            // get e-mail send times from database
            $sql_result = $db->query('SELECT send_times,spam_flag FROM ' . get_table_name('users') . ' WHERE `user_id`=?', $uid);
            $user_table = $db->fetch_assoc($sql_result);
            $sendTimes = unserialize($user_table['send_times']);
	    
	    if ($user_table['spam_flag']>0) {
		// user is spammer do not let it send mail
            	write_log('sblock', "SPAM blocked user tries to send mail: " . $_SESSION['username'] . ", from " . $_SERVER['REMOTE_ADDR']); 
            	$this->add_texts('localization/');
            	$rcmail->output->command('display_message',$this->gettext('sblock.user_blocked'),'error');
            	$rcmail->output->send('iframe');
		$args['abort']=true;
		return $args;
	    }

	    if (isset($sendTimes[(string)$now])) {
		    $sendTimes[(string)$now]+=$total;
	    } else {
		    $sendTimes[(string)$now]=$total;
	    }

		syslog(LOG_WARNING,'Spammer: '.json_encode($sendTimes)." ".serialize($sendTimes));
	    if (is_array($sendTimes) && !empty($sendTimes)) {
		    // sendTimes is array and is not empty
		    $allMessages=0;
		    foreach (array_keys($sendTimes) as $sendtime) {
			    	if ((int)$sendtime<$lastTime) {
					// too old, purge
					unset($sendTimes[$sendtime]);
				} else  {
					// recent, let's count it
					$allMessages+=$sendTimes[$sendtime];
				}
		    }	
		    if ($allMessages > $threshold) {
                	    $db->query('UPDATE ' . get_table_name('users') . ' SET `spam_flag`=? WHERE `user_id`=?', true, $uid);
            		    write_log('sblock', "SPAM blocked user: " . $_SESSION['username'] . ", from " . $_SERVER['REMOTE_ADDR']); 
            		    $this->add_texts('localization/');
            		    $rcmail->output->command('display_message',$this->gettext('sblock.user_blocked'),'error');
            		    $rcmail->output->send('iframe');
			    $args['abort']=true;
			    return $args;
		    } else {
			    // legit, send and update 
            		    $sendTimes = serialize($sendTimes);
            		    $db->query('UPDATE ' . get_table_name('users') . ' SET `send_times`=? WHERE `user_id`=?', serialize($sendTimes), $uid);
			    return;
		    }
	    }

        }
    }
}
