<?php
/*
Plugin Name: Bouncehelp WP Plugin
Description: Turn more visitors into customers with Bouncehelp.
Version: 2.0
Author: Dmitri Kozhevnikov
Author URI: https://www.linkedin.com/in/dmitrikozhevnikov
License: GPLv2
*/

defined('ABSPATH') or die('No script kiddies please!');

define( 'BOUNCEHELP_PATH', dirname(__FILE__) );
define( 'BOUNCEHELP_FOLDER', 'bouncehelp' );
define( 'BOUNCEHELP_VERSION', '2.0' );
define( 'BOUNCEHELP_AUTH_URL', 'https://bouncehelp.com/api/auth.php' );
define( 'BOUNCEHELP_GET_SITES_URL', 'https://bouncehelp.com/api/get_sites.php' );

// INIT
add_action( 'init', 'bouncehelp_init' );
function bouncehelp_init(){
	require BOUNCEHELP_PATH."/includes/functions.php";
	if( is_admin() )
		require BOUNCEHELP_PATH."/includes/admin.functions.php";
}

?>