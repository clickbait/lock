<?php

function lock_hide($params, $content) {
  global $mybb, $post, $templates, $db;

  // if the tag has no content, do nothing.
	if (!$content) {
		return false;
	}

  // return nothing if the print thread page is viewed
  if(empty($post['pid'])) {
    return 'Hidden Content';
  }

  // does the user have to pay for the content?
  if($mybb->settings['lock_purchases_enabled'] == true || (Int)$mybb->settings['lock_default_price'] > 0) {

    // is the pay to view feature allowed in this forum?
    $disabled = explode(',', $mybb->settings['lock_disabled_forums']);
    if(!in_array($post['fid'], $disabled)) {

      // does the content have a price? can the user set the price?
      if(!isset($params['cost']) || !(Bool)$mybb->settings['lock_allow_user_prices']) {

        // if not, do we have a default price?
        if($mybb->settings['lock_default_price'] > 0) {
          $params['cost'] = $mybb->settings['lock_default_price'];
        } else {
          $params['cost'] = null;
        }

      }

      // is the cost an actual number?
      if(is_numeric($params['cost'])) {

        // cost must be valid, because numbers aren't evil.
        $cost = $params['cost'];

        // check to see whether the user hasn't already unlocked the content.
        $allowed = explode(',', $post['unlocked']);
        if(in_array($mybb->user['uid'], $allowed)) {
          $paid = true;
        }

      }

    }

  }

  if(!isset($cost)) {
    // if there's no cost, this must be a "post to view" hide tag

    // check to see whether the user has posted in this thread.
    $query = $db->simple_select('posts', '*', "tid = '{$post['tid']}' AND uid = '{$mybb->user['uid']}'");

    if($db->num_rows($query)) {
      $posted = true;
    }
  }

  // if no title has been set, set a default title.
  if(!isset($params['title'])) {
    $params['title'] = "Hidden Content";
  }

  // if the user is not the OP, and has not been exempt from having hidden content
  if(
    $mybb->user['uid'] != $post['uid'] &&
    !in_array($mybb->user['usergroup'], explode(',', $mybb->settings['lock_exempt']))
  ) {

      // if the user isn't logged in, tell them to login or register.
      if($mybb->user['uid'] == 0) {

        $return = "You must <a href=\"{$mybb->settings['bburl']}/member.php?action=register\">register</a> or <a href=\"{$mybb->settings['bburl']}/member.php?action=login\">login</a> to view this content.";

      // if they are logged in, but the item has a price that they haven't paid yet, tell them how they can pay for it.
      } elseif(isset($cost) && !$paid) {

        // include the pcrypt class, so we can encrypt our data; to keep it safe from spookys.
        require_once __DIR__ . '/../pcrypt.php';

        $key = $mybb->settings['lock_key'];

        $pcrypt = new pcrypt(MODE_ECB, "BLOWFISH", $key);

        // place the info we need, into an array
        $info = array(
          'pid' => $post['pid'],
          'cost' => $cost
        );

        // encode the information as json, for safe transit
        $info = json_encode($info);

        // encrypt the json, and encode it as base64; so it can be submitted in a form.
        $info = base64_encode($pcrypt->encrypt($info));

        // build the return button.
        $return = "<form method=\"post\">
          <button type=\"submit\">Unlock for {$cost} points.</button>
          <input type=\"hidden\" name=\"info\" value=\"{$info}\" />
          <input type=\"hidden\" name=\"action\" value=\"purchase\" />
        </form>";

      // if the user doesn't need to pay, but hasn't posted

      } elseif(!$paid && !$posted) {

        // tell them to reply to the thread.
        $return = "You must reply to this thread to view this content.";

      // all is good.
      } else {

        // give them the content.
        $return = $content;

      }

  // bypass the hide tags.
  } else {

    // give them the content
    $return = $content;

  }

  eval("\$return = \"".$templates->get("lock_wrapper")."\";");

	return $return;

}

// add the hide tag if the shortcodes plugin has been installed.

Shortcodes::add("hide", "lock_hide");
