<?php

if(THIS_SCRIPT == 'showthread.php')
{
  global $templatelist;

  if(!isset($templatelist))
  {
    $templatelist = '';
  }

  $templatelist .= ',lock_wrapper,lock_form,';
}
// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

function lock_info()
{
  global $lang;
  isset($lang->lock) || $lang->load('lock');

  return array(
    'name' => 'Lock',
    'description' => $lang->lock_desc,
    'website' => 'https://github.com/neko',
    'author' => 'Neko',
    'authorsite' => 'https://github.com/neko',
    'version' => '1.1',
    'compatibility' => '18*',
    'pl' => '13',
  );
}

if(!defined('IN_ADMINCP'))
{
  // keep people from using the highlight feature to bypass the tags
  $plugins->add_hook('parse_message_start', 'lock_highlight_start');
  $plugins->add_hook('parse_message', 'lock_highlight_end');

  // adds a new action method to MyBB.
  $plugins->add_hook('global_end', 'lock_purchase');

  // remove hide tags from quotes
  $plugins->add_hook('parse_quoted_message', 'lock_quoted');

  // validate maximum cost
  $plugins->add_hook('datahandler_post_validate_post', array('Shortcodes', 'validate_post'));
  $plugins->add_hook('datahandler_post_validate_thread', array('Shortcodes', 'validate_post'));
}
else
{
  $plugins->add_hook('admin_formcontainer_end', 'lock_admin_formcontainer_end');
  $plugins->add_hook('admin_user_groups_edit_commit', 'lock_admin_user_groups_edit_commit');
}

if(!empty($mybb->input['highlight']))
{
  $highlight_replacement = null;
}

if(!class_exists('Shortcodes'))
{
  require __DIR__ . '/lock/shortcodes.class.php';
}

function lock_activate()
{
  global $db, $PL, $lang;
  lock_deactivate();

  require_once __DIR__ . '/lock/core/install.php';
}

function lock_deactivate()
{
  global $PL, $lang;

  isset($lang->lock) || $lang->load('lock');

  $info = lock_info();

  if(file_exists(PLUGINLIBRARY))
  {
    $PL or require_once PLUGINLIBRARY;
  }

  if(!(file_exists(PLUGINLIBRARY) && $PL->version >= $info['pl']))
  {
    flash_message($lang->sprintf($lang->lock_pluginlibrary, $info['pl']['url'], $info['pl']), 'error');
		admin_redirect('index.php?module=config-plugins');
  }
}

function lock_uninstall()
{
  global $db, $PL;

  require_once __DIR__ . '/lock/core/uninstall.php';
}

function lock_is_installed()
{
  global $db;

  return $db->field_exists('unlocked', 'posts');
}

function lock_highlight_start($message) {
  global $mybb, $replacement;

  if(!empty($mybb->input['highlight'])) {
    $replacement = substr(str_shuffle(str_repeat("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWYXZ", 20)), 0, 20);
    $message = str_replace('hide', $replacement, $message);
  }

  return $message;
}

function lock_highlight_end($message) {
  global $mybb, $replacement;

  if(!empty($mybb->input['highlight'])) {
    $message = str_replace($replacement, 'hide', $message);
  }

  return Shortcodes::parse($message);
}

function lock_purchase() {
  global $_POST, $mybb, $db;

  require_once __DIR__ . '/lock/core/purchase.php';
}

require_once __DIR__ . '/lock/core/shortcode.php';

function lock_quoted(&$quoted_post) {
  Shortcodes::set_tag();
  $quoted_post['message'] = preg_replace("#\[".Shortcodes::$tag."(.*)\[/".Shortcodes::$tag."\]#is",'', $quoted_post['message']);
}

// Hook: admin_formcontainer_end
function lock_admin_formcontainer_end()
{
  global $run_module, $form_container, $lang;

  if($run_module == 'user' && isset($form_container->_title) && $form_container->_title == $lang->users_permissions)
  {
    global $form, $mybb;

    isset($lang->lock) || $lang->load('lock');

    $perms = array();

    $db_fields = lock_get_db_fields();

    foreach($db_fields['usergroups'] as $name => $definition)
    {
      $perms[] = "<br />{$lang->lock_permission_maxcost}<br /><small>{$lang->lock_permission_maxcost_desc}</small><br />{$form->generate_text_box($name, $mybb->get_input($name, MyBB::INPUT_STRING), array('id' => $name, 'class' => 'field50'))}";
    }

    $form_container->output_row($lang->setting_group_lock, '', '<div class="group_settings_bit">'.implode('</div><div class="group_settings_bit">', $perms).'</div>');
  }
}

// Hook: admin_user_groups_edit_commit
function lock_admin_user_groups_edit_commit()
{
  global $updated_group, $mybb;

  $db_fields = lock_get_db_fields();

  foreach($db_fields['usergroups'] as $name => $definition)
  {
    $updated_group[$name] = $mybb->get_input($name, MyBB::INPUT_STRING);
  }
}

function lock_get_db_fields()
{
  global $db;

  // Create DB table
  switch($db->type)
  {
    case 'pgsql':
      $fields = array(
        'usergroups'	=> array(
          'lock_maxcost'		=> "VARCHAR(5) NOT NULL DEFAULT ''",
        )
      );
      break;
    default:
      $fields = array(
        'usergroups'	=> array(
          'lock_maxcost'		=> "VARCHAR(5) NOT NULL DEFAULT ''",
        )
      );
      break;
  }

  return $fields;
}