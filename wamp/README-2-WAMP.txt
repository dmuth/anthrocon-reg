
WAMP:
- Download WAMP
- Run installer
- docroot is in c:\wamp\www\ or /cygdrive/c/wamp/www/
- Copy in the latest backup of the website
- Helpful symlinks to set up from $HOME:
	www -> /cygdrive/c/wamp/www
	reg -> www/sites/all/modules/reg
	wamp -> reg/wamp

- Add the following to the end of $HOME/.bashrc:
	cd $HOME/wamp/
	. ./bashrc
	cd ..

- Drupal on WAMP:
	- Files
		- Back up live site with bin/backup.php
		- Drop www files into /cygdrive/c/wamp/www/
			- Create filecache/ directory if necessary
		- Make sure that the settings.php file is readable
		- Comment out $base_url if it's set in settings.php

	- Create the database (if necessary):
		- Get database credentials from settings.php and create that user in phpMyAdmin
		- Create the database in phpMyAdmin

	- Import the data
		- Helpful MySQL wrappers can be found in (reg module path)/wamp/bin
		- Getting errors about primary keys?
			- Run mysql-reg with "-f" parameter to force
		- Run reg/wamp/bin/make-local.sh to disable modules that talk to the network, such as aggregator
		- TRUNCATE huge tables that we don't need: sessions, accesslog, watchdog
		- Unblock the regadmin and regstaff users
		- Put this in my.ini: query-cache-size = 128M

	- Troubleshooting:
		- If seeing the main page over and over, make sure the rewrite module is enabled
		- If the theme is non-existant, go to the themes page (admin/build/themes) and make sure that the theme you want is set to the default.
		- Run "./bin/deploy.sh anthrocon-2012 copy" in theme you want to deploy
			- You mean need to remove the contents of filecache/ after that

	- Misc:
		- Optionally replace sites/all/modules/reg/ directory with checked out version

	- SSL:
		- Run generate-ssl-key
		- Add the following to the end of httpd.conf:
			Include "c:/wamp/www/sites/all/modules/reg/wamp/httpd-ssl.conf"
		- When testing, go into c:\wamp\bin\apache\Apache2.2.11\bin and run "httpd -t" to test the config file
		- If mod_ssl isn't being loaded, edit httpd.conf itself and uncomment the line to load mod_ssl
	- Onsite reg:
		- Permissions for onsite reg


