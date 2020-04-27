<?php

lock_deactivate();

// Delete settings
$PL->settings_delete('lock');

// remove the unlocked column from the posts table.
!$db->field_exists('unlocked', 'posts') or $db->drop_column('posts', 'unlocked');

// Delete template group
$PL->templates_delete('lock');

// Remove DB fields
foreach(lock_get_db_fields() as $table => $fields)
{
  foreach($fields as $name => $definition)
  {
    if($db->field_exists($name, $table))
    {
      $db->drop_column($table, $name);
    }
  }
}