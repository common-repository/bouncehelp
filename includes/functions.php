<?
defined('ABSPATH') or die('No script kiddies please!');

function bouncehelp_get_code($key){
	$options = get_option('bouncehelp_data');
	if(!empty($key) && !empty($options['show']) && $options['show'] == 1) return "<script>
(function() { var bh = document.createElement('script'); bh.type = 'text/javascript'; bh.async = true;
bh.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'code.bouncehelp.com/".$key."/bh.min.js';
var bs = document.getElementsByTagName('script')[0];bs.parentNode.insertBefore(bh, bs);})();
</script>";
	return false;
}

function bouncehelp_add_code_to_footer(){
	if( $key = get_option('bouncehelp_api_sid') ) echo bouncehelp_get_code($key);
	else echo '';
}
add_action( 'wp_footer', 'bouncehelp_add_code_to_footer', 99 );

?>