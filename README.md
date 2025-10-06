# Account Details for Roundcube Plugin

[![Packagist](https://img.shields.io/packagist/dt/texxasrulez/account_details?style=flat-square)](https://packagist.org/packages/texxasrulez/account_details)
[![Packagist Version](https://img.shields.io/packagist/v/texxasrulez/account_details?style=flat-square)](https://packagist.org/packages/texxasrulez/account_details)
[![Project license](https://img.shields.io/github/license/texxasrulez/account_details?style=flat-square)](https://github.com/texxasrulez/account_details/LICENSE)
[![GitHub stars](https://img.shields.io/github/stars/texxasrulez/account_details?style=flat-square&logo=github)](https://github.com/texxasrulez/account_details/stargazers)
[![issues](https://img.shields.io/github/issues/texxasrulez/account_details)](https://github.com/texxasrulez/account_details/issues)
[![Donate to this project using Paypal](https://img.shields.io/badge/paypal-donate-blue.svg?style=flat-square&logo=paypal)](https://www.paypal.me/texxasrulez)


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
![Alt text](/tests/screenshot2.png?raw=true "Account Details Screenshot")

<h1>Hi 👋, I'm Gene Hawkins</h1>
<h3>Just a simple man from Texas</h3>

<p align="left"> <img src="https://komarev.com/ghpvc/?username=texxasrulez&label=Profile%20views&color=0e75b6&style=flat" alt="texxasrulez" /> </p>

<p align="left"> <a href="https://github.com/texxasrulez/account_details"><img src="https://account_details.vercel.app/?username=texxasrulez" alt="texxasrulez" /></a> </p>

<h3 align="left">Languages and Tools:</h3>
<p align="left"> <a href="https://www.w3schools.com/css/" target="_blank" rel="noreferrer"> <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/css3/css3-original-wordmark.svg" alt="css3" width="40" height="40"/> </a> <a href="https://www.w3.org/html/" target="_blank" rel="noreferrer"> <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/html5/html5-original-wordmark.svg" alt="html5" width="40" height="40"/> </a> <a href="https://developer.mozilla.org/en-US/docs/Web/JavaScript" target="_blank" rel="noreferrer"> <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/javascript/javascript-original.svg" alt="javascript" width="40" height="40"/> </a> <a href="https://www.linux.org/" target="_blank" rel="noreferrer"> <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/linux/linux-original.svg" alt="linux" width="40" height="40"/> </a> <a href="https://www.mysql.com/" target="_blank" rel="noreferrer"> <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/mysql/mysql-original-wordmark.svg" alt="mysql" width="40" height="40"/> </a> <a href="https://www.php.net" target="_blank" rel="noreferrer"> <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/php/php-original.svg" alt="php" width="40" height="40"/> </a> </p>

<p><img align="left" src="https://github-readme-stats.vercel.app/api/top-langs?username=texxasrulez&show_icons=true&locale=en&layout=compact" alt="texxasrulez" /></p>

<p>&nbsp;<img align="center" src="https://github-readme-stats.vercel.app/api?username=texxasrulez&show_icons=true&locale=en" alt="texxasrulez" /></p>

<p><img align="center" src="https://github-readme-streak-stats.herokuapp.com/?user=texxasrulez&" alt="texxasrulez" /></p>

