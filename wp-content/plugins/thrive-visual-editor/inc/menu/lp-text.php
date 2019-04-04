<?php
/**
 * Created by PhpStorm.
 * User: Ovidiu
 * Date: 10/24/2017
 * Time: 4:06 PM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}
?>

<div id="tve-lp-text-typography-component" class="tve-component" data-view="LpTextTypography">
	<div class="dropdown-header" data-prop="docked">
		<div class="group-description"><?php echo __( 'Typography', 'thrive-cb' ) ?></div>
		<i></i>
	</div>
	<div class="dropdown-content">
		<div class="tve-control lp-text-hide-states" data-view="FontFace"></div>
		<div class="tve-control" data-view="FontColor"></div>
		<div class="tve-control tcb-hidden" data-view="ButtonGroup" data-key="LinkStates"></div>
		<div class="tve-control lp-text-hide-states" data-view="TextTransform"></div>
		<div class="tve-control lp-text-hide-states" data-view="TextAlign"></div>
		<div class="tve-control lp-text-hide-states btn-group-light" data-view="ToggleControls"></div>
		<div class="lp-text-hide-states">
			<div class="tve-control tcb-lp-text-toggle-element tcb-lp-text-font-size" data-view="FontSize"></div>
			<div class="tve-control tcb-lp-text-toggle-element tcb-lp-text-line-height" data-view="LineHeight"></div>
			<div class="tve-control tcb-lp-text-toggle-element tcb-lp-text-letter-spacing" data-view="LetterSpacing"></div>
		</div>
		<hr>
		<div class="tcb-text-center mt-10">
			<span class="click tcb-text-uppercase clear-format custom-icon" data-fn="clear_landing_page_text_formatting">
				<?php echo __( 'Clear formatting', 'thrive-cb' ) ?>
			</span>
		</div>
	</div>
</div>

<div id="tve-lp-text-layout-component" class="tve-component" data-view="LpTextLayout"></div>

<div id="tve-lp-text-background-component" class="tve-component" data-view="LpTextBackground">
	<div class="dropdown-header" data-prop="docked">
		<div class="group-description"><?php echo __( 'Background Style', 'thrive-cb' ) ?></div>
		<i></i>
	</div>
	<div class="dropdown-content">
		<div class="gradient-layers"></div>
		<div class="tve-control" data-key="PreviewFilterList" data-view="PreviewList"></div>
		<div class="tve-control" data-view="PreviewList"></div>
		<div class="v-sep"></div>
		<div class="tve-control" data-view="ColorPicker" data-show-gradient="0"></div>
		<div class="tve-control video-bg" data-key="video" data-initializer="video"></div>
	</div>
</div>

<div id="tve-lp-text-borders-component" class="tve-component" data-view="Borders">
	<div class="borders-options action-group">
		<div class="dropdown-header" data-prop="docked">
			<div class="group-description">
				<?php echo __( 'Borders & Corners', 'thrive-cb' ); ?>
			</div>
			<i></i>
		</div>
		<div class="dropdown-content">
			<div class="tve-control" data-view="LpTextBorders"></div>
			<hr>
			<div class="tve-control" data-view="LpTextCorners"></div>
		</div>
	</div>
</div>

<div id="tve-lp-text-shadow-component" class="tve-component" data-view="LpTextShadow">
	<div class="borders-options action-group">
		<div class="dropdown-header" data-prop="docked">
			<div class="group-description">
				<?php echo __( 'Shadow', 'thrive-cb' ); ?>
			</div>
			<i></i>
		</div>
		<div class="dropdown-content">
			<div class="tve-shadow no-space" id="tcb-shadow-buttons"></div>
			<div id="tcb-text-shadow-list" class="tcb-relative tcb-preview-list" data-shadow-type="text-shadow"></div>
			<div id="tcb-box-shadow-list" class="tcb-relative tcb-preview-list" data-shadow-type="box-shadow"></div>
		</div>
	</div>
</div>
