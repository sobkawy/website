<?php
	//Determine if user is using secure connection (https) or not. This will ensure everything is using a secure connection, if they are
	if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) { $secure = "s"; }
	define(SITE_NAME, "s&igrave;loz");
	define(TAG_LINE, "s&igrave;loz - Comerce That Counts");
	define(DB_USERNAME, "admin");
	define(DB_PASSWORD, "99Luftbal00ns!");
	define(DB_HOST, "localhost");
	define(DB_NAME, "guest1_siloz");
	define(ABS_PATH, "/var/www/vhosts/stage.guest1.siloz.com/httpdocs/");
	define(ACTIVE_URL, "http".$secure."://stage.guest1.siloz.com/website/"); // **Don't forget slash at end** //
	define(API_URL, "/website/api.php");
	define(SHORT_URL, "siloz.com");
	define(FLAG_KILL, "off"); // **Disable silos/items with too many flags - on/off** //
	define(FAM_INDEX_KILL, "off"); // **Disable silos/items with low fam index scores - on/off** //
	define(FACEBOOK_ID, "291023471032128");
	define(OPENINVITER_KEY, "848c26c450b38b89fc9de61013811701");
	// for error reporting un comment the next to lines
	//error_reporting(E_ALL);
	//ini_set('display_errors', true);
?>
