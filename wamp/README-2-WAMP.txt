
WAMP:
- Download WAMP
- Run installer
- docroot is in c:\wamp\www\ or /cygdrive/c/wamp/www/
- Copy in the latest backup of the website
- Helpful symlinks to set up from $HOME:
	www -> /cygdrive/c/wamp/www
	reg -> www/sites/all/modules/reg
	wamp -> reg/wamp

- Drupal on WAMP:
	- Back up live site with bin/backup.php
	- Make sure that the settings.php file is readable
		- Comment out $base_url if it's set
	- Get database credentials from settings.php and create that user in phpMyAdmin
	- Create the database in phpMyAdmin
	- Import the data
		- Helpful MySQL wrappers can be found in (reg module path)/wamp/bin
		- Getting errors about primary keys?
			- Run mysql-reg with "-f" parameter to force
		- TRUNCATE huge tables that we don't need: sessions, accesslog, watchdog
	- Create filecache/ directory if necessary

	- If seeing the main page over and over, make sure the rewrite module is enabled

	- Disable a bunch of modules that we don't need:
		- xmlsitemap, search, update
	

	- Optionally replace sites/all/modules/reg/ directory with checked out version
	- Run reg/wamp/bin/make-local.sh to disable modules that talk to the network, such as aggregator
	- If the theme is non-existant, go to the themes page (admin/build/themes) and make sure that the theme you want is set to the default.
	- SSL:
		- Run generate-ssl-key
		- Add the following to the end of httpd.conf:
			Include "c:/wamp/www/sites/all/modules/reg/wamp/httpd-ssl.conf"
		- When testing, go into c:\wamp\bin\apache\Apache2.2.11\bin and run "httpd -t" to test the config file
		- If mod_ssl isn't being loaded, edit httpd.conf itself and uncomment the line to load mod_ssl
	- Onsite reg:
		- Permissions for onsite reg



