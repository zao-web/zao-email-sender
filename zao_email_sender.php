<?php
/**
 * Plugin Name: Zao Email Sender
 * Plugin URI: http://zao.is
 * Description: Sends chosen emails to clients for various purposes
 * Version: 1.0
 * Author: Liz Karaffa
 * Author URI: http://zao.is
 */

//Enqueue script
add_action( 'admin_enqueue_scripts', 'zao_email_sender_enqueue' );

function zao_email_sender_enqueue() {   
    wp_enqueue_script( 'zao_js',
        plugins_url( '/js/zao_email_sender.js', __FILE__ ),
        array( 'jquery' )
    );
}

/**
 * Sends email, based on template, with placeholders replaced with dynamic data.
 *
 * @param  string $template  Filename of template in /emails folder in plugin
 * @param  string $user      User message is being sent to.
 * @param  string $subject   Subject
 *
 * @return boolean           True if successfully sent, false if not.
 */
function zao_send_email( $template, $receiver, $subject, $extra = '' ) {

	add_filter( 'wp_mail_content_type', function() {
		return 'text/html';
	} );

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

	$text = zao_merge_email_tags( $text, $receiver );

	$mail = wp_mail( $receiver, $subject, $text );
	
	wp_redirect( admin_url( 'admin.php?page=zao_email_sender' ) );
	exit;
}

//Send the Appropriate email
function zao_create_email() {

	if( isset( $_POST['zes-template'] ) && $_POST['zes-template'] === 'welcome' ) {
		zao_send_email( 'welcome-letter', $_POST['zes-email'], 'Project Start with Zao' );
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
 * [gb_merge_email_tags description]
 *
 * @param  [type] $text [description]
 * @param  [type] $user [description]
 * @return [type]       [description]
 */
function zao_merge_email_tags( $text, $user ) {

	$args = array(
		'{first_name}'	=> $_POST[ 'zes-name' ],
		'{url_query}'	=> esc_url( add_query_arg( 'email', $_POST[ 'zes-email' ], 'http://zao.is/case-study-questionnaire/' ) )
	);
	return str_replace( array_keys( $args ), $args, $text );
}

//Creates the Setting in Admin Menu
function zao_email_sender_menu() {
	add_menu_page( 'Zao Email Sender', 'Send Email', 'manage_options', 'zao_email_sender', 'zao_email_sender_options', 'dashicons-email-alt', '21' );
}

add_action( 'admin_menu', 'zao_email_sender_menu' );

//HTML formatting to Send Email
function zao_email_sender_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	} ?>

	<div class="wrap">
		<h1>Zao Email Sender</h1>
		<p>Easily send boilerplate HTML emails to clients.</p>
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
			<input type="Submit" name="submit" value="Send"/>
		</form>
	</div>

<?php } ?>
