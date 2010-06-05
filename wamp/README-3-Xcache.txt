
Xcache setup and install
========================

- Loading this should give you a 50% speed boost

- http://xcache.lighttpd.net/
	- If unsure of VC6 or VC9, check the output of phpinfo()
	- http://xcache.lighttpd.net/pub/Releases/1.3.0/XCache-1.3.0-php-5.3.0-Win32-VC6-x86.zip

- Put the following in php.ini:
	extension=php_xcache.dll	; Required
	[xcache]					; Required
	xcache.cacher=On			; Required
	xcache.size = 64M			; Required

