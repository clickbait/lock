<?php

function lock_info()
{
  return array(
    'name' => 'Lock',
    'description' => 'Hide tags on steroids',
    'website' => 'https://mybb.solutions',
    'author' => 'Neko',
    'authorsite' => 'https://mybb.solutions',
    'version' => '1.0',
    'compatibility' => '18*',
  );
}

if(!defined('IN_ADMINCP'))
{
  // keep people from using the highlight feature to bypass the tags
  $plugins->add_hook('parse_message_start', 'lock_highlight_start');
  $plugins->add_hook('parse_message', 'lock_highlight_end');

  // adds a new action method to MyBB.
  $plugins->add_hook('global_intermediate', 'lock_purchase');

  // remove hide tags from quotes
  $plugins->add_hook('parse_quoted_message', 'lock_quoted');
}

if(!empty($mybb->input['highlight']))
{
  $highlight_replacement = null;
}

if(!class_exists('Shortcodes'))
{
  require __DIR__ . '/lock/shortcodes.class.php';
}

function lock_install()
{
  global $db;

  require_once __DIR__ . '/lock/core/install.php';
}

function lock_uninstall()
{
  global $db;

  require_once __DIR__ . '/lock/core/uninstall.php';
}

function lock_is_installed()
{
  global $db;

  // check to see whether we have a settings group
  $query = $db->simple_select("settinggroups", "*", "name='lock'");

  if($db->num_rows($query)) {
    // if there is, return true
    return true;
  }

  return false;
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
  $quoted_post['message'] = preg_replace("#\[hide(.*)\[/hide\]#is",'', $quoted_post['message']);
}



?>
