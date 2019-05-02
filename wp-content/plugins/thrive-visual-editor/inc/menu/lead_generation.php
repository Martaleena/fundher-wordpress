<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package TCB2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}
?>
<div id="tve-lead_generation-component" class="tve-component" data-view="LeadGeneration">
	<div class="dropdown-header" data-prop="docked">
		<?php echo __( 'Main Options', 'thrive-cb' ); ?>
		<i></i>
	</div>
	<div class="dropdown-content">

		<div class="state-edit-mode" style="display: none; text-align: center">
			<span class="grey-text">
				<?php echo __( 'You are now in Form Components Mode.', 'thrive-cb' ); ?><br>
				<?php echo __( 'Select a form component to edit it.', 'thrive-cb' ); ?>
			</span>
			<button class="mt-15 click tve-btn tve-button grey long"
			        data-fn="f:main.EditMode.exit"><?php echo __( 'Exit Edit Mode', 'thrive-cb' ); ?></button>
		</div>

		<div class="state-default-mode">
			<div class="no-api tve-control api-connections-list" data-view="ApiConnections"></div>

			<div class="no-api tcb-text-center mb-10 mr-5 ml-5">
				<button class="tve-button orange click" data-fn="edit_form_elements">
					<?php echo __( 'Edit Form Elements', 'thrive-cb' ); ?>
				</button>
			</div>

			<hr>

			<div class="tve-control skip-api no-service" data-view="Consent"></div>

			<div class="tve-control" data-view="Captcha"></div>
			<a class="tcb-hidden info-link toggle-control mb-5" target="_blank" href="<?php echo admin_url( 'admin.php?page=tve_dash_api_connect' ); ?>">
				<span class="info-text"><?php echo __( 'Requires integration with Google ReCaptcha', 'thrive-cb' ); ?></span>
			</a>

			<div id="tcb-lg-captcha-controls" class="control-grid tcb-hidden">
				<div class="full-width">
					<div class="tve-control" data-view="CaptchaTheme"></div>
				</div>
				<div class="full-width">
					<div class="tve-control pt-10" data-view="CaptchaType"></div>
				</div>
				<div class="full-width">
					<div class="tve-control pt-10" data-view="CaptchaSize"></div>
				</div>
			</div>

			<?php do_action( 'tcb_lead_generation_menu' ); ?>

			<div class="no-api tve-advanced-controls extend-grey">
				<div class="dropdown-header" data-prop="advanced">
					<span>
						<?php echo __( 'Advanced', 'thrive-cb' ); ?>
					</span>
				</div>
				<div class="dropdown-content clear-top">
					<div class="no-api tcb-text-center">
						<button class="tve-button blue long click" data-fn="manage_error_messages">
							<?php echo __( 'Edit error messages', 'thrive-cb' ); ?>
						</button>
						<button class="tve-button click long blue multiple-services-connect mt-10" data-fn="mServiceConnect">
							<?php echo __( 'Connect to multiple services', 'thrive-cb' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
