//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class bitracker_hook_BitMemberSettingsMenu extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return array_merge_recursive( array (
  'userBar' => 
  array (
    0 => 
    array (
      'selector' => '#elAccountSettingsLink',
      'type' => 'add_after',
      'content' => '				<li class=\'ipsMenu_item\' id=\'elTrackerSettingsLink\' data-menuItem=\'settings\'><a href=\'{url="app=bitracker&module=system&controller=settings" seoTemplate="bitracker_settings"}\' title=\'{lang="edit_tracker_settings"}\'>{lang="menu_tracker_settings"}</a></li>',
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */


}