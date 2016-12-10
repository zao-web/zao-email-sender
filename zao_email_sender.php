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
function zao_send_email( $template, $receiver, $subject ) {

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

/**
 * Creates the appropriate email based on what template has be selected by the user. 
 * @return [type] [description]
 */
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
 * Replace the merge tags with the actual data.
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

/**
 * Creates the UI in admin for the plugin
 * @return [type] [description]
 */
function zao_email_sender_menu() {
	global $zes_settings;
	$zes_settings = add_menu_page( 'Zao Email Sender', 'Send Email', 'manage_options', 'zao_email_sender', 'zao_email_sender_render_page', 'dashicons-email-alt', '21' );
}

add_action( 'admin_menu', 'zao_email_sender_menu' );

function zes_options_menu() {
	add_submenu_page( 'zao_email_sender', 'Zao Email Sender Settings', 'Settings', 'manage_options', 'zes_settings', 'zao_email_sender_render_page' );
}
add_action( 'admin_menu', 'zes_options_menu' );

/**
 * HTML to render the plugin page
 * @return [type] [description]
 */
function zao_email_sender_render_page() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	} ?>

	<div class="wrap">
		<h1>Zao Email Sender</h1>
		<p>Easily send boilerplate HTML emails to clients.</p>
		<?php settings_errors(); ?>

		<?php zes_nav_tab_html(); ?>
		
		<div class="zes-tab-page-wrapper"> 

			<?php zes_send_email_html(); ?>

			<?php zes_settings_html(); ?>
			
		</div>
	</div>

<?php } 

function zes_nav_tab_html() {
	$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'send_email'; ?>

	<h2 class="nav-tab-wrapper">
	    <a href="?page=zao_email_sender&tab=send_email" class="nav-tab <?php echo $active_tab == 'send_email' ? 'nav-tab-active' : ''; ?>">Send Email</a>
	    <a href="?page=zao_email_sender&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
	</h2>
<?php }

function zes_send_email_html() { ?>

	<div class="zes-email-form">
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
	<div class="zes-viewer">
		<h2>Email Previewer</h2>
		<div class="zes-email-template"></div>
	</div>

<?php }

function zes_settings_html() { ?>
	<div class="zes-options">
		<form method="post" action="options.php">
            <?php settings_fields( 'general' ); ?>
            <?php do_settings_sections( 'general' ); ?>           
            <?php submit_button(); ?>
        </form>
	</div>
<?php }

function zes_initialize_plugin_settings() {
	if( false == get_option( 'zao_email_sender_render_page' ) ) {  
	    add_option( 'zao_email_sender_render_page' );
	} 
	// First, we register a section. This is necessary since all future options must belong to one.
    add_settings_section(
        'settings_section',         // ID used to identify this section and with which to register options
        'Settings',                  // Title to be displayed on the administration page
        'zes_settings_callback', // Callback used to render the description of the section
        'general'         // Page on which to add this section of options
    );
    // Next, we will introduce the fields.
	add_settings_field( 
	    'from_email',                      // ID used to identify the field throughout the theme
	    'From Email',                           // The label to the left of the option interface element
	    'zes_from_email_callback',   // The name of the function responsible for rendering the option interface
	    'general',                          // The page on which this option will be displayed
	    'settings_section',         // The name of the section to which this field belongs
	    array(                              // The array of arguments to pass to the callback. In this case, just a description.
	        'Enter the email address you want emails to be from (i.e. me@email.com). Defaults to admin email.'
	    )
	);
	add_settings_field( 
	    'from_name',                      // ID used to identify the field throughout the theme
	    'From  Name',                           // The label to the left of the option interface element
	    'zes_from_name_callback',   // The name of the function responsible for rendering the option interface
	    'general',                          // The page on which this option will be displayed
	    'settings_section',         // The name of the section to which this field belongs
	    array(                              // The array of arguments to pass to the callback. In this case, just a description.
	        'Enter the name you want emails to be from (i.e. John Doe). Defaults to admin name.'
	    )
	);

	// Finally, we register the fields with WordPress
	register_setting(
	    'settings_section',
	    'from_email'
	);
	register_setting(
	    'settings_section',
	    'from_name'
	);
}
add_action('admin_init', 'zes_initialize_plugin_settings');


/**
 * This function provides a simple description for the General Options page. 
 *
 * It is called from the 'sandbox_initialize_theme_options' function by being passed as a parameter
 * in the add_settings_section function.
 */
function zes_settings_callback() {
    echo '<p>General settings for the Zao Email Sender Plugin</p>';
}

/**
 * This function renders the interface elements for toggling the visibility of the header element.
 * 
 * It accepts an array of arguments and expects the first element in the array to be the description
 * to be displayed next to the checkbox.
 */
function zes_from_email_callback($args) {

	// Here, we will take the first argument of the array and add it to a label next to the checkbox
    $html = '<label for="from_email"> '  . $args[0] . '</label><br>'; 
     
    // Note the ID and the name attribute of the element match that of the ID in the call to add_settings_field
    $html .= '<input type="text" id="from_email" name="from_email" />'; 
    echo $html;
     
}

/**
 * This function renders the interface elements for toggling the visibility of the header element.
 * 
 * It accepts an array of arguments and expects the first element in the array to be the description
 * to be displayed next to the checkbox.
 */
function zes_from_name_callback($args) {

	// Here, we will take the first argument of the array and add it to a label next to the checkbox
    $html = '<label for="from_name"> '  . $args[0] . '</label><br>'; 
     
    // Note the ID and the name attribute of the element match that of the ID in the call to add_settings_field
    $html .= '<input type="text" id="from_name" name="from_name" />'; 
    echo $html;
     
}

add_action( 'wp_ajax_zes_get_ajax', 'zes_process_ajax' );

/**
 * Uses AJAX to preview the email with it's dynamic content before sending it.
 * @return [type] [description]
 */
function zes_process_ajax() {

	if( !isset( $_POST['zes_nonce'] ) || !wp_verify_nonce( $_POST['zes_nonce'], 'zes-nonce') ) {
		die( 'Permissions check failed' );
	}

	$template = $_POST['zes_template'];

	$template_file = plugin_dir_path( __FILE__ )  . 'emails/' . $template . '.php';	

	$args = array(
		'name' => $_POST['zes_name'],
		'message' => wp_unslash( $_POST['zes_message'] ),
		'email' => $_POST['zes_email'],
	);

	ob_start();

	include_once( $template_file );

	$html = ob_get_clean();
	$html = zao_merge_email_tags( $html, $args );

	echo $html;

	die();
}
