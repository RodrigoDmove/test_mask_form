<?php
namespace Mask_Form_Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

class mfe_feedback {

		private $plugin_url     = MFE_PLUGIN_URL;
		private $plugin_version = MFE_VERSION;
		private $plugin_name    = 'Mask Form Elementor';
		private $plugin_slug    = 'mask-form-elementor';
		// private $feedback_url   = 'http://feedback.coolplugins.net/wp-json/coolplugins-feedback/v1/feedback';

	/*
	|-----------------------------------------------------------------|
	|   Use this constructor to fire all actions and filters          |
	|-----------------------------------------------------------------|
	*/
	public function __construct() {
		// $this->plugin_url = plugin_dir_url( $this->plugin_url );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_feedback_scripts' ) );
		add_action( 'admin_head', array( $this, 'show_deactivate_feedback_popup' ) );
		add_action( 'wp_ajax_' . $this->plugin_slug . '_submit_deactivation_response', array( $this, 'submit_deactivation_response' ) );
	}

	/*
	|-----------------------------------------------------------------|
	|   Enqueue all scripts and styles to required page only          |
	|-----------------------------------------------------------------|
	*/
	function enqueue_feedback_scripts() {
		$screen = get_current_screen();
		if ( isset( $screen ) && $screen->id == 'plugins' ) {
			wp_enqueue_script( __NAMESPACE__ . 'feedback-script', $this->plugin_url . 'admin/feedback/js/admin-feedback.js', array( 'jquery' ), $this->plugin_version );
			wp_enqueue_style( 'cool-plugins-feedback-css', $this->plugin_url . 'admin/feedback/css/admin-feedback.css', null, $this->plugin_version );
		}
	}

	/*
	|-----------------------------------------------------------------|
	|   HTML for creating feedback popup form                         |
	|-----------------------------------------------------------------|
	*/
	public function show_deactivate_feedback_popup() {
		$screen = get_current_screen();
		if ( ! isset( $screen ) || $screen->id != 'plugins' ) {
			return;
		}
		$deactivate_reasons = array(
			'didnt_work_as_expected'         => array(
				'title'             => __( 'The plugin didn\'t work as expected.', 'mask-form-elementor' ),
				'input_placeholder' => 'What did you expect?',
			),
			'found_a_better_plugin'          => array(
				'title'             => __( 'I found a better plugin.', 'mask-form-elementor' ),
				'input_placeholder' => __( 'Please share which plugin.', 'mask-form-elementor' ),
			),
			'couldnt_get_the_plugin_to_work' => array(
				'title'             => __( 'The plugin is not working.', 'mask-form-elementor' ),
				'input_placeholder' => 'Please share your issue. So we can fix that for other users.',
			),
			'temporary_deactivation'         => array(
				'title'             => __( 'It\'s a temporary deactivation.', 'mask-form-elementor' ),
				'input_placeholder' => '',
			),
			'other'                          => array(
				'title'             => __( 'Other reason.', 'mask-form-elementor' ),
				'input_placeholder' => __( 'Please share the reason.', 'mask-form-elementor' ),
			),
		);

		?>
		<div id="cool-plugins-feedback-<?php echo esc_attr( $this->plugin_slug ); ?>" class="hide-feedback-popup">
						
			<div class="cp-feedback-wrapper">

			<div class="cp-feedback-header">
				<div class="cp-feedback-title"><?php echo esc_html__( 'Quick Feedback', 'mask-form-elementor' ); ?></div>
				<div class="cp-feedback-title-link">A plugin by <a href="https://coolplugins.net/?utm_source=<?php echo esc_attr( $this->plugin_slug ); ?>_plugin&utm_medium=inside&utm_campaign=coolplugins&utm_content=deactivation_feedback" target="_blank">CoolPlugins.net</a></div>
			</div>

			<div class="cp-feedback-loader">
				<img src="<?php echo esc_url( $this->plugin_url ); ?>admin/feedback/images/cool-plugins-preloader.gif">
			</div>

			<div class="cp-feedback-form-wrapper">
				<div class="cp-feedback-form-title"><?php echo esc_html__( 'If you have a moment, please share the reason for deactivating this plugin.', 'mask-form-elementor' ); ?></div>
				<form class="cp-feedback-form" method="post">
					<?php
					wp_nonce_field( '_cool-plugins_deactivate_feedback_nonce' );
					?>
					<input type="hidden" name="action" value="cool-plugins_deactivate_feedback" />
					
					<?php foreach ( $deactivate_reasons as $reason_key => $reason ) : ?>
						<div class="cp-feedback-input-wrapper">
							<input id="cp-feedback-reason-<?php echo esc_attr( $reason_key ); ?>" class="cp-feedback-input" type="radio" name="reason_key" value="<?php echo esc_attr( $reason_key ); ?>" />
							<label for="cp-feedback-reason-<?php echo esc_attr( $reason_key ); ?>" class="cp-feedback-reason-label"><?php echo esc_html( $reason['title'] ); ?></label>
							<?php if ( ! empty( $reason['input_placeholder'] ) ) : ?>
								<textarea class="cp-feedback-text" type="textarea" name="reason_<?php echo esc_attr( $reason_key ); ?>" placeholder="<?php echo esc_attr( $reason['input_placeholder'] ); ?>"></textarea>
							<?php endif; ?>
							<?php if ( ! empty( $reason['alert'] ) ) : ?>
								<div class="cp-feedback-text"><?php echo esc_html( $reason['alert'] ); ?></div>
							<?php endif; ?>	
						</div>
					<?php endforeach; ?>
					
					<div class="cp-feedback-terms">
					<input class="cp-feedback-terms-input" id="cp-feedback-terms-input" type="checkbox"><label for="cp-feedback-terms-input"><?php echo esc_html__( 'I agree to share my feedback with Cool Plugins, including site URL and admin email, to enable them to address my inquiry.', 'mask-form-elementor' ); ?></label>
					</div>

					<div class="cp-feedback-button-wrapper">
						<a class="cp-feedback-button cp-submit" id="cool-plugin-submitNdeactivate">Submit and Deactivate</a>
						<a class="cp-feedback-button cp-skip" id="cool-plugin-skipNdeactivate">Skip and Deactivate</a>
					</div>
				</form>
			</div>


		   </div>
		</div>
		<?php
	}


	function submit_deactivation_response() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), '_cool-plugins_deactivate_feedback_nonce' ) ) {
			wp_send_json_error();
		} else {
			$reason             = isset( $_POST['reason'] ) ? sanitize_text_field( $_POST['reason'] ) : '';
			$deactivate_reasons = array(
				'didnt_work_as_expected'         => array(
					'title'             => __( 'The plugin didn\'t work as expected', 'mask-form-elementor' ),
					'input_placeholder' => 'What did you expect?',
				),
				'found_a_better_plugin'          => array(
					'title'             => __( 'I found a better plugin', 'mask-form-elementor' ),
					'input_placeholder' => __( 'Please share which plugin.', 'mask-form-elementor' ),
				),
				'couldnt_get_the_plugin_to_work' => array(
					'title'             => __( 'The plugin is not working', 'mask-form-elementor' ),
					'input_placeholder' => 'Please share your issue. So we can fix that for other users.',
				),
				'temporary_deactivation'         => array(
					'title'             => __( 'It\'s a temporary deactivation.', 'mask-form-elementor' ),
					'input_placeholder' => '',
				),
				'other'                          => array(
					'title'             => __( 'Other', 'cool-plugins' ),
					'input_placeholder' => __( 'Please share the reason.', 'mask-form-elementor' ),
				),
			);

			$deativation_reason = array_key_exists( $reason, $deactivate_reasons ) ? $reason : 'other';

			$deativation_reason = esc_html($deativation_reason);
			$sanitized_message = empty( $_POST['message'] ) || sanitize_text_field( $_POST['message'] ) == '' ? 'N/A' : sanitize_text_field( $_POST['message'] );
			$admin_email       = sanitize_email( get_option( 'admin_email' ) );
			$site_url          = esc_url( site_url() );
			$feedback_url      = esc_url( 'http://feedback.coolplugins.net/wp-json/coolplugins-feedback/v1/feedback' );
			$response          = wp_remote_post(
				$feedback_url,
				array(
					'timeout' => 30,
					'body'    => array(
						'plugin_version' => $this->plugin_version,
						'plugin_name'    => $this->plugin_name,
						'reason'         => $deativation_reason,
						'review'         => $sanitized_message,
						'email'          => $admin_email,
						'domain'         => $site_url,
					),
				)
			);

			die( json_encode( array( 'response' => $response ) ) );
		}

	}
}
new mfe_feedback();
