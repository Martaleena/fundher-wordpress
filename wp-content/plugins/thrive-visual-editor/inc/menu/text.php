<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
} ?>

<div id="tve-text-component" class="tve-component" data-view="Text">
	<div class="text-options action-group">
		<div class="dropdown-header" data-prop="docked">
			<div class="group-description">
				<?php echo __( 'Main Options', 'thrive-cb' ); ?>
			</div>
			<i></i>
		</div>
		<div class="dropdown-content">
			<div class="tve-control" data-view="FontColor"></div>
			<div class="tve-control" data-view="FontFace"></div>
			<div class="tve-control" data-view="FontBackground"></div>
			<!--			<div class="tve-control" data-view="FontWeight"></div>-->
			<div class="tve-control" data-view="TextTransform"></div>
			<div class="tve-control btn-group-light" data-view="ToggleControls"></div>
			<div class="tve-control tcb-text-toggle-element tcb-text-font-size" data-view="FontSize"></div>
			<div class="tve-control tcb-text-toggle-element tcb-text-line-height" data-view="LineHeight"></div>
			<div class="tve-control tcb-text-toggle-element tcb-text-letter-spacing" data-view="LetterSpacing"></div>
			<hr>
			<div class="tcb-text-center">
				<span class="click tcb-text-uppercase clear-format custom-icon" data-fn="clear_formatting">
					<?php echo __( 'Clear all formatting', 'thrive-cb' ); ?>
				</span>
			</div>

			<div class="tve-advanced-controls extend-grey">
				<div class="dropdown-header" data-prop="advanced">
				<span>
					<?php echo __( 'Advanced', 'thrive-cb' ); ?>
				</span>
				</div>

				<div class="dropdown-content clear-top">
					<div class="tve-control" data-key="typefocus" data-initializer="typefocus_control"></div>
				</div>
			</div>
		</div>
	</div>
</div>
