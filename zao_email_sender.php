<?php
/**
 * Plugin Name: Zao Email Sender
 * Plugin URI: http://zao.is
 * Description: Sends chosen emails to clients for various purposes
 * Version: 1.0
 * Author: Liz Karaffa
 * Author URI: http://zao.is
 */

/**
 * Enqueue script
 * @param  string $hook Admin page hook.
 * @return void       
 */
function zao_email_sender_enqueue( $hook ) { 
	global $zes_settings;

	if( $hook != $zes_settings ) {
		return;
	}  

    wp_enqueue_style( 'zes_style', plugins_url( '/css/zes_style.css',  __FILE__ ) );

    wp_enqueue_script( 'zes_js',
        plugins_url( '/js/zao_email_sender.js', __FILE__ ),
        array( 'jquery' )
    );

    $current_user = wp_get_current_user();
    wp_localize_script( 'zes_js', 'zes_vars', array(
		'nonce' => wp_create_nonce( 'zes-nonce' ),
		'user' => $current_user,
	));
}
add_action( 'admin_enqueue_scripts', 'zao_email_sender_enqueue' );

function zao_mail_from( $email ) {
	return "office@zao.is";
}

function zao_mail_from_name( $name ) {
    return "Zao Office";
}


/**
 * Sends email, based on template, with placeholders replaced with dynamic data	
 * @param  string $template Filename of template in /emails folder in plugin
 * @param  string $receiver Email message is being sent to.
 * @param  string $subject  Subject of email
 * 
 * @return boolean          True if successfully sent, false if not.
 */
function zao_send_email( $template, $receiver, $subject, $extra = '' ) {

	add_filter( 'wp_mail_content_type', function() {
		return 'text/html';
	} );

	add_filter( 'wp_mail_from', 'zao_mail_from' );
	add_filter( 'wp_mail_from_name', 'zao_mail_from_name' );

	$template_file = plugin_dir_path( __FILE__ )  . 'emails/' . $template . '.php';
	
	if ( ! file_exists( $template_file ) ) {
		return false;
	}

	if( ! isset( $receiver ) ) {
		return;
	}
	
	ob_start();

	include $template_file;

	$text = ob_get_clean();

	$args = array(
		'name' => $_POST['zes-name'],
		'message' => $_POST['zes-message'],
		'email' => $receiver,
	);

	$text = zao_merge_email_tags( $text, $args );

	$mail = wp_mail( $receiver, $subject, $text );
	
	wp_redirect( admin_url( 'admin.php?page=zao_email_sender' ) );
	exit;
}

//Send the Appropriate email
function zao_create_email() {

	if( isset( $_POST['zes-template'] ) && $_POST['zes-template'] === 'welcome' ) {
		zao_send_email( 'welcome', $_POST['zes-email'], 'Project Start with Zao' );
	} elseif ( isset( $_POST['zes-template'] ) && $_POST['zes-template'] === 'case-study' ) {
		zao_send_email( 'case-study', $_POST['zes-email'], 'Congrats on your successful project!' );
	} elseif ( isset( $_POST['zes-template'] ) && $_POST['zes-template'] === 'case-study-followup' ) {
		zao_send_email( 'case-study-followup', $_POST['zes-email'], 'A friendly reminder' );
	} elseif ( isset( $_POST['zes-template'] ) && $_POST['zes-template'] === 'client-survey' ) {
		zao_send_email( 'client-survey', $_POST['zes-email'], 'Thank you for working with us!' );
	}
}
add_action( 'admin_init', 'zao_create_email' );


/**
 * Replace the merge tags with the actual data.do
 *
 * @param  string $html The HTML markup to do the replacements on.
 * @param  array  $args An array of arguments to use in the tag replacements. Can be 'name', 'message', 'email'.
 * @return string       The modified HTML markup.
 */
function zao_merge_email_tags( $html, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'name' => '',
		'message' => '',
		'email' => '',
	) );

	$has_message = trim( $args['message'] );

	$replacements = array(
		'{first_name}'			=> $args['name'],
		'{custom_message}'		=> $has_message ? $args['message'] : '',
		'{url-case-study}'		=> esc_url( add_query_arg( 'email', urlencode( $args['email'] ), 'http://zao.is/case-study-questionnaire/' ) ),
		'{url-client-survey}' 	=> esc_url( add_query_arg( 'email', urlencode( $args['email'] ), 'http://zao.is/improving/' ) ),
	);
	return str_replace( array_keys( $replacements ), array_values( $replacements ), $html );
}

add_action( 'admin_menu', 'zao_email_sender_menu' );

//HTML formatting for Form
function zao_email_sender_render_page() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	} ?>

	<div class="wrap">
		<h1>Zao Email Sender</h1>
		<p>Easily send boilerplate HTML emails to clients.</p>
		<div class="settings">
			<form id="zes-sender" method="post" action="">
				<label for="zes-email">Receiver&rsquo;s Email</label><br>
				<input id="zes-email" name="zes-email" type="email"/><br> 
				<label for="zes-template">Select the Email Template</label><br>
				<select id="zes-template" name="zes-template">
					<option value="welcome">Welcome Letter</option>
					<option value="client-survey">Client Satisfaction Survey</option>
					<option value="case-study">Case Study</option>
					<option value="case-study-followup">Case Study Followup</option>
				</select><br>
				<div class="zes-name">
					<label for="zes-name">Receiver&rsquo;s First Name</label><br> 
					<input id="zes-name" name="zes-name" type="text"/><br> 
				</div>
				<div class="zes-message">
					<label for="zes-message">Custom Message</label><br> 
					<textarea id="zes-message" name="zes-message"></textarea>
					<p>Suggested message about a project start date or delay. 1-2 sentences.</p> 
				</div>
				<input class="zes-submit button" type="Submit" name="submit" value="Send Email"/>
			</form>
		</div>
		<div class="viewer">
			<h2>Email Previewer</h2>
			<div class="zes-email-template"></div>
		</div>
	</div>

<?php } 

add_action( 'wp_ajax_zes_get_ajax', 'zes_process_ajax' );

function zes_process_ajax() {

	if( !isset( $_POST['zes_nonce'] ) || !wp_verify_nonce( $_POST['zes_nonce'], 'zes-nonce') ) {
		die( 'Permissions check failed' );
	}

	$template = $_POST['zes_template'];

	$template_file = plugin_dir_path( __FILE__ )  . 'emails/' . $template . '.php';	

	$args = array(
		'name' => $_POST['zes_name'],
		'message' => $_POST['zes_message'],
		'email' => $_POST['zes_email'],
	);

	ob_start();

	include_once( $template_file );

	$html = ob_get_clean();
	$html = zao_merge_email_tags( $html, $args );

	echo $html;

	die();
}
