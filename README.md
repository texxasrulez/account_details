Account Details Roundcube Plugins

I have merged 3 plugins, userinfo, moreuserinfo and serverinfo into one .. 

Account Details Plugin for Roundcube

Adds tab in Setting for more user info. 
* Identities
* email address
* storage space and quota
* server url, port and other useful info
* CalDAV URL;s
* CardDAV URL's

You can enable/disable certain things via the config.inc.php

Copy url's from Calendars and Address Books to clipboard with a simple click (Doesn't work in Firefox)

Installation
-------------
Upload contents to '/roundcube_location/plugins/account_details/'.

Copy folder named 'copy_to_web_root' into your webroot. Basically want your url like 'http(s):domain.ltd/tutorials/etc/'
I just have empty html index files. You can customized your tutorials to suit your needs and custom to your services.

Enable plugin via config.inc.php with

$config['plugins'] = array('account_details');

Enjoy!

More to come
