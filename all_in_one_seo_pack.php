<?php

/*
Plugin Name: All in One SEO Pack
Plugin URI: http://wp.uberdose.com/2007/03/24/all-in-one-seo-pack/
Description: Out-of-the-box SEO for your Wordpress blog.
Version: 0.6.1
Author: uberdose
Author URI: http://wp.uberdose.com/
*/

/* Copyright (C) 2007 Dirk Zimmermann (dirk AT uberdose DOT com)

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
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA */
 
class All_in_One_SEO_Pack {
	
 	var $version = "0.6.1";
 	
 	/**
 	 * Number of words to be used (max) for generating an excerpt.
 	 */
 	var $maximum_excerpt_length = 25;

 	/**
 	 * Minimum number of chars an excerpt should be so that it can be used
 	 * as description. Touch only if you know what you're doing.
 	 */
 	var $minimum_excerpt_length = 1;
 	
	function start() {
		if (get_option('aiosp_max_words_excerpt') && is_numeric(get_option('aiosp_max_words_excerpt'))) {
			$this->maximum_excerpt_length = get_option('aiosp_max_words_excerpt');
		}
		ob_start();
	}

	function wp_head() {
		global $post;
		$meta_string = null;
		
		echo "<!-- all in one seo pack $this->version -->\n";
		
		if (is_home() && get_option('aiosp_home_keywords')) {
			$keywords = trim(get_option('aiosp_home_keywords'));
		} else {
			$keywords = $this->get_all_keywords();
		}

		if (is_single() || is_page()) {
            $description = trim(stripslashes(get_post_meta($post->ID, "description", true)));
			if (!$description) {
				$description = $this->trim_excerpt_without_filters_full_length($post->post_excerpt);
				if (!$description) {
					$description = $this->trim_excerpt_without_filters($post->post_content);
				}				
			}
		} else if (is_home()) {
			$description = trim(stripslashes(get_option('aiosp_home_description')));
		}
		
		if (isset($description) && strlen($description) > $this->minimum_excerpt_length) {
			if (isset($meta_string)) {
				$meta_string .= "\n";
			}
			$meta_string .= sprintf("<meta name=\"description\" content=\"%s\"/>", $description);
		}

		if (isset ($keywords) && !empty($keywords)) {
			if (isset($meta_string)) {
				$meta_string .= "\n";
			}
			$meta_string .= sprintf("<meta name=\"keywords\" content=\"%s\"/>\n", $keywords);
		}

		if(!is_home() && !is_single() && !is_page()) {
			if (isset($meta_string)) {
				$meta_string .= "\n";
			}
			$meta_string = '<meta name="robots" content="noindex,follow" />';
		}
		
		// title
		if (is_home()) {
			$title = trim(stripslashes(get_option('aiosp_home_title')));
		} else if (is_single()) {
        	$title = stripslashes(get_post_meta($post->ID, "title", true));
		}
		$header = ob_get_contents();
		ob_end_clean();
		
		if ($title) {
			$header = preg_replace("/<title>.*<\/title>/", "<title>$title</title>", $header);
		} else if (get_option('aiosp_rewrite_titles')) {
			global $s;
			if (is_search() && isset($s) && !empty($s)) {
				$title = attribute_escape(stripslashes($s));
			} else if (is_single() || is_page()) {
				global $post;
	            $title = stripslashes(get_post_meta($post->ID, "title", true));
			}		
			if (!$title) {
				$title = wp_title('', false);
			}
			if ($title) {
				$title .= ' | ' . get_bloginfo('name');
				$title = trim($title);
				$header = preg_replace("/<title>.*<\/title>/", "<title>$title</title>", $header);
			}
		}
		
		gzip_compression();
		print($header);

		if ($meta_string != null) {
			echo $meta_string;
		}
	}
	
	/**
	 * @deprecated Was only used for braille, will be removed in a coming release
	 */
	function trim_excerpt($text) {
		$text = apply_filters('the_content', $text);
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
		$excerpt_length = 55;
		$words = explode(' ', $text, $excerpt_length + 1);
		if (count($words) > $excerpt_length) {
			array_pop($words);
			array_push($words, '...');
			$text = implode(' ', $words);
		}
		return trim(stripslashes($text));
	}
	
	function trim_excerpt_without_filters($text) {
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
		$excerpt_length = $this->maximum_excerpt_length;
		$words = explode(' ', $text, $excerpt_length + 1);
		if (count($words) > $excerpt_length) {
			array_pop($words);
			array_push($words, '...');
			$text = implode(' ', $words);
		}
		return trim(stripslashes($text));
	}
	
	function trim_excerpt_without_filters_full_length($text) {
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
		$excerpt_length = $this->maximum_excerpt_length;
		$words = explode(' ', $text, $excerpt_length + 1);
		return trim(stripslashes($text));
	}
	
	function get_all_keywords() {
		global $posts;

	    if (is_array($posts)) {
	        foreach ($posts as $post) {
	            if ($post) {
	            	if (get_option('aiosp_use_categories') && !is_page()) {
		                $categories = get_the_category($post->ID);
		                foreach ($categories as $category) {
		                    if (isset($keywords) && !empty($keywords)) {
		                        $keywords .= ',';
		                    }
		                	$keywords .= $category->cat_name;
		                }
	            	}
	                $keywords_a = $keywords_i = null;
	                $description_a = $description_i = null;
	                $id = $post->ID;
		            $keywords_i = stripslashes(get_post_meta($post->ID, "keywords", true));
	                if (isset($keywords_i) && !empty($keywords_i)) {
	                    if (isset($keywords) && !empty($keywords)) {
	                        $keywords .= ',';
	                    }
	                    $keywords .= $keywords_i;
	                }
	            }
	        }
	    }
	    
	    return $this->get_unique_keywords($keywords);
	}

	function get_unique_keywords($keywords) {
		$keywords_ar = array_unique(explode(',', $keywords));
		return implode(',', $keywords_ar);
	}
	
	function post_meta_tags($id) {
	    $awmp_edit = $_POST["aiosp_edit"];
	    if (isset($awmp_edit) && !empty($awmp_edit)) {
		    $keywords = $_POST["aiosp_keywords"];
		    $description = $_POST["aiosp_description"];
		    $title = $_POST["aiosp_title"];

		    delete_post_meta($id, 'keywords');
		    delete_post_meta($id, 'description');
		    delete_post_meta($id, 'title');

		    if (isset($keywords) && !empty($keywords)) {
			    add_post_meta($id, 'keywords', $keywords);
		    }
		    if (isset($description) && !empty($description)) {
			    add_post_meta($id, 'description', $description);
		    }
		    if (isset($title) && !empty($title)) {
			    add_post_meta($id, 'title', $title);
		    }
	    }
	}

	function add_meta_tags_textinput() {
	    global $post;
	    $keywords = stripslashes(get_post_meta($post->ID, 'keywords', true));
	    $title = stripslashes(get_post_meta($post->ID, 'title', true));
		?>
		<input value="aiosp_edit" type="hidden" name="aiosp_edit" />
		<table style="margin-bottom:40px; margin-top:30px;">
		<tr>
		<th style="text-align:left;" colspan="2">
		<a href="http://wp.uberdose.com/2007/03/24/all-in-one-seo-pack/">All in One SEO Pack</a>
		</th>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Title:') ?></th>
		<td><input value="<?php echo $title ?>" type="text" name="aiosp_title" size="80"/></td>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Keywords (comma separated):') ?></th>
		<td><input value="<?php echo $keywords ?>" type="text" name="aiosp_keywords" size="80"/></td>
		</tr>
		</table>
		<?php
	}

	function add_meta_tags_page_textinput() {
	    global $post;
	    $keywords = stripslashes(get_post_meta($post->ID, 'keywords', true));
	    $description = stripslashes(get_post_meta($post->ID, 'description', true));
	    $title = stripslashes(get_post_meta($post->ID, 'title', true));
		?>
		<input value="aiosp_edit" type="hidden" name="aiosp_edit" />
		<table style="margin-bottom:40px; margin-top:30px;">
		<tr>
		<th style="text-align:left;" colspan="2">
		<a href="http://wp.uberdose.com/2007/03/24/all-in-one-seo-pack/">All in One SEO Pack</a>
		</th>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Title:') ?></th>
		<td><input value="<?php echo $title ?>" type="text" name="aiosp_title" size="80"/></td>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Keywords (comma separated):') ?></th>
		<td><input value="<?php echo $keywords ?>" type="text" name="aiosp_keywords" size="80"/></td>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Description:') ?></th>
		<td><input value="<?php echo $description ?>" type="text" name="aiosp_description" size="80"/></td>
		</tr>
		</table>
		<?php
	}

	function admin_menu() {
		add_submenu_page('options-general.php', __('All in One SEO'), __('All in One SEO'), 5, __FILE__, array($this, 'plugin_menu'));
	}
	
	function plugin_menu() {
		$message = null;
		$message_updated = __("All in One SEO Options Updated.");
		
		// update options
		if ($_POST['action'] && $_POST['action'] == 'aiosp_update') {
			$message = $message_updated;
			update_option('aiosp_home_title', $_POST['aiosp_home_title']);
			update_option('aiosp_home_description', $_POST['aiosp_home_description']);
			update_option('aiosp_home_keywords', $_POST['aiosp_home_keywords']);
			update_option('aiosp_max_words_excerpt', $_POST['aiosp_max_words_excerpt']);
			update_option('aiosp_rewrite_titles', $_POST['aiosp_rewrite_titles']);
			update_option('aiosp_use_categories', $_POST['aiosp_use_categories']);
			wp_cache_flush();
		}

?>
<?php if ($message) : ?>
<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
<?php endif; ?>
<div id="dropmessage" class="updated" style="display:none;"></div>
<div class="wrap">
<h2><?php _e('All in One SEO Plugin Options'); ?></h2>
<p><?php _e('For feedback, help etc. please click <a title="Homepage for All in One SEO Plugin" href="http://wp.uberdose.com/2007/03/24/all-in-one-seo-pack/">here</a>.') ?></p>
<form name="dofollow" action="" method="post">
<table>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;"><?php _e('Home Title:')?></td>
<td>
<textarea cols="60" rows="2" name="aiosp_home_title"><?php echo stripcslashes(get_option('aiosp_home_title')); ?></textarea>
</td>
</tr>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;"><?php _e('Home Description:')?></td>
<td>
<textarea cols="60" rows="2" name="aiosp_home_description"><?php echo stripcslashes(get_option('aiosp_home_description')); ?></textarea>
</td>
</tr>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;"><?php _e('Home Keywords (comma separated):')?></td>
<td>
<textarea cols="60" rows="2" name="aiosp_home_keywords"><?php echo stripcslashes(get_option('aiosp_home_keywords')); ?></textarea>
</td>
</tr>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;"><?php _e('Max Number of Words in Auto-Generated Descriptions:')?></td>
<td>
<input size="60" name="aiosp_max_words_excerpt" value="<?php echo stripcslashes(get_option('aiosp_max_words_excerpt')); ?>"/>
</td>
</tr>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;"><?php _e('Rewrite Titles:')?></td>
<td>
<input type="checkbox" name="aiosp_rewrite_titles" <?php if (get_option('aiosp_rewrite_titles')) echo "checked=\"1\""; ?>/>
</td>
</tr>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;"><?php _e('Use Categories for META keywords:')?></td>
<td>
<input type="checkbox" name="aiosp_use_categories" <?php if (get_option('aiosp_use_categories')) echo "checked=\"1\""; ?>/>
</td>
</tr>
</table>
<p class="submit">
<input type="hidden" name="action" value="aiosp_update" /> 
<input type="hidden" name="page_options" value="aiosp_home_description" /> 
<input type="submit" name="Submit" value="<?php _e('Update Options')?> &raquo;" /> 
</p>
</form>
</div>
<?php
	
	} // plugin_menu

}

add_option("aiosp_home_description", null, __('All in One SEO Plugin Home Description'), 'yes');
add_option("aiosp_home_title", null, __('All in One SEO Plugin Home Title'), 'yes');
add_option("aiosp_rewrite_titles", 1, __('All in One SEO Plugin Rewrite Titles'), 'yes');
add_option("aiosp_use_categories", 1, __('All in One SEO Plugin Use Categories'), 'yes');
add_option("aiosp_max_words_excerpt", 25, __('All in One SEO Plugin Maximum Number of Words in Auto-Generated Descriptions'), 'yes');

$aiosp = new All_in_One_SEO_Pack();
add_action('wp_head', array($aiosp, 'wp_head'));

add_action('simple_edit_form', array($aiosp, 'add_meta_tags_textinput'));
add_action('edit_form_advanced', array($aiosp, 'add_meta_tags_textinput'));
add_action('edit_page_form', array($aiosp, 'add_meta_tags_page_textinput'));

add_action('edit_post', array($aiosp, 'post_meta_tags'));
add_action('publish_post', array($aiosp, 'post_meta_tags'));
add_action('save_post', array($aiosp, 'post_meta_tags'));
add_action('edit_page_form', array($aiosp, 'post_meta_tags'));

add_action('admin_menu', array($aiosp, 'admin_menu'));

$aiosp->start();

?>
