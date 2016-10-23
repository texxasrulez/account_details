<?php

/**
 * Sample plugin that adds a new tab to the settings section
 * to display some information about the current user
 */
class account_details extends rcube_plugin
{
    public $task    = 'settings';
    public $noajax  = true;

    function init()
    {
        $this->add_texts('localization/', array('account_details'));
        $this->register_action('plugin.account_details', array($this, 'infostep'));
        $this->include_script('account_details.js');
		$this->include_stylesheet('account_details.css');
    }
		private function _load_config()
	{
		
		$fpath_config_dist	= $this->home . '/config.inc.php.dist';
		$fpath_config 		= $this->home . '/config.inc.php';
		
		if (is_file($fpath_config_dist) and is_readable($fpath_config_dist))
			$found_config_dist = true;
		if (is_file($fpath_config) and is_readable($fpath_config))
			$found_config = true;
		
		if ($found_config_dist or $found_config) {
			ob_start();

			if ($found_config_dist) {
				include($fpath_config_dist);
				$account_details_config_dist = $account_details_config;
			}
			if ($found_config) {
				include($fpath_config);
			}
			
			$config_array = array_merge($account_details_config_dist, $account_details_config);
			$this->config = $config_array;
			ob_end_clean();
		} else {
			raise_error(array(
				'code' => 527,
				'type' => 'php',
				'message' => "Failed to load Account Details plugin config"), true, true);
		}
	} 

    function infostep()
    {
		
        $this->register_handler('plugin.body', array($this, 'infohtml'));
		
        $rcmail = rcmail::get_instance();
		$rcmail->output->set_pagetitle($this->gettext('account_details'));
		$rcmail->output->send('plugin');
		
    }

    function infohtml()
    {	
		$this->_load_config();
		$rcmail = rcmail::get_instance();
		$user = $rcmail->user;
		
			// Set commalist variables from config and language file
		if ($this->config['pn_newline']) {
			$pn_newline = true;
			$pn_parentheses = false;
		} else {
			$pn_newline = false;
			$pn_parentheses = true;
		}
	
	$table = new html_table(array('cols' => 2, 'cellpadding' => 0, 'cellspacing' => 0, 'class' => 'account_details'));
		

    $skin = $rcmail->config->get('skin', 'classic');
    $this->include_script('flashclipboard.js');
    $rcmail->output->add_footer(html::tag('div', array('id' => 'zclipdialog', 'title' => $this->gettext('copiedtoclipboard'))));
    $icon = '&nbsp;' . html::tag('img', array('class' => 'zclip', 'src' => 'plugins/account_details/skins/' . $skin . '/images/clipboard.png', 'title' => $this->gettext('copytoclipboard'), 'alt' => $this->gettext('copytoclipboard'), 'align' => 'baseline'));


    $table = new html_table(array('class' => 'account_details', 'cols' => 2, 'cellpadding' => 0, 'cellspacing' => 0));
    
    $table->add('title', html::tag('h4', null, '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('account') . ':')));
    $table->add('', '');
	$identity = $user->get_identity();
	if ($this->config['enable_userid']) {
	$table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('userid') . ':'));
    $table->add('', rcube_utils::rep_specialchars_output($user->ID));
	}
    $table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('defaultidentity') . ':'));
    $table->add('', rcube_utils::rep_specialchars_output($identity['name'] . ' '));
    $table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('emailaddress') . ':'));
    $table->add('', rcube_utils::rep_specialchars_output($identity['email'] . ' '));
	
    $date_format = $rcmail->config->get('date_format', 'm/d/Y') . ' ' . $rcmail->config->get('time_format', 'H:i');
    if(date('Y', strtotime($user->data['created'])) > 1970){
      $created = new DateTime($user->data['created']);
	  if ($this->config['display_create']) {
      $table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('created') . ':'));
      $table->add('', rcube_utils::rep_specialchars_output(date_format($created, $date_format)));
    }
	}
	$lastlogin = new DateTime($user->data['last_login']);
	if ($this->config['display_lastlogin']) {
    $table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('lastlogin') . ':'));
    $table->add('', rcube_utils::rep_specialchars_output(date_format($lastlogin, $date_format)));
	}
    
			if ($this->config['enable_quota']) {
				
			$rcmail->storage_connect(true);
			$imap = $rcmail->imap;
		
			$quota = $imap->get_quota();
			
			if (quota) {
				$quotatotal = rcmail::get_instance()->show_bytes($quota['total'] * 1024);
				$quotaused = rcmail::get_instance()->show_bytes($quota['used'] * 1024) . ' (' . $quota['percent'] . '%)';

				if ($quota && ($quota['total']==0 && $rcmail->config->get('quota_zero_as_unlimited'))) {
					$quotatotal = 'unlimited';
				}
				
				$table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('storagequota')));
				$table->add('value', $quotatotal);
				
				$table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('usedstorage')));
				$table->add('value', $quotaused);
			}
		}
			if (!empty($this->config['location'])) {
				$table->add('title', html::tag('h4', null, '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('serverinfo') . ':')));
				$table->add('', '');
				$table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('location')));
				$table->add('value', $this->config['location']);
			}

		if (!empty($this->config['hostname'])) {
			$table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('web_url')));
			$table->add('value', $this->_host_replace($this->config['webmail_url']));
		}

		if (!empty($this->config['hostname_smtp'])) {
			$table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('smtp')));
			$table->add('value', $this->_host_replace($this->config['hostname_smtp']));
		}
		
		if (!empty($this->config['hostname_imap'])) {
			$table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('imap')));
			$table->add('value', $this->_host_replace($this->config['hostname_imap']));
		}
		
		if (!empty($this->config['hostname_pop'])) {
			$table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('pop')));
			$table->add('value', $this->_host_replace($this->config['hostname_pop']));
		}
		
		// Add custom fields
		$this->_custom_fields('customfields_server');
		
		// Port numbers - initial checking and generating of detailed information
		
		if ($this->config['spa_support_smtp'] and $this->config['spa_support_imap'] and $this->config['spa_support_pop']) {
		// SPA supported on all three protocols. We set this variable and will use it later to print the info on a row.
			$spa_all = true;
		} else {
		// SPA not supported on all three protocols. Instead of printing on a row we append to each supportet protocol
			if ($this->config['spa_support_smtp'])
				$smtp_notes_array_regularonly[] = $this->gettext('spaauthsupported');
			if ($this->config['spa_support_imap'])
				$imap_notes_regular = ' (' . $this->gettext('spaauthsupported') . ')';
			if ($this->config['spa_support_pop'])
				$pop_notes_regular = ' (' . $this->gettext('spaauthsupported') . ')';
		} 
		

		if ($this->config['smtp_auth_required_always']) {
			$smtp_notes_array_all[] = $this->gettext('authrequired');
		} else {
		// SMTP auth is not always enabled, we have to print something based on
		// the next config settings
		
			// Set the correct "SMTP after *" based on conf combination
			if ($this->config['smtp_after_pop'] and !$this->config['smtp_after_imap'])
				$smtp_after_text = $this->gettext('smtpafterpop');
			elseif (!$this->config['smtp_after_pop'] and $this->config['smtp_after_imap'])
				$smtp_after_text = $this->gettext('smtpafterimap');
			elseif ($this->config['smtp_after_pop'] and $this->config['smtp_after_imap'])
				$smtp_after_text = $this->gettext('smtpafterpopimap');
		
			if ($this->config['smtp_auth_required_else']) {
			// If SMTP auth is required unless something
				if ($this->config['smtp_relay_local'] and !$this->config['smtp_after_pop'] and !$this->config['smtp_after_imap']) {
					$smtp_notes_array_all[] = $this->gettext('authrequired_local');
				} else if ($this->config['smtp_relay_local'] and ($this->config['smtp_after_pop'] || !$this->config['smtp_after_imap'])) {
					$smtp_notes_array_all[] = str_replace("%s", $smtp_after_text, $this->gettext('authrequired_local_smtpafter'));
				} else if (!$this->config['smtp_relay_local'] and ($this->config['smtp_after_pop'] || !$this->config['smtp_after_imap'])) {
					$smtp_notes_array_all[] = str_replace("%s", $smtp_after_text, $this->gettext('authrequired_smtpafter'));
				}
			} else {
			// If SMTP auth is not required, but some other infp may be given
				if ($this->config['smtp_relay_local'])
					$smtp_notes_array_all[] = $this->gettext('openrelaylocal');
				if ($smtp_after_text)
					$smtp_notes_array_all[] = $smtp_after_text;
			}			
		}
		
		// We summarize the correct arrays
		$smtp_notes_array_regular = array_merge((array)$smtp_notes_array_all, (array)$smtp_notes_array_regularonly);
		$smtp_notes_array_encrypted = array_merge((array)$smtp_notes_array_all, (array)$smtp_notes_array_encryptedonly);
		
		// If we have some info in the SMTP information arrays, make them ready for printing
		if (!empty($smtp_notes_array_regular))
			$smtp_notes_regular = ucfirst($this->_separated_list($smtp_notes_array_regular, $and = false, $sentences = true, $commalist_ucfirst, $pn_parentheses, $pn_newline));
		if (!empty($smtp_notes_array_regular))
			$smtp_notes_encrypted = ucfirst($this->_separated_list($smtp_notes_array_encrypted, $and = false, $sentences = true, $commalist_ucfirst, $pn_parentheses, $pn_newline));
			
		
		// Port numbers - regular
		
		if (!empty($this->config['port_smtp']) or !empty($this->config['port_imap']) or !empty($this->config['port_pop']) or count($this->config['customfields_regularports']) > 0) {
		
			$table->add('title', html::tag('h4', null, '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('portnumbers') . ' - ' . $this->gettext('portnumbersregular'))));
			$table->add('', '');
			
			if ($spa_all) {	
			// SPA supported for all three protocols. We print it on a row.
				$table->add(array('colspan' => 2, 'class' => 'categorynote'), ucfirst($this->gettext('spaauthsupported')));
				$table->add_row();
			}
			
			if (!empty($this->config['port_smtp'])) {
				$table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('smtp')));
				$table->add('value', $this->gettext('port') . ' ' . $this->_separated_list($this->config['port_smtp'], $and = true) . $smtp_notes_regular);
			}
		
			if (!empty($this->config['port_imap'])) {
				$table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('imap')));
				$table->add('value', $this->gettext('port') . ' ' . $this->_separated_list($this->config['port_imap'], $and = true) . $imap_notes_regular);
			}
		
			if (!empty($this->config['port_pop'])) {
				$table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('pop')));
				$table->add('value', $this->gettext('port') . ' ' . $this->_separated_list($this->config['port_pop'], $and = true) . $pop_notes_regular);
			}
		
			// Add custom fields
			$this->_custom_fields('customfields_regularports');
		
		}
		
		// Port numbers - encrypted
		
		if (!empty($this->config['port_smtp-ssl']) or !empty($this->config['port_imap-ssl']) or !empty($this->config['port_pop-ssl']) or count($this->config['customfields_encryptedports']) > 0) {

			$portnumbers_regular_header =  html::tag('h4', null, '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('portnumbers') . ' - ' . $this->gettext('portnumbersencrypted')));
			if ($this->config['recommendssl'])
				$portnumbers_regular_header .= ' ' . html::tag('div', array('style' => 'color:green;'), '&nbsp;' .  $this->gettext('recommended'));

			$table->add(array('colspan' => 2, 'class' => 'header'), $portnumbers_regular_header);
			$table->add_row();
			
			if (!empty($this->config['port_smtp-ssl'])) {
				$table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('smtp-ssl')));
				$table->add('value', $this->gettext('port') . ' ' . $this->_separated_list($this->config['port_smtp-ssl'], $and = true) . $smtp_notes_encrypted);
			}
		
			if (!empty($this->config['port_imap-ssl'])) {
				$table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('imap-ssl')));
				$table->add('value', $this->gettext('port') . ' ' . $this->_separated_list($this->config['port_imap-ssl'], $and = true) . $imap_notes_encrypted);
			}
		
			if (!empty($this->config['port_pop-ssl'])) {
				$table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('pop-ssl')));
				$table->add('value', $this->gettext('port') . ' ' . $this->_separated_list($this->config['port_pop-ssl'], $and = true) . $pop_notes_encrypted);
			}

			// Add custom fields
			$this->_custom_fields('customfields_encryptedports');
			
		}
		
	
    $cals = array();
    $user = $username;
    if(class_exists('calendar')){
      $query = "SELECT user_id, caldav_user, caldav_pass, caldav_url, name from " . rcmail::get_instance()->db->table_name('caldav_calendars') . " WHERE user_id=?";
      $sql_result = $rcmail->db->query($query, $rcmail->user->ID);
      while ($sql_result && ($sql_arr = $rcmail->db->fetch_assoc($sql_result))) {
        $cals[$sql_arr['name']] = $sql_arr;
      }
    }
    if(count($cals) > 0){
      $i ++;
      $table->add('title', html::tag('h4', null, '&nbsp;' . $this->gettext('calendars') . ':&nbsp;&sup' . $i . ';'));
      $table->add('', '');
      ksort($cals);
      $repl = $rcmail->config->get('caldav_url_replace', false);
      foreach($cals as $key => $cal){
        $temp = explode('?', $cal['caldav_url'], 2);
        $url = slashify($temp[0]) . ($temp[1] ? ('?' . $temp[1]) : '');
         if(is_array($repl)){
          foreach($repl as $key1 => $val){
            $url = str_replace($key1, $val, $url);
          }
        }
        $table->add('title','&nbsp;&#9679; ' . $key);
        $table->add('', html::tag('span', null,rcube_utils::rep_specialchars_output(str_replace('%u', $user, str_replace('@', urlencode('@'), $url)))) . $icon);
      }
      if($clients == '' && $rcmail->config->get('account_details_show_tutorial_links', true)){
        $clients = ('');
      }
      if($i > 0 && $rcmail->config->get('account_details_show_tutorial_links', true)){
        $clients .= html::tag('hr') . '&nbsp;&sup' . $i . ';&nbsp;' . sprintf($this->gettext('clients'), 'CalDAV') . ':' . html::tag('br') . '&nbsp;&nbsp;- ' . html::tag('a', array('href' => 'https://www.mozilla.org/en-US/thunderbird/all.html', 'target' => '_blank'), 'Thunderbird');
        $clients .= ' + ' . html::tag('a', array('href' => 'https://addons.mozilla.org/en-US/thunderbird/addon/lightning/', 'target' => '_blank'), 'Lightning');
        $url = $rcmail->config->get('caldav_thunderbird','../tutorials/thunderbird-caldav');
        $clients .= html::tag('a', array('href' => $url, 'target' =>'_blank'), html::tag('div', array('style' => 'display:inline;float:right;'), 'Thunderbird ' . $this->gettext('tutorial')));
        $url = $rcmail->config->get('caldav_android_app','https://play.google.com/store/apps/details?id=org.dmfs.caldav.lib&hl=en');
        $clients .= html::tag('br') . '&nbsp;&nbsp;- ' . html::tag('a', array('href' => 'http://www.android.com/', 'target' => '_blank'), 'Android') . ' + ' . html::tag('a', array('href' => $url, 'target' => '_blank'), 'CalDAV-sync');
        $url = $rcmail->config->get('caldav_android','../tutorials/android-caldav');
        $clients .= html::tag('a', array('href' => $url, 'target' =>'_blank'), html::tag('div', array('style' => 'display:inline;float:right;'), 'Android ' . $this->gettext('tutorial')));
        $url = $rcmail->config->get('caldav_iphone','../tutorials/iphone-caldav');
        $clients .= html::tag('br') . '&nbsp;&nbsp;- ' . html::tag('a', array('href' => 'http://www.apple.com/iphone/', 'target' => '_blank'), 'iPhone') . html::tag('a', array('href' => $url, 'target' => '_blank'), html::tag('div', array('style' => 'display:inline;float:right;'), 'iPhone ' . $this->gettext('tutorial'))) . html::tag('br') . html::tag('br');
      }
    }
    $addressbooks = array();
    if(class_exists('carddav')){
      $query = "SELECT user_id, username, password, url, name from " . rcmail::get_instance()->db->table_name('carddav_addressbooks') . " WHERE user_id=?";
      $sql_result = $rcmail->db->query($query, $rcmail->user->ID);
      while ($sql_result && ($sql_arr = $rcmail->db->fetch_assoc($sql_result))) {
        $addressbooks[$sql_arr['name']] = $sql_arr;
      }
    }
    if(count($addressbooks) > 0){
      $i ++;
      $table->add('title', html::tag('h4', null, '&nbsp;' . $this->gettext('addressbooks') . ':&nbsp;&sup' . $i . ';'));
      $table->add('', '');
      ksort($addressbooks);
      $repl = $rcmail->config->get('carddav_url_replace', false);
      foreach($addressbooks as $key => $addressbook){
        $temp = explode('?', $addressbook['url'], 2);
        $url = slashify($temp[0]) . ($temp[1] ? ('?' . $temp[1]) : '');
         if(is_array($repl)){
          foreach($repl as $key1 => $val){
            $url = str_replace($key1, $val, $url);
          }
        }
        $table->add('title', '&nbsp;&#9679; ' . $key);
        $table->add('', html::tag('span', null, rcube_utils::rep_specialchars_output(str_replace('%u', $user, str_replace('@', urlencode('@'), $url)))) . $icon);
      }
      if($clients == '' && $rcmail->config->get('account_details_show_tutorial_links', true)){
        $clients = html::tag('hr');
      }
      if($i > 0 && $rcmail->config->get('account_details_show_tutorial_links', true)){
        $clients .= '&nbsp;&sup' . $i . ';&nbsp;' . sprintf($this->gettext('clients'), 'CardDAV') . ':' . html::tag('br') . '&nbsp;&nbsp;- ' . html::tag('a', array('href' => 'http://www.mozilla.org/en-US/thunderbird/all.html', 'target' => '_blank'), 'Thunderbird');
        $url = $rcmail->config->get('carddav_thunderbird','../tutorials/thunderbird-carddav');
        $clients .= ' + ' . html::tag('a', array('href' => 'https://sogo.nu/download.html#/frontends', 'target' => '_blank'), 'SOGo Connector') . html::tag('a', array('href' => $url, 'target' =>'_blank'), html::tag('div', array('style' => 'display:inline;float:right;'), 'Thunderbird ' . $this->gettext('tutorial')));
        $url = $rcmail->config->get('carddav_android_app','https://play.google.com/store/apps/details?id=org.dmfs.carddav.sync&hl=en');
        $clients .= html::tag('br') . '&nbsp;&nbsp;- ' . html::tag('a', array('href' => 'http://www.android.com/', 'target' => '_blank'), 'Android') . ' + ' . html::tag('a', array('href' => $url, 'target' => '_blank'), 'CardDAV-sync');
        $url = $rcmail->config->get('carddav_android_app_editor','https://play.google.com/store/apps/details?id=org.dmfs.android.contacts&hl=en');
        if($url){
          $clients .=  ' + ' . html::tag('a', array('href' => $url, 'target' => '_blank'), 'Contact Editor');
        }
        $url = $rcmail->config->get('carddav_android','../tutorials/android-carddav');
        $clients .= html::tag('a', array('href' => $url, 'target' =>'_blank'), html::tag('div', array('style' => 'display:inline;float:right;'), 'Android ' . $this->gettext('tutorial')));
        $url = $rcmail->config->get('carddav_iphone','../tutorials/iphone-carddav');
        $clients .= html::tag('br') . '&nbsp;&nbsp;- ' . html::tag('a', array('href' => 'http://www.apple.com/iphone/', 'target' => '_blank'), 'iPhone') . html::tag('a', array('href' => $url, 'target' => '_blank'), html::tag('div', array('style' => 'display:inline;float:right;'), 'iPhone ' . $this->gettext('tutorial')));
      }
    }
	$out = html::div(array('class' => 'settingsbox settingsbox-account_details'), html::div(array('class' => 'boxtitle'), $this->gettext('account_details') . ' for ' . $identity['name'])) . html::div(array('class' => 'scroller'), $table->show() . $clients);
	
			if ($this->config['enable_custombox']) {
			
			$out .= html::div(array('class' => 'settingsbox settingsbox-account_details-custom'), html::div(array('class' => 'boxtitle'), $this->config['custombox_header']) . html::div(array('class' => 'boxcontent'), $this->_print_file_contents($this->config['custombox_file'])));
		}
	
    return $out;
  }

	private function _custom_fields($arrayname)
	{
	// Add custom fields from a defines array name
		
	global $table;
	
	if (count($this->config[$arrayname]) > 0) {
			
			foreach ($this->config[$arrayname] as $key => $arrayvalue) {
				
				$coltype = $arrayvalue['type'];
				$coltext = $arrayvalue['text'];
				
			    if ($coltype == 'header' or $coltype == 'wholeline') {
					$table->add(array('colspan' => 2, 'class' => $coltype), $coltext);
					$table->add_row();
				} elseif ($coltype == 'title' or $coltype == 'value') {
					$table->add($coltype, $coltext);
				}
				
				$coltype = '';
				$coltext = '';
				
			}
		}
		
		return false;
	}
	
	
	private function _separated_list($array, $and = false, $sentences = false, $ucfirst = false, $parentheses = false, $newline = false)
	{
	// Return array as a separated list
		$str = '';
		$size = count($array);
		$i = 0;
		if ($sentences)
			$separator = ". ";
		else
			$separator = ", ";
		
		if ($parentheses and $newline)
			$str .= '<span class="fieldnote-parentheses fieldnote-newline">(';
		elseif ($parentheses)
			$str .= '<span class="fieldnote-parentheses"> (';
		elseif ($newline)
			$str .= '<span class="fieldnote-newline">';
		
		
		foreach ($array as $item) {
			if ($i == 0 and $ucfirst)
				$item = ucfirst($item);
			$str .= $item;
			$i++;
			if ($i < $size-1)
				$str .= $separator;
			elseif ($i == $size-1) {
				// final separator, and or comma?
				if ($and)
					$str .= ' ' . $this->gettext('and') . ' ';
				else 
					$str .= $separator;
				
			}
			if ($i == $size and $sentences and count($array) > 1)
				$str .= '.';
		}
		
		if ($parentheses and $newline)
			$str .= ')</span>';
		elseif ($parentheses)
			$str .= ')</span>';
		elseif ($newline)
			$str .= '</span>';
		
		return $str;
	}
	
	private function _print_file_contents($filename)
	{
	// Return contents of a file
	
	$filename = $filename;
	
		if (is_file($filename) and is_readable($filename)) {
			$handle = fopen($filename, "r");
			$contents = fread($handle, filesize($filename));
			fclose($handle);
			return $contents;
			/*include*/
		} else {
			return 'Could not output file ' . $filename;
		}
	}
	
	private function _host_replace($host) {
	// Does some replacements in a host string

		$rcmail = rcmail::get_instance();
		$user = $rcmail->user;

		$host = str_replace('%h', $user->data['mail_host'], $host);
		$host = str_replace('%s', $_SERVER['SERVER_NAME'], $host);

		if(empty($_SERVER['HTTPS']))
			$protocol = 'http';
		else 
			$protocol = 'https';	
		$host = str_replace('%p', $protocol, $host);
		
		$stripped_h_array = explode('.', $user->data['mail_host']);
		array_shift($stripped_h_array);
		$stripped_s_array = explode('.', $_SERVER['SERVER_NAME']);
		array_shift($stripped_s_array);
		$stripped_h = implode('.', $stripped_h_array);
		$stripped_s = implode('.', $stripped_s_array);
		$host = str_replace('%H', $stripped_h, $host);
		$host = str_replace('%S', $stripped_s, $host);
		
		return $host;
		
	}

}
?>