# Account Details Roundcube Plugin #

Adds tab in Setting for more user info. 
* Identities
* email address
* storage space quota
* Operating System
* Web Browser
* Video Resolution
* Mailbox Stats
* Server URL, port and other useful info
* CalDAV URL;s
* CardDAV URL's
* and more

You can enable/disable certain things via the config.inc.php

**Installation**

Upload contents to '/roundcube_location/plugins/account_details/'.

Copy folder named 'copy_to_web_root' into your webroot. Your url should look like this => 'http(s):domain.ltd/tutorials/etc/'
I just have empty html index files. You can customize your tutorials to suit your needs and custom to your site.

Enable plugin via config.inc.php with

$config['plugins'] = array('account_details');

Make an hourly cronjob with your web credentials as follows for Roundcube Version Checking:

`curl https://api.github.com/repos/roundcube/roundcubemail/releases | grep tag_name | grep -o "[0-9].[0-9].[0-9]\{1,\}" | sort -n | tail -1 > /path_to_roundcube/plugins/account_details/rc_latest.txt`


Enjoy!

:moneybag: **Donations** :moneybag:

If you use this plugin and would like to show your appreciation by buying me a cup of coffee, I surely would appreciate it. A regular cup of Joe is sufficient, but a Starbucks Coffee would be better ... \
Zelle (Zelle is integrated within many major banks Mobile Apps by default) - Just send to texxasrulez at yahoo dot com \
No Zelle in your banks mobile app, no problem, just click [Paypal](https://paypal.me/texxasrulez?locale.x=en_US) and I can make a Starbucks run ...

I appreciate the interest in this plugin and hope all the best ...

**Screenshot**
-----------
With All Details enabled

![Alt text](/tests/ad-screenshot1.png?raw=true "Account Details Screenshot")
