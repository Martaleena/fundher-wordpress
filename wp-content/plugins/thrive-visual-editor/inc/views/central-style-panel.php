<?php
/**
 * Created by PhpStorm.
 * User: Ovidiu
 * Date: 1/21/2019
 * Time: 3:27 PM
 */
//Central Style Panel Drawer

?>
<div id="c-s-p-content">
	<?php foreach ( $data as $key => $set_templates ) : ?>
		<?php if ( empty( $set_templates ) ) : ?>
			<?php continue; ?>
		<?php endif; ?>
		<div class="c-s-p-tpl-list-wrapper">
			<span class="c-s-p-list-title"></span>
			<div class="c-s-p-tpl-list" data-list="<?php echo $key; ?>"></div>
		</div>
	<?php endforeach; ?>
</div>
