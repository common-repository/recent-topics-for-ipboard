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

$fields = array(
	'title' => array('type' => 'text'),
	'mode' => array('type' => 'select', 'default' => 'widget', 'data' => array(
		'widget' => __('mode_widget', $this->label),
		'shortcode' => __('mode_shortcode', $this->label),
		'both' => __('mode_both', $this->label))),
	'database' => array('type' => 'title'),
	'dbhost' => array('type' => 'text', 'default' => 'localhost'),
	'dbusername' => array('type' => 'text'),
	'dbpassword' => array('type' => 'text'),
	'dbname' => array('type' => 'text'),
	'dbprefix' => array('type' => 'text'),
	'url' => array('type' => 'text'),
	'convert' => array('type' => 'radio', 'data' => array('1' => __('yes', $this->label), '0' => __('no', $this->label))));

?>
		
<div class="<?php echo $this->label; ?> kui">
	<div class="option-header">
		<span><?php echo __('shortcode_header', $this->label); ?>: <?php echo (is_numeric($this->number) ? $this->number : __('message_save', $this->label)); ?></span>
	</div>
	<?php if (file_exists($this->basedir.'premium/settings.php')) { ?>
		<?php require($this->basedir.'premium/settings.php'); ?>
	<?php } else { ?>
		<p>More settings available in <a href="https://thekrotek.com/wordpress-extensions/ipboard-recent" title="Go Premium!">Premium version</a> only.</p>
	<?php } ?>
	<?php require($this->basedir.'helpers/fields.php'); ?>
	<div class="footnote"><?php echo sprintf(__('footnote', $this->label), date("Y", time())); ?></div>
</div>