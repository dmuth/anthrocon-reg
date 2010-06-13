
Troubleshooting
===============

Web server:
	- Screens with garbled data:
		- Make sure the theme doesn't have symlinks
		- Run bin/deploy.sh anthrocon copy
	- If turning on CSS aggregation causes Apache to crash, a preg_replace() in drupal_load_stylesheet() in common.php is the culprit.  Your best bet to carefully check the comments in your theme's style.css file, or else just not use aggregation
	- An error about "q", and then errors about the connection being reset or having encoding issues
		- Make sure we're not accessing $_GET["q"] without an empty() check...

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

