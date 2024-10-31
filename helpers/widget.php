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

class IPBRecentWidget extends WP_Widget
{
	protected $params;
	protected $label;
	protected $basedir;
	protected $basename;
	
	public function __construct()
	{
		global $ipbrecent;

		$this->label = $ipbrecent->get('label');
		$this->basedir = $ipbrecent->get('basedir');
		$this->basename = $ipbrecent->get('basename');		
		
		parent::__construct($this->label, __('IP.Board Recent Topics', $this->label), array('description' => __('Displays recent topics list from IPS Community Suite (former IP.Board) based forum.', $this->label)));
	}
	
	public function form($instance)
	{
		$this->params = !empty($instance['params']) ? $instance['params'] : array();
		$this->prefix = $this->getOption('prefix');
		
		$ipbdb = new WPDB($this->getOption('dbusername'), $this->getOption('dbpassword'), $this->getOption('dbname'), $this->getOption('dbhost'));
	
		$widget = true;
	
		require($this->basedir.'settings.php');
	}

	public function update($new, $old)
	{
		$instance = array();
		
		$params = $new['params'];

		foreach ($params as $name => $value) {
			$instance['params'][$name] = !empty($value) ? $value : (!is_array($value) ? '' : array());
		}

		return $instance;
	}
	
	public function widget($args, $instance)
	{
		global $ipbrecent;

		echo $args['before_widget'];
		echo $ipbrecent->getOutput(array('id' => $this->number, 'widget' => true));
		echo $args['after_widget'];
	}
	
	public function getOption($name, $default = "")
	{
		return isset($this->params[$name]) ? $this->params[$name] : $default;
	}	
}

?>