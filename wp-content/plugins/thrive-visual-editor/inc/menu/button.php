<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
} ?>

<div id="tve-button-component" class="tve-component" data-view="Button">
	<div class="text-options action-group">
		<div class="dropdown-header" data-prop="docked">
			<div class="group-description">
				<?php echo __( 'Main Options', 'thrive-cb' ); ?>
			</div>
			<i></i>
		</div>
		<div class="dropdown-content">
			<div class="tve-control gl-st-button-toggle-1" data-view="MasterColor"></div>
			<div class="master_color_warning global-edit-warning tcb-hide">
				<?php echo __( 'Changing the Master Color will unlink the element from any global color/gradient which was applied on it previously.', 'thrive-cb' ); ?>
			</div>
			<div class="hide-states">
				<div class="tve-control" data-view="SecondaryText"></div>
				<div class="tve-control" data-view="ButtonIcon"></div>
			</div>
			<div class="tcb-button-icon-controls tcb-hidden hide-states">
				<div class="tve-control tcb-icon-side-wrapper" data-key="icon_side" data-view="ButtonGroup"></div>
			</div>

			<div class="hide-states padding-4">
				<div class="tve-control gl-st-button-toggle-1 no-space sep-top" data-key="ButtonSize" data-view="ButtonGroup"></div>
				<div class="tve-control pt-5 gl-st-button-toggle-2" data-key="Align" data-view="ButtonGroup"></div>
				<div class="tve-control gl-st-button-toggle-2" data-view="ButtonWidth"></div>
				<hr class="tcb-button-link-container-divider">
				<div class="control-grid tcb-button-link-container no-space">
					<span class="grey-text"><?php echo __( 'Button Link', 'thrive-cb' ); ?></span>
					<span class="click grey-text" data-fn="open_button_search_settings"><?php tcb_icon( 'settings' ); ?></span>
				</div>
				<div class="control-grid no-space">
					<div id="tcb-button-link-search-control" class="fill pt-5"></div>
				</div>
				<div class="tcb-flex space-between tcb-button-link-options-container">
					<div class="tve-control" data-view="LinkNewTab"></div>
					<div class="tve-control" data-view="LinkNoFollow"></div>
				</div>
			</div>
		</div>
	</div>
</div>
