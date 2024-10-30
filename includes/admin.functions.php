<?
defined('ABSPATH') or die('No script kiddies please!');

add_action('admin_menu', 'add_plugin_page');
function add_plugin_page(){
	add_options_page( 'Settings BounceHelp', 'BounceHelp', 'manage_options', 'bouncehelp_slug', 'bouncehelp_options_page_output' );
}

function bouncehelp_options_page_output(){
	$key = get_option('bouncehelp_api_sid');
	if(empty($key)) $attr = array('disabled' => 'disabled' ); else $attr = array();
	?>
	<div class="wrap">
		<h2><?php echo get_admin_page_title() ?></h2>

		<form action="options.php" method="POST" id="bouncehelp_auth_form">
			<?php
				settings_fields( 'bouncehelp_option_group' );
				do_settings_sections( 'bouncehelp_page' );
				submit_button(null, 'primary', 'submit', true, $attr);
			?>
		</form>
	</div>
<script type="text/javascript">
jQuery( function ( $ ) {
$(document).ready(function(){
	$('input#bh_login, input#bh_pass').change(function(){
		var bhLogin = $('input#bh_login').val(), bhPass = $('input#bh_pass').val(); bhRes = $('#bh_result'), bhBtn = $('#bouncehelp_auth_form #submit');
		bhRes.html('<img src="images/loading.gif" style="margin:0 5px; height:14px;">  Loading...').css('color','#666');
		if(bhLogin && bhPass){
			var data = {action:'bouncehelp_auth', login:bhLogin, password:bhPass};	
			jQuery.post( ajaxurl, data, function(response) {
				if(response.status == 'error') { bhRes.html(response.message).css('color', 'red'); bhBtn.attr('disabled','disabled'); }
				if(response.status == 'success') { bhRes.html(response.message).css('color', 'green'); bhBtn.removeAttr('disabled'); }
			}, 'json');			
		}
	});
});
});
</script>
	<?php
}

add_action('admin_init', 'bouncehelp_plugin_settings');
function bouncehelp_plugin_settings(){
	register_setting( 'bouncehelp_option_group', 'bouncehelp_data', 'bouncehelp_sanitize_callback' );

	add_settings_section( 'bouncehelp_section', '', '', 'bouncehelp_page' ); 

	add_settings_field('login', 'Login', 'bouncehelp_fill_field_login', 'bouncehelp_page', 'bouncehelp_section' );
	add_settings_field('password', 'Password', 'bouncehelp_fill_field_password', 'bouncehelp_page', 'bouncehelp_section' );
	add_settings_field('show', 'On/Off', 'bouncehelp_fill_field_show', 'bouncehelp_page', 'bouncehelp_section' );
}

function bouncehelp_fill_field_login(){
	$val = get_option('bouncehelp_data');
	$val = $val['login'];
	?>
	<input type="text" class="regular-text" id="bh_login" name="bouncehelp_data[login]" value="<?php echo esc_attr( $val ) ?>" />
	<p class="description">Enter your login on BounceHelp</p>
	<?php
}

function bouncehelp_fill_field_password(){
	$val = get_option('bouncehelp_data');
	$val = $val['password'];
	?>
	<input type="password" class="regular-text" id="bh_pass" name="bouncehelp_data[password]" value="<?php echo esc_attr( $val ) ?>" />
	<p class="description">Enter your password on BounceHelp</p>
	<?php
}

function bouncehelp_fill_field_show(){
	$val = get_option('bouncehelp_data');
	$val = $val['show'];
	?>
	<p class="description"><input type="checkbox" name="bouncehelp_data[show]" value="1" <?php checked( 1, $val ) ?> /> <span class="dashicons dashicons-warning" style="margin-left:20px; font-size:18px; padding-top:3px;"></span> <strong>Authorization result:</strong> <span id="bh_result">Enter login and password</span></p>
	<p class="description">Enable/Disable code BounceHelp</p>
	<?php
}

function bouncehelp_sanitize_callback( $options ){ 
	foreach( $options as $name => & $val ){
		if( $name == 'login' || $name == 'password' )
			$val = strip_tags( $val );

		if( $name == 'show' )
			$val = intval( $val );
	}

	return $options;
}

add_action('wp_ajax_bouncehelp_auth', 'bouncehelp_auth_callback');
function bouncehelp_auth_callback() {

if(!empty($_POST['login']) && !empty($_POST['password'])) {
	$site_url = get_site_url();
	$data = array(
	  'login'=>$_POST['login'],
	  'password'=>$_POST['password'],
	  'website'=>$site_url,
	  'plugin_version'=>'wp_'.BOUNCEHELP_VERSION
	);
	
	$result = bouncehelp_send_curl(BOUNCEHELP_AUTH_URL, $data);
	
	$resultArr = json_decode($result);
	if($resultArr->status == 'success'){
		if(!empty($resultArr->API_SID)) {
			$sitesResult = bouncehelp_send_curl(BOUNCEHELP_GET_SITES_URL, array('API_SID'=>$resultArr->API_SID));
			$sitesResult = json_decode($sitesResult, true);
			
			if($sitesResult['status'] == 'success'){
			
			if(!empty($sitesResult['websites'])){
				$site = ''; $hash = '';
			
				foreach($sitesResult['websites'] as $k=>$site){
					if(strpos($site['name'], str_replace('www.', '', $_SERVER['HTTP_HOST']))){
						update_option( 'bouncehelp_api_sid', $site["hash"] );
						$site = $site['name'];
					}
					else continue;
				}
				
				if(!empty($site)) {
					
					$result = json_encode(array("status"=>"success","message"=>"You have successfully signed. The website is successfully connected."));
					
				} else $result = json_encode(array("status"=>"error","error"=>"10003","message"=>"Please, add this website in your account on bouncehelp.com"));
			} else $result = json_encode(array("status"=>"error","error"=>"10002","message"=>"In your account on bouncehelp.com there is no sites. Please, add this website."));
			
			} else $result = $sitesResult;
			
		}
	}
	
	echo $result;
} 

else echo json_encode(array("status"=>"error","error"=>"10000","message"=>"You have not entered login or password"));

	wp_die();
}

add_action('wp_ajax_bouncehelp_get_hash', 'bouncehelp_get_hash_callback');
function bouncehelp_get_hash_callback() {

if(!empty($_POST['login']) && !empty($_POST['password'])) {
	$site_url = get_site_url();
	$data = array(
	  'login'=>$_POST['login'],
	  'password'=>$_POST['password'],
	  'website'=>$site_url,
	  'plugin_version'=>'wp_'.BOUNCEHELP_VERSION
	);
	
	$result = bouncehelp_send_curl(BOUNCEHELP_AUTH_URL, $data);
	
	$resultArr = json_decode($result);
	if($resultArr->status == 'success'){
		if(!empty($resultArr->API_SID)) {
			$sitesResult = bouncehelp_send_curl(BOUNCEHELP_GET_SITES_URL, $resultArr->API_SID);
			update_option( 'bouncehelp_api_sid', $resultArr->API_SID );
		}
	}
	var_dump( $sitesResult );
	echo $sitesResult;
	//echo $result;
} 

else echo json_encode(array("status"=>"error","error"=>"10000","message"=>"You have not entered login or password"));

	wp_die();
}

function bouncehelp_send_curl($url, $data){	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url );
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}


?>