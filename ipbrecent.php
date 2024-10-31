<?php
/**
 * Plugin Name: IP.Board Recent Topics
 * Plugin URI:  https://thekrotek.com/wordpress-extensions/ipboard-recent
 * Description: Displays recent topics list from IPS Community Suite (former IP.Board) based forum.
 * Version:     1.0.0
 * Author:      The Krotek
 * Author URI:  https://thekrotek.com
 * Text Domain: ipbrecent
 * License:     GPL2
*/

defined("ABSPATH") or die("Restricted access");

$ipbrecent = new IPBRecent();

class IPBRecent
{
	protected $label = 'ipbrecent';
	protected $basedir;
	protected $basename;
	protected $params;
	protected $debug = false;

	public function __construct()
	{
		add_action('init', array($this, 'init'));

		$this->basedir = plugin_dir_path(__FILE__);
		$this->basename = plugin_basename(__FILE__);
		
		require_once('helpers/widget.php');
		
		add_action('widgets_init', function () { register_widget('IPBRecentWidget'); });
		
		add_shortcode($this->label, array($this, 'getOutput'));
		
		if ($this->debug) {
			ini_set('error_reporting', E_ALL);
			ini_set("display_errors", 1);
		}
	}

	public function init()
	{	
		add_action('wp_enqueue_scripts', array($this, 'loadSiteScripts'));
		add_action('admin_enqueue_scripts', array($this, 'loadAdminScripts'));
		
		add_filter('plugin_row_meta', array($this, 'updatePluginMeta'), 10, 2);
		add_filter('sidebars_widgets', array($this, 'filterWidgets'), 10, 1);
		
		load_plugin_textdomain($this->label, false, dirname($this->basename).'/languages');
	}
		
    public function loadAdminScripts($hook)
    {
    	if ($hook == 'widgets.php') {
			wp_enqueue_style($this->label, plugins_url('', __FILE__).'/assets/admin.css'.($this->debug ? '?v='.time() : ''), array(), null);
		}
	}
	
	public function loadSiteScripts($hook)
    {	
		wp_enqueue_style($this->label, plugins_url('', __FILE__).'/assets/style.css'.($this->debug ? '?v='.time() : ''), array(), null);
		wp_enqueue_script($this->label, plugins_url('', __FILE__).'/assets/script.js'.($this->debug ? '?v='.time() : ''), array(), null);
    }
	
	public function filterWidgets($sidebars)
	{
		if (!is_admin()) {
			$options = get_option('widget_'.$this->label);
		
			foreach ($sidebars as $sidebar => $widgets) {
				if (!empty($widgets) && is_array($widgets)) {
					foreach ($widgets as $key => $widget) {
						if (preg_match('/^'.$this->label.'-/', $widget)) {
							$number = str_replace($this->label.'-', '', $widget);
						
							if (!empty($options[$number]) && !empty($options[$number]['params'])) {
								if ($options[$number]['params']['mode'] == 'shortcode') {
									unset($sidebars[$sidebar][$key]);
								}
							}							
						}
					}
				}
			}
		}
		
		return $sidebars;
	}
		
	public function getOutput($args)
	{
		$output = "";
		
		$options = get_option('widget_'.$this->label);
		
		if (empty($args['widget'])) $args['widget'] = false;
		
		if (!empty($args['id'])) {
			if (!empty($options[$args['id']]) && !empty($options[$args['id']]['params'])) {
				$this->params = $options[$args['id']]['params'];

				if ($args['widget'] || ($this->params['mode'] != 'widget')) {		
					$this->prefix = $this->getOption('prefix');
					$forums = $this->getOption('forums', array());
					$ptags = $this->getOption('ptags');
					$postmode = $this->getOption('postmode', 'last');
					$layout = $this->getOption('layout', 'standard');

					$url = $this->getOption('url');
		
					if (preg_match("/\/$/", $url)) $url = substr($url, -1);

					if ($this->getOption('dbname') && $this->getOption('dbhost') && $this->getOption('dbusername') && $this->getOption('dbpassword')) {
						$ipbdb = new WPDB($this->getOption('dbusername'), $this->getOption('dbpassword'), $this->getOption('dbname'), $this->getOption('dbhost'));

						if ($ipbdb) {
							$query = "";

							$query .= "SELECT t.tid AS topic_id, t.forum_id, t.title AS topic_title, t.title_seo AS topic_seo, t.posts, t.views, t.start_date, t.starter_id, ";
							$query .= "w.word_default AS forum_name, f.name_seo AS forum_seo, m.members_seo_name AS starter_seo, t.starter_name, ";
							$query .= "(SELECT GROUP_CONCAT(tag_text) FROM ".$this->prefix."core_tags AS g WHERE g.tag_meta_id = t.tid) AS tags ";
							$query .= "FROM ".$this->prefix."forums_topics AS t ";
							$query .= "LEFT JOIN ".$this->prefix."forums_forums AS f ON f.id = t.forum_id ";
							$query .= "LEFT JOIN ".$this->prefix."core_members AS m ON m.member_id = t.starter_id ";
							$query .= "LEFT JOIN ".$this->prefix."core_sys_lang AS l ON lang_default = 1 ";
							$query .= "LEFT JOIN ".$this->prefix."core_sys_lang_words AS w ON (w.word_key = CONCAT('forums_forum_', f.id) AND w.lang_id = l.lang_id) ";

							$where = array();
		
							if ($forums) {
								$where[] = "t.forum_id ".($this->getOption('action', 'exclude') == "exclude" ? "NOT IN" : "IN")." (".implode(', ', $forums).")";
							}
		
							if (!$this->getOption('closed', '0')) {
								$where[] = "t.state = 'open' AND t.approved > 0";
							}
		
							if ($this->getOption('empty', '0')) {
								$where[] = "t.posts <= 1";
							}
							
							$query .= "WHERE ".implode(" AND ", $where)." ORDER BY t.last_post DESC LIMIT 0, ".$this->getOption('topics', '5');

							$topics = $ipbdb->get_results($query, 'ARRAY_A');

							$output .= "<div id='ipbrecent-".$args['id'].($args['widget'] ? "-widget" : "")."' class='ipbrecent ".$layout."'>";

							if ($this->getOption('above')) $output .= "<p class='list-above'>".$this->getOption('above')."</p>";

							$output .= "<h2 class='list-title'><a class='list-toggle' title='".__('toggle', $this->label)."'><i class='fa fa-chevron-down'></i></a> ".$this->getOption('title')."</h2>";
        					$output .= "<ul class='list-items'>";

							foreach ($topics as $topic) {
								$query = "";
	
								$query .= "SELECT p.pid AS post_id, p.post AS post_text, p.author_id, p.author_name, p.post_date, ";
								$query .= "m.email, m.members_seo_name AS author_seo, m.pp_main_photo AS avatar ";
								$query .= "FROM ".$this->prefix."forums_posts AS p ";
								$query .= "LEFT JOIN ".$this->prefix."core_members AS m ON m.member_id = p.author_id ";

								$where = array();
					
								$where[] = "p.queued = 0";
								$where[] = "p.pdelete_time = 0";
								$where[] = "p.topic_id = ".esc_attr($topic['topic_id']);
								
								$query .= "WHERE ".implode(" AND ", $where)." ORDER BY p.pid ".($postmode == 'first' ? "ASC" : "DESC")." LIMIT 0, 1";

								$post = $ipbdb->get_row($query, 'ARRAY_A');
	
								$data = array_merge($topic, $post);

 								if (!empty($_COOKIE['ips4_ipsTimezone'])) {
 									$data['start_date'] = $this->properTime($data['start_date'], $_COOKIE['ips4_ipsTimezone']);
									$data['post_date'] = $this->properTime($data['post_date'], $_COOKIE['ips4_ipsTimezone']);
								}
				
								$tlink = $url."/topic/".$data['topic_id']."-".$data['topic_seo'];
								$flink = $url."/forum/".$data['forum_id']."-".$data['forum_seo'];
								$alink = $url."/profile/".$data['author_id']."-".$data['author_seo'];
								$slink = $url."/profile/".$data['starter_id']."-".$data['starter_seo'];
								$plink = $url."/topic/".$data['topic_id']."-".$data['topic_seo']."/?do=getLastComment";
								$glink = $url."/tags/";
					
								$tname = $this->processText($data['topic_title'], $this->getOption('tlength'));
								$fname = $this->processText($data['forum_name'], $this->getOption('flength'));
								$aname = $this->processText($data['author_name'], $this->getOption('alength'));
								$sname = $this->processText($data['starter_name'], $this->getOption('alength'));
					
								$ttooltip = __('topic_tooltip', $this->label);
								$ftooltip = __('forum_tooltip', $this->label);
								$atooltip = __('author_tooltip', $this->label)." ".$aname;
								$ptooltip = __('post_tooltip', $this->label);
								$gtooltip = __('tag_tooltip', $this->label);
					
								$tdate = $this->formatDate($data['start_date']);
								$pdate = $this->formatDate($data['post_date']);
					
								$text = preg_replace('/[[\/\!]*?[^\[\]]*?]/si', '', strip_tags(html_entity_decode($data['post_text'], ENT_QUOTES, 'UTF-8'), "{$ptags}"));
								$text = $this->processText($text, $this->getOption('plength'));
										
								if (!$data['avatar']) {
									$email = md5(strtolower(trim($data['email'])));
									$default = $url."/public/style_images/master/profile/default_large.png";
									$avatar = "http://www.gravatar.com/avatar/".$email."?d=".urlencode($default)."&s=100";
								} elseif (!preg_match("/https?:\/\//", $data['avatar'])) {
									$avatar = $url."/uploads/".$data['avatar'];
								} else {
									$avatar = $data['avatar'];
								}

								$output .= "<li class='list-item'>";
					
								if ($layout == 'standard') {
									$output .= "<h4><a href='".$plink."' title='".$ptooltip."'>".$tname."</a></h4>";
										
									if ($this->getOption('meta', '1')) {
										$output .= "<div class='item-meta'>";
										$output .= "<div class='item-avatar'><a href='".$alink."' title='".$atooltip."'><img src='".$avatar."'></a></div>";
										$output .= "<div class='item-profile'>";
										$output .= "<div class='item-author'>".__('author', $this->label).": <a href='".$alink."' title='".$atooltip."'>".$aname."</a></div>";
										$output .= "<div class='item-date'>".__('date', $this->label).": ".$pdate."</div>";
										$output .= "<div class='item-forum'>".__('forum', $this->label).": <a href='".$flink."' title='".$ftooltip."'>".$fname."</a></div>";
										$output .= "</div>";
										$output .= "</div>";
									}
					
									if ($this->getOption('post', '0')) {
										$output .= "<div class='item-post'>".$text."</div>";
									}
								} elseif ($layout == 'native') {
    			    	    		$output .= "<div class='item-star'><span><i class='fa fa-star'></i></span></div>";
        			    			$output .= "<div class='item-topic'>";
            						$output .= "<h4><a href='".$tlink."' title='".$ttooltip."'>".$tname."</a></h4>";
            			
	            					if ($this->getOption('meta', '1')) {
    	        						$output .= "<div class='item-meta'>";
        	    						$output .= "<span><a href='".$flink."' title='".$ftooltip."'>".$fname."</a>, ".__('author', $this->label);
            							$output .= ": <a title='".$atooltip."' href='".$slink."'>".$sname."</a>, ".$tdate."</span>";
            			
		        	    				if ($this->getOption('tags', '1') && !empty($data['tags'])) {
    		        						$tags = explode(',', $data['tags']);
            					
        		    						$output .= "<span class='item-tags'>";
            						
            								foreach ($tags as $tag) {
            									$output .= "<span><a title='".$gtooltip." &quot;".$tag."&quot"."' href='".$glink.$tag."'>".$tag."</a></span>";
            								}
            					
	            							$output .= '</span>';
    	        						}
            				
	    	        					$output .= "</div>";
    	    	    				}
            			
        	    					$output .= '</div>';
            		
		        		    		if ($this->getOption('stats', '1')) {
    		        					$output .= "<div class='item-stats'>";
      									$output .= "<span class='item-replies'>".$data['posts']." ".__('replies', $this->label)."</span>";
      									$output .= "<span class='item-views'>".$data['views']." ".__('views', $this->label)."</span>";
            							$output .= "</div>";
		            				}
            		
			            			if ($this->getOption('meta', '1')) {
    			        				$output .= "<div class='item-meta'>";
        			    				$output .= "<span class='item-avatar'><a title='".$atooltip."' href='".$alink."'><img src='".$avatar."' alt='".__('author_tooltip', $this->label)." ".$aname."'></a></span>";
            							$output .= "<span class='item-author'><a title='".__('author_tooltip', $this->label)." ".$aname."' href='".$alink."'>".$aname."</a></span>";
            							$output .= "<span class='item-date'><a href='".$plink."' title='".$ptooltip."'>".$pdate."</a></span>";
            							$output .= "</div>";
		            				}
	    	    				} else {
    	    	    				$output .= "<h4><a href='".$plink."' title='".$ptooltip."'>".$tname."</a></h4>";
            			
        	    					if ($this->getOption('meta', '1')) {
            							$output .= "<span class='item-meta'><a href='".$flink."' title='".$ftooltip."'>".$fname."</a>, <a title='".__('author_tooltip', $this->label).' '.$aname."' href='".$alink."'>".$aname."</a>, ".$pdate."</span>";
            						}
	        					}
        			
    	    					$output .= "</li>";
	        				}
        		
							$output .= "</ul>";
				
							if ($this->getOption('below')) $output .= "<p class='list-below'>".$this->getOption('below')."</p>";
						} else {
							$output .= "<p class='list-error'>".__('heading', $this->label).": ".__('error_database', $this->label)."</p>";
						}
					} else {
						$output .= "<p class='list-error'>".__('heading', $this->label).": ".__('error_dbparams', $this->label)."</p>";
					}
		
					$output .= "<div class='copyright'>";
					$output .= "Powered by <a href='https://thekrotek.com' target='_blank' title='Discover the truth!'>The Krotek</a>";
					$output .= "</div>";
					$output .= "</div>";
				}
			} else {
				$output = __('heading', $this->label).": ".__('error_params', $this->label);
			}
		} else {
			$output = __('heading', $this->label).": ".__('error_id', $this->label);
		}
				
		return $output;
	}

	public function processText($text, $length)
	{
		if ($length) {
			if (!$this->detectUTF8($text) || $this->getOption('convert', '0')) $text = mb_convert_encoding($text, mb_detect_encoding($text), 'UTF-8');
	
			$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
	
			if (($length > 4) && (mb_strlen($text) > ($length - 4))) $text = trim(mb_substr($text, 0, ($length - 4), 'UTF-8'))."...";
		}
	
		return $text;
	}
	
	public function detectUTF8($text)
	{
		$search  = "%(?:[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|";
		$search .= "[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|";
		$search .= "\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|";
		$search .= "\xF4[\x80-\x8F][\x80-\xBF]{2})+%xs";
	
		return preg_match($search, $text);
	}

	public function formatDate($timestamp)
	{
		$format = $this->convertFormat($this->getOption('dateformat', 'd.m.Y, H:i'), 'strftime');

		setlocale(LC_TIME, get_locale().'.UTF8');

   		$date = strftime($format, $timestamp);
    	
  		return $date;
	}

	public function convertFormat($format, $to = 'date')
	{	
 		$values = array(
	 		'D' => '%a', // Short day of the week
 			'l' => '%A', // Full day of the week
 			'd' => '%d', // Day of the month with leading zeroes
	 		'j' => '%e', // Day of the month without leading zeroes
 			'z' => '%j', // Day of the year
 			'N' => '%u', // Number of the day of the week from 1 to 7
	 		'w' => '%w', // Number of the day of the week from 0 to 7
 			'W' => '%W', // Full week of the year
			'M' => '%b', // Short month
			'M' => '%h', // Short month
			'F' => '%B', // Full month
			'm' => '%m', // Month number with leading zeroes
			'n' => '%m', // Month number without leading zeroes
			'o' => '%G', //	Four digit representation of the year going by ISO-8601:1988 standards
			'y' => '%y', //	Two digit representation of the year
			'Y' => '%Y', //	Four digit representation of the year
			'H' => '%H', // Hour in 24-hour format with leading zeroes
			'G' => '%k', // Hour in 24-hour format without leading zeroes
			'h' => '%I', // Hour in 12-hour format with leading zeroes
			'g' => '%l', // Hour in 12-hour format without leading zeroes
			'a' => '%P', // am or pm
			'A' => '%p', // AM or PM
			'i' => '%M', // Minutes with leading zeroes
			's' => '%S', // Seconds with leading zeroes
			'U' => '%s', // Unix Epoch Time timestamp
			'O' => '%z', //	Timezone offset
			'T' => '%Z', // Timezone abbreviation
	 	);
 	
		foreach ($values as $date => $strftime) {
			if ($to == 'date') $format = str_replace($strftime, $date, $format);
			elseif ($to == 'strftime') $format = str_replace($date, $strftime, $format);
		}
 		
		return $format;
	}

	public function properTime($basetime, $zone)
	{
		$serverzone = new DateTimeZone(date_default_timezone_get());
		$servertime = new DateTime("now", $serverzone);
		$serveroffset = $serverzone->getOffset($servertime);
		
		if (!is_numeric($zone)) {
			$remotezone = new DateTimeZone($zone);
			$remotetime = new DateTime("now", $remotezone);
			$remoteoffset = $remotezone->getOffset($remotetime);
		} else {
			$remoteoffset = $zone;
		}

		$offset = $serveroffset - $remoteoffset;

		if ($offset > 0) $newtime = $basetime - abs($offset);
		else $newtime = $basetime + abs($offset);

		return $newtime;
	}
				
	public function updatePluginMeta($links, $file)
	{
		if ($file == $this->basename) {
			$links = array_merge($links, array('<a href="widgets.php">'.__('Settings', $this->label).'</a>'));
			$links = array_merge($links, array('<a href="https://thekrotek.com/support">'.__('Donate & Support', $this->label).'</a>'));
		}
	
		return $links;
	}
	
	public function getOption($name, $default = "")
	{
		return isset($this->params[$name]) ? $this->params[$name] : $default;
	}
	
    public function get($variable)
    {
        return $this->{$variable};
    }	
}

?>