
MySQL Replication setup
=======================

- Set log-bin and server-id on Master server in my.ini
- Set server-id on Slave server
- Open up the Windows firewall on the Slave and Master

- User commands
	- CREATE USER 'user'@'host' IDENTIFIED BY 'password'
	- GRANT ALL ON database.* TO user
	- SET PASSWORD FOR user=PASSWORD('password')
	- GRANT REPLICATION SLAVE ON *.* TO 'user'@'host'
		- Cannot be granted on a specific database

- Replication commands
	- FLUSH TABLES WITH READ LOCK
	- SHOW MASTER STATUS
	- CHANGE MASTER TO MASTER_HOST='hostname', MASTER_USER='user', MASTER_PASSWORD='pass', MASTER_LOG_FILE='logfilename', MASTER_LOG_POS=pos
	- START SLAVE
	- STOP SLAVE
	- UNLOCK TABLES
	- SHOW SLAVE STATUS

