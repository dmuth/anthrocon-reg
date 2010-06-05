
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
	- Run reg/wamp/bin/make-local.sh to disable modules that talk to the network, such as aggregator
	- If seeing the main page over and over, make sure the rewrite module is enabled
	- If the theme is non-existant, go to the themes page (admin/build/themes) and make sure that the theme you want is set to the default.
	- SSL:
		- When testing, go into c:\wamp\bin\apache\Apache2.2.11\bin and run "httpd -t" to test the config file
		- If mod_ssl isn't being loaded, edit httpd.conf itself and uncomment the line to load mod_ssl

