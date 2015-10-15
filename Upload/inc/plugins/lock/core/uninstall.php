<?php
// fetch the settings group id from the settinggroups table
$query = $db->simple_select("settinggroups", "gid", "name='lock'");
$gid = $db->fetch_field($query, "gid");

// if there's no settings group, stop.
if(!$gid)
{
    return;
}

// remove all lock settings from the database
$db->delete_query("settinggroups", "name='lock'");
$db->delete_query("settings", "gid=$gid");

// remove the unlocked column from the posts table.
$db->query("ALTER TABLE ".TABLE_PREFIX."posts DROP `unlocked`");

// remove the Lock template
$db->delete_query("templates", "title IN('lock_wrapper')");

// rebuild settings
rebuild_settings();
?>
