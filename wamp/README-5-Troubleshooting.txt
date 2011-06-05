
Troubleshooting
===============

Webserver won't start?
	- Go into C:/wamp/bin/apache/Apache2.2.11/bin/ and run httpd.exe from the command line
		- If there are any errors (such as bad config), they will be reported here

Web server:
	- Screens with garbled data:
		- Make sure the theme doesn't have symlinks
		- Run bin/deploy.sh anthrocon copy
	- If turning on CSS aggregation causes Apache to crash, a preg_replace() in drupal_load_stylesheet() in common.php is the culprit.  Your best bet to carefully check the comments in your theme's style.css file, or else just not use aggregation
	- An error about "q", and then errors about the connection being reset or having encoding issues
		- Make sure we're not accessing $_GET["q"] without an empty() check...
	- The webserver completely wigging out:
		- Be careful about changing themes!
		- Way to fix: reinstall the contents of www/ and the registration system, the database can be left alone

MySQL:
	- Error that says "MySQL Server has gone away"?
		- Edit my.ini and change max_alowed_packet to a large (32M+) value

Self-signed SSL certs in MSIE:
	- Go into Tools->Internet Options->Security->Trusted Sites
	- Click "Sites", and add the current site
	- Choose "continue to this website"
	- Click "Cerificate Error" next to the address bar
	- Install the certificate

Internet Explorer 8:
"Automation server can't create object" error:
	- Go into Tools->Internet Options->Security->Internet
	- Click "Custom level"
		- Set "Initialize and script ActiveX controls not marked as safe" to "Enable"

