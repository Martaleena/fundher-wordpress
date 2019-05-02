<div class="side">
	<?php if ( tcb_editor()->has_revision_manager() ) : ?>
		<a class="click" href="javascript:void(0)" data-fn="revisions"
		   data-tooltip="<?php echo esc_attr__( 'Revision Manager', 'thrive-cb' ); ?>" data-position="top">
			<?php tcb_icon( 'clock-regular' ); ?>
		</a>
	<?php endif ?>
	<a class="click tve-disabled" id="tcb-undo" href="javascript:void(0)" data-fn="undo" data-position="top"
	   data-tooltip="<?php echo esc_attr__( 'Undo', 'thrive-cb' ); ?>">
		<?php tcb_icon( 'undo-regular' ); ?>
	</a>
	<a class="click tve-disabled" id="tcb-redo" href="javascript:void(0)" data-fn="redo" data-position="top"
	   data-tooltip="<?php echo esc_attr__( 'Redo', 'thrive-cb' ); ?>">
		<?php tcb_icon( 'redo-regular' ); ?>
	</a>

</div>
<div class="side save">
	<a href="javascript:void(0)" class="save-btn click save" data-fn="save">
		<span class="txt"><?php echo __( 'Save work', 'thrive-cb' ); ?></span>
		<span class="drop click" data-fn="click_save_arrow"><?php tcb_icon( 'chevron-up-regular' ); ?></span>
	</a>
	<a href="javascript:void(0)" class="save-btn click edit-mode p-0 tve-button orange" data-fn="f:main.EditMode.exit"><?php echo __( 'DONE', 'thrive-cb' ); ?></a>
</div>

<div class="save-options">
	<a href="<?php echo tcb_get_preview_url( false, false ); ?>" class="click" data-fn="save_exit"><?php esc_html_e( 'Save and Exit', 'thrive-cb' ); ?></a>
</div>
