<?php

/**
 * Change hMailServer Autoresponder
 *
 * Plugin that gives access to Autoresponder using postfixadmin database
 *
 * @version 2.0 - 31.07.2009
 * @author Pawel Muszynski pawel@prolin.pl
 * @author Grzegorz Marsza³ek graf0@post.pl
 * @website http://www.prolin.pl
 * @licence GNU GPL
 *
 * Requirements: Postfixadmin
 *
 * Changelog
 * 2.0 - 2012.07.27 - Fixed SQL, Larry skin comatible (thanks to Grzegorz Marsza³ek) 
 * 1.0 - 2009.07.31 - Initial release
 *
 **/
             

/**
 *
 * #1- Configure "pfadmin_autoresponder/config/config.inc.php".
 * #3- Register plugin ("./config/main.inc.php ::: $rcmail_config['plugins']").
 *
 **/

require_once('pfadmin_functions.php');

class pfadmin_autoresponder extends rcube_plugin
{
  public $task = 'settings';
  private $sql_select = 'SELECT * FROM vacation WHERE email = %u LIMIT 1;';
  private $sql_update = 'insert into vacation (email, active, subject, body, activefrom ,activeuntil) values (%u, %o, %s, %m, %f, %d) on duplicate key update active = %o, subject = %s, body = %m, activefrom = %f, activeuntil =%d;';
  private $date_format_regexp = '/^\d{4}\/\d{2}\/\d{2}$/';
  

  function init()
  {
    $this->_load_config();
    $this->add_texts('localization/');    
    $rcmail = rcmail::get_instance();
    $rcmail->output->add_label('pfadmin_autoresponder');
    $this->register_action('plugin.pfadmin_autoresponder', array($this, 'pfadmin_autoresponder_init'));
    $this->register_action('plugin.pfadmin_autoresponder-save', array($this, 'pfadmin_autoresponder_save'));
    $this->register_handler('plugin.pfadmin_autoresponder_form', array($this, 'pfadmin_autoresponder_form'));
    $this->include_script('pfadmin_autoresponder.js');
  }
  
  function _load_config()
  {
    $rcmail = rcmail::get_instance();
    $config = "plugins/pfadmin_autoresponder/config/config.inc.php";
    if(file_exists($config))
      include $config;
    $arr = array_merge($rcmail->config->all(),$rcmail_config);
    $rcmail->config->merge($arr);
  }  

  function pfadmin_autoresponder_init()
  {

    $this->add_texts('localization/');
    $this->register_handler('plugin.body', array($this, 'pfadmin_autoresponder_form'));

    $rcmail = rcmail::get_instance();
    $rcmail->output->set_pagetitle($this->gettext('autoresponder')); 
//    $rcmail->output->send('pfadmin_autoresponder.pfadmin_autoresponder');
    $rcmail->output->send('plugin');

  }
  
  function pfadmin_autoresponder_save()
  {
  
    $rcmail = rcmail::get_instance();
    $user        = $rcmail->user->data['username'];
    $enabled     = get_input_value('_autoresponderenabled', RCUBE_INPUT_POST);
    if(!$enabled)
      $enabled = 0;
    if(isset($_SESSION['dnsblacklisted']) && $_SESSION['dnsblacklisted'] != 'pass')
      $enabled = 0;
    $subject     = get_input_value('_autorespondersubject', RCUBE_INPUT_POST);
    $body        = get_input_value('_autoresponderbody', RCUBE_INPUT_POST);
    $date        = get_input_value('_autoresponderdate', RCUBE_INPUT_POST);
    $datefrom      = get_input_value('_autoresponderdatefrom', RCUBE_INPUT_POST);

    // check $datefrom
    if(preg_match("/^\s*$/", $datefrom) or !preg_match($this->date_format_regexp, $datefrom))
      $datefrom = "now()";
    if(preg_match("/^\s*$/", $date) or !preg_match($this->date_format_regexp, $date)){
      $date = "NULL";
    }
    if(!$enabled){
      $date = $datefrom = "NULL";
    }
            
    if (!($res = $this->_save($user,$enabled,$subject,$body,$date, $datefrom))) {
        if(isset($_SESSION['dnsblacklisted']) && $_SESSION['dnsblacklisted'] != 'pass'){
          $this->add_texts('../dnsbl/localization/');
          $rcmail->output->command('display_message', sprintf(rcube_label('dnsblacklisted', 'pfadmin_autoresponder'),$_SESSION['clientip']),'error');        
        }
        else{
          $rcmail->output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
        }
      } else
        $rcmail->output->command('display_message', "DUPA.".$res, 'error');

    if (!$rcmail->config->get('db_persistent')) {
      if ($dsn = $rcmail->config->get('db_dsnw')) {
                $rcmail->db = rcube_db::factory($dsn, '', false);
      }
    }

    $this->pfadmin_autoresponder_init();
  
  }

  function pfadmin_autoresponder_form()
  {
    $rcmail = rcmail::get_instance();

    // add some labels to client
    $rcmail->output->add_label(
      'pfadmin_autoresponder.autoresponder',
      'pfadmin_autoresponder.dateformatinconsistency',
      'pfadmin_autoresponder.dateformat',
      'pfadmin_autoresponder.entervalidmonth',
      'pfadmin_autoresponder.entervalidday',
      'pfadmin_autoresponder.enterfordigityear',
      'pfadmin_autoresponder.entervaliddate',
      'pfadmin_autoresponder.dateinpast',
      'pfadmin_autoresponder.subjectempty',
      'pfadmin_autoresponder.and'
    );
    
    $rcmail->output->add_script("var settings_account=true;");  

    $settings = $this->_get();
    
    $enabled     = $settings['active'];
    $subject     = $settings['subject'];
    $body        = $settings['body'];
    $date        = $settings['activeuntil'];
    $datefrom    = $settings['activefrom'];

    $date = str_replace("-","/",substr($date,0,10));
    $datefrom = str_replace("-","/",substr($datefrom,0,10));
    
    if($date == "0000/00/00")
      $date = "";
    if($datefrom == "0000/00/00")
      $datefrom = "";
       
    $rcmail->output->set_env('product_name', $rcmail->config->get('product_name'));

    // allow the following attributes to be added to the <table> tag
    $attrib_str = create_attrib_string($attrib, array('style', 'class', 'id', 'cellpadding', 'cellspacing', 'border', 'summary'));

    // return the complete edit form as table
    $table = new html_table(array('cols' => 2));

    // show autoresponder properties
    $field_id = 'autoresponder_subject';
    $input_autorespondersubject = new html_textarea(array('name' => '_autorespondersubject', 'id' => $field_id, 'cols' => 48, 'rows' => 2));

    $table->add('title', html::label($field_id, rep_specialchars_output($this->gettext('subject'))));
    $table->add(null, $input_autorespondersubject->show($subject));

    $field_id = 'autoresponderbody';
    $input_autoresponderbody = new html_textarea(array('name' => '_autoresponderbody', 'id' => $field_id, 'cols' => 48, 'rows' => 15));

    $table->add('title', html::label($field_id, rep_specialchars_output($this->gettext('autorespondermessage'))));
    $table->add(null, $input_autoresponderbody->show($body));

    $field_id = 'autoresponderdatefrom';
    $input_autoresponderdatefrom = new html_inputfield(array('name' => '_autoresponderdatefrom', 'id' => $field_id, 'value' => $date, 'maxlength' => 10, 'size' => 10));

    $table->add('title', html::label($field_id, rep_specialchars_output($this->gettext('autoresponderdatefrom'))));
    $table->add(null, $input_autoresponderdatefrom->show($datefrom) . " ". $this->gettext('dateformat'));

    $field_id = 'autoresponderdate';
    $input_autoresponderdate = new html_inputfield(array('name' => '_autoresponderdate', 'id' => $field_id, 'value' => $date, 'maxlength' => 10, 'size' => 10));

    $table->add('title', html::label($field_id, rep_specialchars_output($this->gettext('autoresponderdate'))));
    $table->add(null, $input_autoresponderdate->show($date) . " ". $this->gettext('dateformat'));

    $field_id = 'autoresponderenabled';
    $input_autoresponderenabled = new html_checkbox(array('name' => '_autoresponderenabled', 'id' => $field_id, 'value' => 1));

    $table->add('title', html::label($field_id, rep_specialchars_output($this->gettext('autoresponderenabled'))));
    $table->add(null, $input_autoresponderenabled->show($enabled));


    $out = html::div(array('class' => 'box'),
	html::div(array('id' => 'prefs-title', 'class' => 'boxtitle'), $this->gettext('autoresponder')) .
	html::div(array('class' => 'boxcontent'), $table->show() .
	html::p(null,
		$rcmail->output->button(array(
			'command' => 'plugin.pfadmin_autoresponder-save',
			'type' => 'input',
			'class' => 'button mainaction',
			'label' => 'save'
    	)))));
    $rcmail->output->add_gui_object('autoresponderform', 'autoresponder-form');

    return $rcmail->output->form_tag(array(
    	'id' => 'autoresponderform',
	'name' => 'autoresponderform',
	'method' => 'post',
	'action' => './?_task=settings&_action=plugin.pfadmin_autoresponder-save',
    ), $out);
  }
 
  private function _get()
  {
    $rcmail = rcmail::get_instance();
      
    $sql = $this->sql_select;

    if ($dsn = $rcmail->config->get('db_pfadmin_autoresponder_dsn')) {
        $db = rcube_db::factory($dsn, '', false);
        $db->set_debug((bool)$rcmail->config->get('sql_debug'));
        $db->db_connect('r');
    } else {
      die("FATAL ERROR ::: RoundCube Plugin ::: pfadmin_autoresponder ::: \$rcmail_config['db_pfadmin_autoresponder_dsn'] undefined !!! ==> die");
}
    if ($err = $db->is_error())
      return $err;
      
    $sql = str_replace('%u', $db->quote($rcmail->user->data['username'],'text'), $sql);
    $res = $db->query($sql);
                 
    if ($err = $db->is_error()){
       return $err;
    }
    $ret = $db->fetch_assoc($res);
    if (!$rcmail->config->get('db_persistent')) {
      if ($dsn = $rcmail->config->get('db_dsnw')) {
          $rcmail->db = rcube_db::factory($dsn, '', false);
      }
    }
    return $ret;  
  }

  private function _save($user,$enabled,$subject,$body,$date, $datefrom)
  {
    $cfg = rcmail::get_instance()->config;
    
    if ($dsn = $cfg->get('db_pfadmin_autoresponder_dsn')) {
      $db = rcube_db::factory($dsn, '', false);
    //  $db->set_debug((bool)$rcmail->config->get('sql_debug'));
      $db->db_connect('w');
                                        
    } else {
      die("FATAL ERROR ::: RoundCube Plugin ::: pfadmin_autoresponder ::: \$rcmail_config['db_pfadmin_autoresponder_dsn'] undefined !!! ==> die");
    }
    if ($err = $db->is_error())
      return $err;
    $sql = $this->sql_update;

    $sql = str_replace('%s',  $db->quote($subject,'text'), $sql);
    $sql = str_replace('%m',  $db->quote($body,'text'), $sql);
    $sql = str_replace('%d',  preg_match('/NULL|now/', $date) ? $date : $db->quote($date,'text'), $sql);            
    $sql = str_replace('%f',  preg_match('/NULL|now/', $datefrom) ? $datefrom : $db->quote($datefrom,'text'), $sql);           
    $sql = str_replace('%o',  $db->quote($enabled,'text'), $sql);
    $sql = str_replace('%u',  $db->quote($user,'text'), $sql);
    
    $res = $db->query($sql);
    $user_arr = preg_split('/@/',$user);
    $user_name = $user_arr[0];
    $domain = $user_arr[1];
    addtoalias($db, $user, $user);  // just in case
//    return ($domain);
    if ($enabled)
      $result = addtoalias($db, $user, $user_name."#".$domain."@".$cfg->get('vac_domain'));
    else
    {
      $result = removefromalias ($db, $user, $user_name."#".$domain."@".$cfg->get('vac_domain'));
    }

    if ($err = $db->is_error())
      return $err;
                              
    $res = $db->affected_rows($res);
    if (!$result) return $this->gettext('errorsaving');
  }
  
}

?>
