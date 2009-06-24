
Cygwin:
- Download setup program from their site
- Run program, choose to "install from Internet", with local files saved in a package directory
- Recommended packages:
	- Default install (quicker than selecting a bunch of packages by hand)
	- devel/rcs
	- devel/subversion
	- editors/vim
	- editors/nano
	- archive/zip
	- archive/unzip
	- net/openssh
	- utils/screen
 - If you run into problems about missing cygz.dll, do a full reinstall, but from the local directory of packages

Optional:
- Download gvim for editing files under Cygwin

WAMP:
- Download WAMP
- Run installer
- docroot is in c:\wamp\www\ or /cygdrive/c/wamp/www/
- Copy in the latest backup of the website
- Drupal on WAMP:
	- Make sure that the settings.php file is readable
	- Get database credentials from settings.php and create that user in phpMyAdmin
	- Create the database in phpMyAdmin
	- Import the data
		- Helpful MySQL wrappers can be found in (reg module path)/wamp/bin
	- If seeing the main page over and over, make sure the rewrite module is enabled
	- If the theme is non-existant, go to the themes page (admin/build/themes) and make sure that the theme you want is set to the default.
- MySQL replication:
	- Set log-bin and server-id on Master server
	- Set server-id on Slave server
	- Open up the Windows firewall on the Slave and Master

	- Useful commands:
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

TODO:
- SQL scripts to config Drupal
    - Turn off various modules
        - Turn off poormanscron?
        - Definitely turn off the aggregation module

