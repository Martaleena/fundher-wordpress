<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package TCB2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}
$menus        = tve_get_custom_menus();
$wp_templates = tcb_elements()->element_factory( 'menu' )->get_wp_menu_templates();
?>
<h2 class="tcb-modal-title"><?php echo __( 'Setup your menu', 'thrive-cb' ) ?></h2>

<div class="tve-modal-content">
	<div class="group-label grey-text mt-25 mr-20 mb-10">
		<?php echo __( 'Menu source:', 'thrive-cb' ); ?>
	</div>
	<select class="tve-select change" data-fn="changeMenu">
		<option value="custom"><?php echo __( 'Custom', 'thrive-cb' ); ?></option>
		<?php foreach ( $menus as $item ) : ?>
			<option value="<?php echo $item['id'] ?>"><?php echo $item['name'] ?></option>
		<?php endforeach ?>
	</select>
	<p>
		<?php echo __( 'Note: Changing the menu source will reset your current styling of the menu.', 'thrive-cb' ); ?>
		<span class="tve-wp-menu-notice">
			<?php echo __( 'Templates and features are limited for WordPress menus, due to technical constraints.', 'thrive-cb' ); ?>
		</span>
	</p>

	<div class="tve-templates-wrapper tve-templates-container">
		<div class="content-templates" id="menu-cloud-templates"></div>
		<div class="content-templates" id="menu-templates">
			<?php foreach ( $wp_templates as $key => $template ) : ?>
				<div class="template-item">
					<div class="template-name">
						<?php echo $template['name'] ?>
					</div>
					<div class="template-wrapper click" data-id="<?php echo $key ?>" data-hover-effect="<?php echo $template['hover'] ?>" data-dropdown-icon="<?php echo $template['dropdown'] ?>" data-fn="dom_select">
						<div class="template-thumbnail" style="background-image: url('<?php echo $template['image'] ?>')">
						</div>
						<div class="selected"></div>
					</div>
				</div>
			<?php endforeach ?>
		</div>
	</div>
</div>

<div class="control-grid">
	<button type="button" class="tcb-left tve-button text-only tcb-modal-cancel"><?php echo __( 'Cancel', 'thrive-cb' ) ?></button>
	<button type="button" class="tcb-right tve-button medium tcb-modal-save"><?php echo __( 'Done', 'thrive-cb' ) ?></button>
</div>
