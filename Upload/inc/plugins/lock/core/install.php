<?php

// add a new setting group for Lock
$new_setting_group = array(
  "name" => "lock",
  "title" => "Lock Settings",
  "disporder" => 1,
  "isdefault" => 0
);

$gid = $db->insert_query("settinggroups", $new_setting_group);

$settings[] = array(
  "name" => "lock_key",
  "title" => "Key",
  "description" => "A password to keep spooky people from editing values they shouldn\'t be.",
  "optionscode" => "text",
  "disporder" => 1,
  "value" => substr(str_shuffle(str_repeat("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWYXZ", 20)), 0, 20),
  "gid" => $gid
);

$settings[] = array(
  "name" => "lock_purchases_enabled",
  "title" => "Enable Newpoints purchases.",
  "description" => "Allow users to sell locked content for newpoints credits.",
  "optionscode" => "yesno",
  "disporder" => 2,
  "value" => 1,
  "gid" => $gid
);

$settings[] = array(
  "name" => "lock_allow_user_prices",
  "title" => "Allow user prices.",
  "description" => "Do you want to let users set the price of their content?",
  "optionscode" => "yesno",
  "disporder" => 2,
  "value" => 1,
  "gid" => $gid
);

$settings[] = array(
  "name" => "lock_default_price",
  "title" => "Default price.",
  "description" => "The default price for hidden content. Set this to zero if you want hide tags to fall back to the \"Reply to view\" mode.",
  "optionscode" => "text",
  "disporder" => 3,
  "value" => '0',
  "gid" => $gid
);

$settings[] = array(
  "name" => "lock_tax",
  "title" => "Tax",
  "description" => "Tax a percentage of the points every user spends on content (up to 100%).",
  "optionscode" => "text",
  "disporder" => 3,
  "value" => '10',
  "gid" => $gid
);

$settings[] = array(
  "name" => "lock_exempt",
  "title" => "Excempt usergroups.",
  "description" => "Enter a comma seperated list of usergroups that can bypass the hide tags.",
  "optionscode" => "text",
  "disporder" => 3,
  "value" => '3,4',
  "gid" => $gid
);

$settings[] = array(
  "name" => "lock_disabled_forums",
  "title" => "Disabled forums.",
  "description" => "Enter a comma seperated list of forum IDs that you do not want the \"pay to view\" functionality to work in.",
  "optionscode" => "text",
  "disporder" => 3,
  "value" => '',
  "gid" => $gid
);

// add the settings into the database.
foreach($settings as $data)
{
    $db->insert_query("settings", $data);
}

// add a new colum to the posts table.
$db->write_query("ALTER TABLE `".TABLE_PREFIX."posts` ADD `unlocked` TEXT AFTER `visible`");

// add a template for the hide tag
$templates['lock_wrapper'] = '<div class="hidden-content">
  <div class="hidden-content-title"><strong>{$params[\'title\']}</strong></div>
  <div class="hidden-content-body">
    {$return}
  </div>
</div>';

// insert the template into the database
foreach($templates as $title => $template)
{
  $new_template = array(
    'title' => $db->escape_string($title),
    'template' => $db->escape_string($template),
    'sid' => '-1',
    'version' => '1800',
    'dateline' => TIME_NOW
  );

  $db->insert_query('templates', $new_template);
}

// rebuild settings
rebuild_settings();
?>
