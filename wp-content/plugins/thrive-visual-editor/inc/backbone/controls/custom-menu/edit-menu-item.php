<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 5/23/2018
 * Time: 2:25 PM
 */
?>
<div class="tcb-flex space-between pb-10">
	<div class="label"><?php echo __( 'Insert / edit hyperlink', 'thrive-cb' ); ?></div>
	<span class="click" data-fn="open_settings"><?php tcb_icon( 'settings' ); ?></span>
</div>

<div class="control-grid no-space full-width pb-5">
	<input type="text" class="tcb-menu-item-text change target input" data-fn-change="changed" value="" autocomplete="off">
</div>
<div id="a-link-main" class="control-grid full-width pt-0"></div>

<label class="tcb-checkbox"><input type="checkbox" class="change target" data-fn="attr" data-attr="target"
			value="_blank"><span><?php echo __( 'Open in new tab', 'thrive-cb' ) ?></span></label>
<label class="tcb-checkbox"><input type="checkbox" class="change rel" data-fn="attr" data-attr="rel"
			value="nofollow"><span><?php echo __( 'Set link as nofollow', 'thrive-cb' ) ?></span></label>
