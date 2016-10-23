<?php

/*
 +-----------------------------------------------------------------------+
 | plugins/userinfo/localization/<lang>.inc                              |
 |                                                                       |
 | Localization file of the Roundcube Webmail Userinfo plugin            |
 | Copyright (C) 2012-2013, The Roundcube Dev Team                       |
 |                                                                       |
 | Licensed under the GNU General Public License version 3 or            |
 | any later version with exceptions for skins & plugins.                |
 | See the README file for a full license statement.                     |
 |                                                                       |
 +-----------------------------------------------------------------------+

 For translation see https://www.transifex.com/projects/p/roundcube-webmail/resource/plugin-userinfo/
*/

$labels = array();
$labels['account'] = 'Account Details';
$labels['account_details'] = 'Account Details';
$labels['authrequired'] = 'Authentication required';
$labels['created'] = 'Created';
$labels['defaultidentity'] = 'Name';
$labels['emailaddress'] = 'Email Address';
$labels['lastlogin'] = 'Last Login';
$labels['storagequota'] = 'Storage Space:';
$labels['usedstorage'] = 'Used storage:';
$labels['pluginname'] = 'Account Information';
$labels['plugindescription'] = 'Displays basic account information such as IMAP, POP & SMTP server protocols, default Identity, user’s CalDAV & CardDAV URLs and other details.';
$labels['accountinformation'] = 'Account Details';
$labels['encryption'] = 'Encryption';
$labels['port'] = 'Port';
$labels['auth'] = 'Authentication';
$labels['yes'] = 'Yes';
$labels['no'] = 'No';
$labels['back'] = 'Back';
$labels['userinfo'] = 'User Info';
$labels['userid'] = 'System User ID';
$labels['description'] = 'Get detailed information about your account.';
$labels['default'] = 'Default';
$labels['clients'] = 'Recommended clients which support %s protocol';
$labels['tutorial'] = 'Tutorial &nbsp;&nbsp;';
$labels['copiedtoclipboard'] = 'Copied to clipboard';
$labels['copytoclipboard'] = 'Copy to clipboard';
$labels['calendars'] = 'Calendars';
$labels['addressbooks'] = 'Address Books';
$labels['mailbox'] = 'Mailbox';


$labels['serverinfo'] = 'Server Info';
$labels['serverinformation'] = 'Server Information';
$labels['server'] = 'Server';

$labels['username'] = 'Username';

$labels['webmailsystem'] = 'Webmail system:';

$labels['hostname'] = 'Hostname:';
$labels['location'] = 'Server Location:';

$labels['portnumbers'] = 'Port numbers:';
$labels['portnumbersregular'] = 'regular connection to mail server';
$labels['smtp'] = 'Outgoing: SMTP:';
$labels['imap'] = 'Incoming: IMAP:';
$labels['pop'] = 'Incoming: POP:';
$labels['portnumbersencrypted'] = 'encrypted connection to mail server';
$labels['smtp-ssl'] = 'Outgoing: SMTP-SSL:';
$labels['imap-ssl'] = 'Incoming: IMAP-SSL:';
$labels['pop-ssl'] = 'Incoming: POP-SSL:';

$labels['recommended'] = 'SSL Recommended';
$labels['port'] = 'Port:';
$labels['and'] = 'and';


$labels['spaauthsupported'] = ' SPA password encyption supported';
$labels['web_url'] = 'Webmail URL:';
$labels['webmail'] = 'Webmail:';
$labels['url'] = 'URL';


// %s will be excanged with the appropiate "SMTP after *" below
$labels['openrelaylocal'] = 'Permitted to send when on same network as server';
$labels['authrequired_local'] = 'Authentication required, except when on the same network as server';
$labels['authrequired_local_smtpafter'] = 'Authentication or %s required, except when on the same network as server';
$labels['authrequired_smtpafter'] = 'Authentication or %s required';

// "SMTP after *" are to some extent known technical terminologies.
// For some languages it will probably be correct to not translate "after"
$labels['smtpafterpop'] = 'SMTP after POP';
$labels['smtpafterimap'] = 'SMTP after IMAP';
$labels['smtpafterpopimap'] = 'SMTP after POP/IMAP';

?>