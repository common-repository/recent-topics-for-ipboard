<?php 
/*------------------------------------------------------------------------
# IP.Board Recent Topics
# ------------------------------------------------------------------------
# The Krotek
# Copyright (C) 2011-2019 thekrotek.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website: https://thekrotek.com
# Support: support@thekrotek.com
-------------------------------------------------------------------------*/

foreach ($fields as $field => $data) {
	if ($data['type'] != 'title') {
		if ($widget) {
			$id = esc_attr($this->get_field_id($field));
			$name = esc_attr($this->get_field_name('params['.$field.']'));		
		} else {
			$id = $this->label.'-'.$field;
			$name = 'params['.$field.']';
		}
	
		$default = !empty($data['default']) ? $data['default'] : '';
		$value = esc_attr($this->getOption($field, $default));
		$classes = array($field);
	
		if (!empty($data['class'])) $classes[] = $data['class'];
	}

	?>
	<?php if ($data['type'] == 'title') { ?>
		<h2 class="title"><?php echo __('heading_'.$field, $this->label); ?></h2>
		<p class="note"><?php echo __('note_'.$field, $this->label); ?></p>		
	<?php } else { ?>
		<div class="option-container">
			<div class="option-label">
				<label for="<?php echo $id; ?>">
					<span><?php echo __($field.'_title', $this->label); ?></span>
					<a class="dashicons dashicons-editor-help option-hint" title="<?php echo esc_attr(__($field.'_hint', $this->label)); ?>"></a>
				</label>
			</div>
			<div class="option-input">
				<?php if ($data['type'] == 'text') { ?>
					<input type="text" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="<?php echo $value; ?>" class="<?php echo implode(' ', $classes); ?>" />
				<?php } elseif ($data['type'] == 'textarea') { ?>
					<textarea name="<?php echo $name; ?>" id="<?php echo $id; ?>" class="<?php echo implode(' ', $classes); ?>" rows="3"><?php echo $value; ?></textarea>
				<?php } elseif ($data['type'] == 'image') { ?>
					<input type="hidden" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="<?php echo $value; ?>" />
					
					<?php if ($this->getOption($field)) { ?>
						<img src="<?php echo esc_attr($this->getOption($field)); ?>" class="uploaded-image" />
					<?php } ?>
							
					<button id="upload-<?php echo $field; ?>" class="button secondary upload-file"><span class="dashicons dashicons-format-image"></span> <?php echo __('button_image', $this->label); ?></button>
				<?php } elseif ($data['type'] == 'number') { ?>
					<input type="number" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="<?php echo $value; ?>" class="<?php echo implode(' ', $classes); ?>" min="0" step="1">
				<?php } elseif ($data['type'] == 'radio') { ?>
					<?php if (!empty($data['data']) && is_array($data['data'])) { ?>						
						<?php foreach ($data['data'] as $option => $title) { ?>
							<label class="radio-option"><input type="radio" name="<?php echo $name; ?>" value="<?php echo $option; ?>"<?php echo $option == $value ? " checked='checked'" : ""; ?> /><?php echo $title; ?></label>
						<?php } ?>
					<?php } else {?>
						<span class='option-message'><?php echo $data['data']; ?></span>
					<?php } ?>							
				<?php } elseif ($data['type'] == 'select') { ?>
					<?php if (!empty($data['data']) && is_array($data['data'])) { ?>
						<select name="<?php echo $name; ?>" id="<?php echo $id; ?>" class="<?php echo implode(' ', $classes); ?>">
							<?php foreach ($data['data'] as $option => $title) { ?>
								<option value='<?php echo $option; ?>'<?php echo $option == $value ? " selected='selected'" : ""; ?>><?php echo $title; ?></option>
							<?php } ?>
						</select>
					<?php } else {?>
						<span class='option-message'><?php echo $data['data']; ?></span>
					<?php } ?>
				<?php } elseif ($data['type'] == 'multicheck') { ?>
					<?php if (!empty($data['data']) && is_array($data['data'])) { ?>
						<span class="multicheck">
							<?php foreach ($data['data'] as $option => $title) { ?>
								<label><input type="checkbox" name="<?php echo $name; ?>[]" value="<?php echo $option; ?>"<?php echo ((is_array($value) && in_array($option, $value)) ? " checked='checked'" : ""); ?> /><?php echo $title; ?></label></li>
							<?php } ?>
						</span>
					<?php } else {?>
						<span class='option-message'><?php echo $data['data']; ?></span>
					<?php } ?>
				<?php } ?>
			</div>
		</div>
	<?php } ?>
<?php } ?>