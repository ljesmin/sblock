# Sblock - RoundCube plugin for limiting number of messages per user

Sblock will set a spam flag in the database for a user, based on
two config values: spam_interval and spam_threshold. Spam flag will only
be set if the user has sent more emails than "spam_threshold" during
the period of "spam_interval". If the spam flag is set, no outgoing
e-mails are allowed and an error message is displayed. All blocked accounts
are logged in logs/sblock.

This plugin is tested and works with MySQL, it is not tested with other 
databases. 

To remove the spam flag for a user, run the included shell script with
the username as a parameter (or many usernames separated with space).
'''
	./remove_spamflag.sh <username>
'''

## INSTALL
* Create config.inc.php
* Run database initialization script
'''
./setup.mysql.sh
'''
* Enable plugin in config/main.inc.php

Authors: Markus Arro, Lauri Jesmin
