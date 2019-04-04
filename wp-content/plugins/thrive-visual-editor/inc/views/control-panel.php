<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/** @var $data TCB_Editor */
?>

<div id="tve_cpanel">

	<div class="tve-active-element no-states" data-default="<?php echo __( 'No Element Selected', 'thrive-cb' ); ?>">
		<div class="element-name"><?php echo __( 'No Element Selected', 'thrive-cb' ); ?></div>
		<div class="element-states"></div>
	</div>

	<div id="tve-scroll-panel">
		<div class="tve-panel">
			<div class="sidebar-block default-text">
				<p><?php echo __( 'Select or add an element on the<br>canvas in order to activate this sidebar.', 'thrive-cb' ); ?></p>
				<?php if ( tcb_editor()->has_templates_tab() ) : ?>
					<img src="<?php echo tve_editor_css( 'images/sidebar-blank-tpl.png' ); ?>" width="207" height="328"
							srcset="<?php echo tve_editor_css( 'images/sidebar-blank-tpl@2x.png' ); ?> 2x">
				<?php else : ?>
					<img src="<?php echo tve_editor_css( 'images/sidebar-blank.png' ); ?>" width="193" height="326"
							srcset="<?php echo tve_editor_css( 'images/sidebar-blank@2x.png' ); ?> 2x">
				<?php endif; ?>
			</div>
			<div id="tve-components" class="tcb-flex sidebar-block" style="display: none">
				<div id="tcb-drop-panels"></div>
				<?php $data->elements->output_components(); ?>
			</div>

			<div id="migrate-element" class="sidebar-block" style="display: none">
				<span><?php echo __( 'You can migrate this element to Thrive Architect. Click the following button to migrate the element', 'thrive-cb' ); ?></span>
				<button class="click tve-btn tve-button grey" data-fn="migrate_element"><?php echo __( 'Migrate element', 'thrive-cb' ); ?></button>
			</div>

			<div id="multiple-select-elements" class="sidebar-block" style="display: none;">
				<div class="row pt-15">
					<div class="col-xs-12">
						<p><?php echo __( 'Multiple selection mode activated. You can now move the selected elements across the page.', 'thrive-cb' ); ?></p>
					</div>
				</div>
				<div class="row pt-15">
					<div class="col-xs-12 tcb-text-center">
						<button class="click tve-btn tve-button grey"
								data-fn="exit_multiple_selected_mode"><?php echo __( 'Exit mode', 'thrive-cb' ); ?></button>
					</div>
				</div>
			</div>

			<?php /* custom sidebar states for elements */ ?>
			<?php foreach ( $data->elements->custom_sidebars() as $key => $element_sidebar ) : ?>
				<div class="sidebar-block" style="display: none" id="sidebar-<?php echo $key; ?>"
					 data-title="<?php echo esc_attr( $element_sidebar['title'] ); ?>"><?php echo $element_sidebar['template']; ?></div>
			<?php endforeach ?>
		</div>
	</div>

	<div class="tve-settings tcb-relative" id="tcb-editor-settings"><?php tcb_template( 'editor-settings', $data ); ?></div>

	<a href="javascript:void(0)" class="panel-extend click" data-fn="togglePanel" data-title-collapsed="<?php echo __( 'Expand panel', 'thrive-cb' ); ?>"
			data-title-expanded="<?php echo __( 'Collapse panel', 'thrive-cb' ); ?>" title="<?php echo __( 'Collapse panel', 'thrive-cb' ); ?>">
		<?php tcb_icon( 'caret-left-solid' ); ?>
	</a>
</div>
