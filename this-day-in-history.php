<?php
/*
Plugin Name: This Day In History Widget (Updated for PHP 8+)
Plugin URI: http://www.blogshh.securehostinghawaii.com/wordpress/
Author: Eli Scheetz
Author URI: http://wordpress.ieonly.com/category/my-plugins/smaly-widget/
Contributors: scheeeli, mauideveloper
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8VWNB5QEJ55TJ
Description: This Widget lists and links to any posts posted in the same month, week, or day of the prior year. If you don't have any posts posted matching the criteria you selected the widget will not show at all.
Version: 2.14.26
*/
define("SMALY_VERSION", "2.14.26");
if (isset($_SERVER["SCRIPT_FILENAME"]) && __FILE__ == $_SERVER["SCRIPT_FILENAME"]) die('You are not allowed to call this page directly.<p>You could try starting <a href="/">here</a>.');
/**
 * SMALY Main Plugin File
 * @package SMALY
*/
/*  Copyright 2011-2014 Eli Scheetz (email: wordpress@ieonly.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/
function SMALY_install() {
	global $wp_version;
	if (version_compare($wp_version, "2.6", "<"))
		die(__("This Plugin requires WordPress version 2.6 or higher"));
}
function SMALY_excerpt($excerpt_length = 0) {
	global $post;
	$return = '';
	if ($excerpt_length) {
		if (($excerpt_length < 0) && strlen($post->post_excerpt))
			$return = '<br />'.$post->post_excerpt;
		else {
			if ($excerpt_length < 0)
				$excerpt_length = 200;
			$the_excerpt = preg_replace('/\[[^\]]*\]/', '', strip_tags('[preg_replace removes this]'.$post->post_content));
			if ($excerpt_length > 0 && strlen($the_excerpt) > $excerpt_length) {
				$excerpt_words = explode(' ', substr($the_excerpt, 0, $excerpt_length));
				$excerpt_words[count($excerpt_words)-1] = '...';
				$the_excerpt = implode(' ', $excerpt_words);
			}
			$return = '<br />'.$the_excerpt;
		}
	}
	return $return;
}
$SMALYorders = array('DESC', 'ASC');
$SMALYorderbys = array('rand', 'comment_count', 'date', 'modified', 'title', 'author', 'menu_order');
$SMALYrelatebys = array('Month' => array('monthnum', 'n'), 'Week' => array('w', 'W'), 'Day' => array('day', 'j'));
class SMALY_Widget_Class extends WP_Widget {
	/*function SMALY_Widget_Class() {
		$this->WP_Widget('SMALY-Widget', __('This Time Last Year'), array('classname' => 'widget_SMALY', 'description' => __('List and link to any posts posted in the same month, week, or day of the prior year')));
		$this->alt_option_name = 'widget_SMALY';
	}*/
	public function __construct() {
	    parent::__construct(
	        'SMALY-Widget',
	        __('This Time Last Year', 'text_domain'),
	        array('description' => __('List and link to any posts posted in the same month, week, or day of the prior year', 'text_domain'),)
	    );
	}

	function widget($args, $instance) {
		global $posts, $post, $SMALYrelatebys;
		$LIs = '';
		extract($args);
		if (!$instance['title'])
			$instance['title'] = "This Time Last Year";
		if (!is_numeric($instance['number']))
			$instance['number'] = 5;
		if (!is_numeric($instance['excerpt']))
			$instance['excerpt'] = 0;
		if (!is_numeric($instance['date']))
			$instance['date'] = 0;
		if (!isset($SMALYrelatebys[$instance['relateby']]))
			$instance['relateby'] = "Month";
		if (!$instance['orderby'])
			$instance['orderby'] = "rand";
		if (!$instance['order'])
			$instance['order'] = "DESC";
		if (!$instance['format'])
			$instance['format'] = "F j, Y";
		if ($instance['number'] > 0) {
			$arr = array('showposts' => $instance['number'], 'year' => $year, $SMALYrelatebys[$instance['relateby']][0] => date($SMALYrelatebys[$instance['relateby']][1]), 'orderby' => $instance['orderby'], 'order' => $instance['order']);
			if ($instance['relateby'] == 'Day')
				$arr[$SMALYrelatebys["Month"][0]] = date($SMALYrelatebys["Month"][1]);
			if ($instance['all'])
				$arr['date_query'] = array('compare' => '<', 'year' => date('Y'));
			else
				$arr['year'] = date('Y')-1;
			$SMALY_query = new WP_Query($arr);
			$pos = array("", "", "", "");
			while ($SMALY_query->have_posts()) {
				$SMALY_query->the_post();
				$pos[$instance['date']] = get_the_date($instance['format']);
				$LIs .= '<li class="SMALY-Post">'.$pos[1].' <a title="'.$pos[0].'" href="'.get_permalink($post->ID).the_title('" rel="nofollow">', '</a> '.$pos[2], false).SMALY_excerpt($instance['excerpt'])." $pos[3]</li>\n";
			}
			wp_reset_postdata();
		}
		if (strlen($LIs) > 0)
			echo $before_widget.$before_title.$instance['title']."$after_title<ul class=\"SMALY-Posts\">\n$LIs</ul>\n$after_widget";
	}
	function flush_widget_cache() {
		wp_cache_delete('widget_SMALY', 'widget');
	}
	function update($new, $old) {
		$instance = $old;
		$instance['title'] = strip_tags($new['title']);
		$instance['number'] = (int) $new['number'];
		$instance['excerpt'] = (int) $new['excerpt'];
		$instance['date'] = (int) $new['date'];
		$instance['relateby'] = strip_tags($new['relateby']);
		$instance['orderby'] = strip_tags($new['orderby']);
		$instance['order'] = strip_tags($new['order']);
		$instance['format'] = strip_tags($new['format']);
		$instance['all'] = (int) $new['all'];
		return $instance;
	}
	function form( $instance ) {
		global $SMALYrelatebys, $SMALYorderbys, $SMALYorders;
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$number = is_numeric($instance['number']) ? absint($instance['number']) : 5;
		$excerpt = is_numeric($instance['excerpt']) ? intval($instance['excerpt']) : 0;
		$date = is_numeric($instance['date']) ? intval($instance['date']) : 0;
		$relateby = isset($instance['relateby']) ? esc_attr($instance['relateby']) : 'Month';
		$orderby = isset($instance['orderby']) ? esc_attr($instance['orderby']) : 'rand';
		$order = isset($instance['order']) ? esc_attr($instance['order']) : 'DESC';
		$format = isset($instance['format']) ? esc_attr($instance['format']) : 'F j, Y';
		$all = is_numeric($instance['all']) ? intval($instance['all']) : 0;
		echo '<p><label for="'.$this->get_field_id('title').'">'.__('Alternate Widget Title').':</label><br />
		<input type="text" name="'.$this->get_field_name('title').'" id="'.$this->get_field_id('title').'" value="'.$title.'" /></p>
		<p><div style="float: left"><label for="'.$this->get_field_id('number').'">Show <input type="text" size="2" name="'.$this->get_field_name('number').'" id="'.$this->get_field_id('number').'" value="'.$number.'" /> posts&nbsp;</label></div>
		<div style="float: left"><label for="'.$this->get_field_id('relateby').'">in this <select name="'.$this->get_field_name('relateby').'" id="'.$this->get_field_id('relateby').'">';
		foreach ($SMALYrelatebys as $Time => $arr)
			echo '<option value="'.$Time.'"'.($Time==$relateby?" selected":"").'>'.$Time.'</option>';
		echo '</select>&nbsp;</label></div>
		<div style="float: left"><label for="'.$this->get_field_id('all').'">of <select name="'.$this->get_field_name('all').'" id="'.$this->get_field_id('all').'"><option value="0">Last Year</option><option value="1"'.($all?" selected":"").'>All Prior Years</option></select>&nbsp;</label></div>
		<div style="float: left"><label for="'.$this->get_field_id('order').'">in <select name="'.$this->get_field_name('order').'" id="'.$this->get_field_id('order').'">';
		foreach ($SMALYorders as $ord)
			echo '<option value="'.$ord.'"'.($ord==$order?" selected":"").'>'.$ord.'</option>';
		echo '</select> order&nbsp;</label></div>
		<div style="float: left"><label for="'.$this->get_field_id('orderby').'">by <select name="'.$this->get_field_name('orderby').'" id="'.$this->get_field_id('orderby').'">';
		foreach ($SMALYorderbys as $ordby)
			echo '<option value="'.$ordby.'"'.($ordby==$orderby?" selected":"").'>'.$ordby.'</option>';
		echo '</select></label></div></p>
		<p style="clear: left;"><label for="'.$this->get_field_id('date').'">Display post_date: </label><select name="'.$this->get_field_name('date').'" id="'.$this->get_field_id('date').'">';
		foreach (array("on hover", "before link", "after link", "after excerpt") as $pos => $desc)
			echo '<option value="'.$pos.'"'.($pos==$date?" selected":"").'>'.$desc.'</option>';
		echo '</select><br /><label for="'.$this->get_field_id('format').'">'.__('Date Format').': </label><input type="text" size="6" name="'.$this->get_field_name('format').'" id="'.$this->get_field_id('format').'" value="'.$format.'" /></p>
		<p><label for="'.$this->get_field_id('excerpt').'">Excerpt Length:</label>
		<input type="text" size="3" name="'.$this->get_field_name('excerpt').'" id="'.$this->get_field_id('excerpt').'" value="'.$excerpt.'" /><br />0 = "No Excerpt"<br />-1 = "Default Excerpt"</p>';
	}
}
function SMALY_set_plugin_action_links($links_array, $plugin_file) {
	if ($plugin_file == substr(__file__, (-1 * strlen($plugin_file))) && strlen($plugin_file) > 10)
		$links_array = array_merge(array('<a href="widgets.php">'.__( 'Widgets' ).'</a>'), $links_array);
	return $links_array;
}
function SMALY_set_plugin_row_meta($links_array, $plugin_file) {
	if ($plugin_file == substr(__file__, (-1 * strlen($plugin_file))) && strlen($plugin_file) > 10)
		$links_array = array_merge($links_array, array('<a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8VWNB5QEJ55TJ">'.__( 'Donate' ).'</a>'));
	return $links_array;
}
add_filter('plugin_row_meta', 'SMALY_set_plugin_row_meta', 1, 2);
add_filter('plugin_action_links', 'SMALY_set_plugin_action_links', 1, 2);
register_activation_hook(__FILE__,'SMALY_install');
add_action('widgets_init', function() {
    register_widget('SMALY_Widget_Class');
});
//add_action('widgets_init', create_function('', 'return register_widget("SMALY_Widget_Class");'));
