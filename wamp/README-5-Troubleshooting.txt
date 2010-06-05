
Troubleshooting
===============

Web server:
	- Screens with garbled data:
		- Make sure the theme doesn't have symlinks
		- Run bin/deploy.sh anthrocon copy

	- If turning on CSS aggregation causes Apache to crash, a preg_replace() in drupal_load_stylesheet() in common.php is the culprit.  Your best bet to carefully check the comments in your theme's style.css file, or else just not use aggregation

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

C30 Printers:
	- How to run a self-test:
		- Unplug printer
		- Hold pause button
		- Plug in printer
		- A card with lines and boxes will be printed
	- Ribbon type should always be "K: Premium Resin"


