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

# Account Details — Installation Guide for Roundcube

This document provides step-by-step installation and configuration instructions for the **Account Details** Roundcube plugin you uploaded.

---

## 1) What’s in the package
- **Main plugin class**: `account_details.php` (class `account_details`)
- **Other plugin classes found**:
  - None

- **Config files/templates**:
  - `config.inc.php.dist`

- **SQL files**:
  - None

- **Assets present**: localization templates skins
- **Composer manifest**: present

### Hooks & actions detected
- `add_hook(...)`: settings_actions
- `register_action(...)`: plugin.account_details

---

## 2) Requirements
- A compatible **Roundcube** installation
- PHP matching your Roundcube version
- File system access to the Roundcube `plugins/` directory

---

## 3) Installation

### Option A — Composer (preferred)
1. Place the plugin in a VCS or local path that Composer can reference.
2. Ensure your Roundcube root has the **Roundcube plugin installer** in `require` (most distros do):

   ```json
   "require": {
     "roundcube/plugin-installer": "^0.3"
   }
   ```

3. Add a repository that points to the plugin (adjust the path):

   ```json
   "repositories": [
     { "type": "path", "url": "../account_details_composer" }
   ],
   "require": {
     "texxasrulez/account_details": "*"
   }
   ```

4. Run:
   ```bash
   composer install
   # or
   composer require texxasrulez/account_details:*
   ```

> Composer will install the plugin under `plugins/account_details` (per its `composer.json`).
---

### Option B — Manual install (simple)
1. Copy/unzip the plugin into Roundcube’s plugins directory:
   ```bash
   cd /path/to/roundcube
   unzip /tmp/account_details.zip -d plugins/account_details
   ```

2. **Permissions** (adjust user/group for your server):
   ```bash
   chown -R www-data:www-data plugins/account_details (or tailor to your server's user:group)
   find plugins/account_details -type d -exec chmod 755 {} \;
   find plugins/account_details -type f -exec chmod 644 {} \;
   ```

3. **Enable the plugin** in `config/config.inc.php`:
   ```php
   // Add 'account_details' (or 'account_details' if that’s the folder name)
   $config['plugins'] = array_unique(array_merge($config['plugins'] ?? [], ['account_details']));
   ```

4. **Copy and edit configuration** (if provided):
   - `config.inc.php.dist`

   Example: `cp plugins/account_details/config.inc.php.dist plugins/account_details/config.inc.php`

## 4) Configuration details
### `config.inc.php.dist` (snippet)
```php
<?php
/*
	Account Details options
	default config last updated in version 2009-09-26
	To hide a row, do not remove the variable, but set it: 'false'
*/
$account_details_config = array();
// Bullet Style - Default &#9679;
$account_details_config['bulletstyle'] = '&#9775;'; // Insert your favorite unicode here. https://www.w3schools.com/charsets/ref_utf_misc_symbols.asp
// URL Text box length - Good if you have a long domain name
$account_details_config['urlboxlength'] = '90'; // Numbers only
// === ACCOUNT/WEBMAIL/SERVER INFO ===================
// Display Roundcube Info
$account_details_config['display_rc'] = true;
$account_details_config['display_rc_version'] = true;
$account_details_config['display_rc_release'] = true;
/* Hourly Cron Job is required to be setup as follows: 
 * curl https://api.github.com/repos/roundcube/roundcubemail/releases | grep tag_name | grep -o "[0-9].[0-9].[0-9]\{1,\}" | sort -n | tail -1 >> /path_to_roundcube/plugins/account_details/rc_latest.txt
*/
$account_details_config['rc_latest'] = 'plugins/account_details/rc_latest.txt';
// Display Plugin List
$account_details_config['rc_pluginlist'] = true;
// Enable display of used/total quota
$account_details_config['enable_userid'] = true;
$account_details_config['enable_quota'] = true;
// Display last webmail login / create
$account_details_config['display_create'] = true;
$account_details_config['display_lastlogin'] = true;
// Enable User IP Address
$account_details_config['enable_ip'] = true; // Simple IP Address from your system IP Only - Shows LAN IP if behind firewall
```
---

## 5) Post-install steps
- Clear Roundcube caches if needed (remove contents of `temp/` and `cache/`, keep the directories).
- Log in as a user and verify the plugin’s UI/behavior appears where expected (e.g., settings pane, message view, or toolbar).
- Check your web server error log for any warnings or deprecations on first load; fix permissions or missing config if needed.

---

## 6) Uninstall
- Remove the plugin folder from `plugins/` (or remove it via Composer if you package it).

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
