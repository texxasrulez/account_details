# Account Details for Roundcube Plugin

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
```
php composer.phar require texxasrulez/account_details
```
Upload contents to '/roundcube_location/plugins/account_details/'.

Enable plugin via config.inc.php with

$config['plugins'] = array('account_details');

Make an hourly cronjob with your web credentials as follows for Roundcube Version Checking:

```
curl -sL https://api.github.com/repos/roundcube/roundcubemail/releases/latest | jq -r ".tag_name" | sort -n | tail -1 > /path_to_roundcube/plugins/account_details/rc_latest.txt`
```

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
