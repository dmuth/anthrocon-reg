
MySQL Replication setup
=======================

my.ini:
	- Turn off skip-networking
	- Set log-bin and server-id on Master server in my.ini
	- Set server-id on Slave server

- Turn the Windows firewall off on the Slave and Master
	(You ARE running this on a physically secured network 
		and not over wireless, right? RIGHT?)

- User commands
	- CREATE USER 'user'@'host' IDENTIFIED BY 'password'
	- GRANT ALL ON database.* TO user
	- SET PASSWORD FOR user=PASSWORD('password')
	- GRANT REPLICATION SLAVE ON *.* TO 'user'@'host'
		- Cannot be granted on a specific database

- Scripts to run:
	- mysql-master-1-lock-tables
	- mysql-slave-1-dump-from-host (IP) drupal_ac | mysql-reg
		(takes about 11 minutes as of AC 2012's reg system)
	- mysql-master-2-status
	- mysql-slave-2-change-master
		(get database credentials from "drush sql-connect"
	- mysql-slave-3-start
	- mysql-slave-4-status
		(it's okay if there is an error, that may be from before)
	- (break table lock on master)
	- reload page from the reg system
	- mysql-slave-5-tail-accesslog
		- Should show identical entries on master and slave

- Replication commands
	- FLUSH TABLES WITH READ LOCK
	- SHOW MASTER STATUS
	- CHANGE MASTER TO MASTER_HOST='hostname', MASTER_USER='user', MASTER_PASSWORD='pass', MASTER_LOG_FILE='logfilename', MASTER_LOG_POS=pos
	- START SLAVE
	- STOP SLAVE
	- UNLOCK TABLES
	- SHOW SLAVE STATUS

