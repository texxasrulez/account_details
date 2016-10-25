<?php

/*
	Account Details options
	default config last updated in version 2009-09-26
	
	To hide a row, do not remove the variable, but set it: ''
	
	If you want to edit the width of the boxes, see classes 
		div.settingsbox-account_details, div.settingsbox-custom
	in account_details.css.
*/

$account_details_config = array();

// Debugging
$config['account_details_debug'] = false;

// DONATION - Just trying to get paid. You can turn off Paypal Button here
$account_details_config['enable_paypal'] = true;

// === ACCOUNT/WEBMAIL/SERVER INFO ===================
// Display Roundcube Version
$account_details_config['display_rc_version'] = true;

// Display PHP Version
$account_details_config['display_php_version'] = true;

// Enable display of used/total quota
$account_details_config['enable_userid'] = true;
$account_details_config['enable_quota'] = true;

// Display last webmail login / create
$account_details_config['display_create'] = true;
$account_details_config['display_lastlogin'] = true;

//Enable IP Address
$account_details_config['enable_ip'] = true;

// Display Support URL - If enabled, Be sure and fill this out in Roundcubes main config.inc.php if not specified during installation
$account_details_config['enable_support'] = true;

// Server location (Example: 'City, Country')
$account_details_config['location'] = 'The Great State of Texas'; //Cannot be blank. Messes with tables

// Display Mailbox Details - Unread Count - Total Count - Size of Folder
$account_details_config['enable_mailbox'] = true;
// Enable or Disable Individual Folders
$account_details_config['enable_drafts'] = true; // Have not figured this out yet. Only can get INBOX Working. I need help
$account_details_config['enable_sent'] = true; // Have not figured this out yet. Only can get INBOX Working. I need help
$account_details_config['enable_junk'] = true; // Have not figured this out yet. Only can get INBOX Working. I need help
$account_details_config['enable_trash'] = true; // Have not figured this out yet. Only can get INBOX Working. I need help
$account_details_config['enable_archive'] = true; // Have not figured this out yet. Only can get INBOX Working. I need help

// For the next three URL/host variables, you can use these
// characters for dynamic replacement:
//     %p is replaced with the current protocol (http or https)
//     %h is replaced with the current mail server name
//     %H is replaced with the current mail server name;
//        everything to and including the fist dot stripped
//     %s is replaced with current http server name
//     %S is replaced with current http server name;
//        everything to and including the fist dot stripped

// Webmail URL
$account_details_config['webmail_url'] = '%p://%s/mail/';

// Server hostname
$account_details_config['hostname'] = '%S';
// If you have different server host names per protocol
$account_details_config['hostname_smtp'] = 'smtp.%S';
$account_details_config['hostname_imap'] = 'imap.%S';
$account_details_config['hostname_pop'] = 'pop.%S';

// Enable CalDAV and CardDAV URL's
$account_details_config['enable_dav_urls'] = true;

// === SERVER CAPABILITIES ==========================================

// Port numbers in arrays (multiple port numbers allowed).
// Assign empty string '' instead of array to hide the row.
$account_details_config['port_smtp'] = array('25');
$account_details_config['port_imap'] = array('143');
$account_details_config['port_pop'] = array('110');
$account_details_config['port_smtp-ssl'] = array('587');
$account_details_config['port_imap-ssl'] = array('993');
$account_details_config['port_pop-ssl'] = array('995');

// SPA auth support for protocols
$account_details_config['spa_support_smtp'] = true;  // SPA auth for SMTP
$account_details_config['spa_support_imap'] = true;  // SPA auth for IMAP
$account_details_config['spa_support_pop'] = true;   // SPA auth for POP

// Authentication always required on SMTP:
$account_details_config['smtp_auth_required_always'] = true;

// If previous setting is set to false, the following info will be used instead:
$account_details_config['smtp_relay_local'] = true;         // Open relay for hosts on server network
$account_details_config['smtp_after_pop'] = false;          // SMTP after POP support
$account_details_config['smtp_after_imap'] = false;         // SMTP after POP support
$account_details_config['smtp_auth_required_else'] = true;  // SMTP auth required if not


// Add a (recommended) note to the SSL port numbers header?
$account_details_config['recommendssl'] = true; //TODO Doesn't work as expexted

// Add a newline between the port numbers and the port notes
// if false - put in pharentesises instead
$account_details_config['pn_newline'] = false;

// === CUSTOM INFORMATION: ADD TO SERVER INFO BOX ======================

// You can add customized fields at the end of each category, or add fields to 
// the bottom of Server Information box. These main variable name defines
// where to add the content:
//     $account_details_config['customfields_account'][]
//     $account_details_config['customfields_webmail'][]
//     $account_details_config['customfields_server'][]
//     $account_details_config['customfields_regularports'][]
//     $account_details_config['customfields_encryptedports'][]
//     $account_details_config['customfields_bottom'][]
//
// Add as many fields as you like, with the content in 'text' and one of the
// following row types in set in 'type':
//     ['header'] will create a category header
//     ['wholeline'] will span over both columns
//     ['title'] will create a title tag (left)
//     ['value'] will create a value tag (right)
// A 'title' must be proceeded by a 'value' to not mess up the table layout.
// See the commented lines below for examples. HTML tags will work.
// Even PHP code will do, but keep in mind that you cannot print directly,
// you need to assign a value.
//
// You can also use language handling. As an example, change:
//    'sky'
// into:
//    $this->gettext('sky')
// and add this line to the language files (at least en_US.inc) in the localization/ folder:
//    $labels['sky'] = 'Sky';

// Example of a custom line in the webmail category:
//$account_details_config['customfields_webmail'][] = array('type' => 'title', 'text' => $this->gettext('webmailsystem'));
//$account_details_config['customfields_webmail'][] = array('type' => 'value', 'text' => 'Roundcube ' . RCMAIL_VERSION);
// Example of some custom content at bottom of the account_details box:
//$account_details_config['customfields_bottom'][] = array('type' => 'header', 'text' => 'Color information');
//$account_details_config['customfields_bottom'][] = array('type' => 'wholeline', 'text' => '<i>Here are some handy infos about colors:</i>');
//$account_details_config['customfields_bottom'][] = array('type' => 'title', 'text' => 'Sky');
//$account_details_config['customfields_bottom'][] = array('type' => 'value', 'text' => 'Blue');
//$account_details_config['customfields_bottom'][] = array('type' => 'title', 'text' => 'Grass');
//$account_details_config['customfields_bottom'][] = array('type' => 'value', 'text' => 'Green');
//$account_details_config['customfields_bottom'][] = array('type' => 'title', 'text' => 'Sun');
//$account_details_config['customfields_bottom'][] = array('type' => 'value', 'text' => 'Yellow');
//$account_details_config['customfields_bottom'][] = array('type' => 'wholeline',
//     'text' => $this->_print_file_contents('plugins/account_details/custom_includes/example_field_1.html'));



// === CUSTOM INFORMATION: SEPARATE BOX AT BOTTOM OF PAGE ===================

// Include a file with customized text in it's own box at the bottom
// This implementation is limited - PHP not possible, no language handling
//$account_details_config['enable_custombox'] = true; //TODO
//$account_details_config['custombox_header'] = 'Help a Brotha Out!';
//$account_details_config['custombox_file'] = 'plugins/account_details/custom_includes/example_box_1.html';

$account_details_config['intro'] = 'plugins/account_details/custom_includes/intro.html';
$account_details_config['paypal'] = 'plugins/account_details/custom_includes/paypal.html';

?>