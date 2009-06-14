
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


TODO:
- remote access to webserver
	- deny access to phpMyAdmin
- Apache SSL
- MySQL replicaiton
- MySQL replication over SSL?

