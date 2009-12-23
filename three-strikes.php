<?php
/*
Plugin Name: Three Strikes And You're Out
Plugin URI: http://www.jamesmckay.net/code/three-strikes/
Description: Closes comments and trackbacks across the board to IP addresses that are behaving badly.  
Version: 1.0-alpha 2
Author: James McKay
Author URI: http://www.jamesmckay.net/
*/

/* ========================================================================== */

/*
 * Copyright (c) 2007 James McKay
 * http://www.jamesmckay.net/
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE. 
 */

/* ====== Constants ====== */ 

/**
 * Defines the limit to the number of bad hits an IP address can give before all
 * comments from it are blocked. Tweak this if you want to allow more slack. 
 */

define('THREE_STRIKES_LIMIT', 3);

/**
 * Defines the timeout for bad hits in days. 
 */

define('THREE_STRIKES_TIMEOUT', 7);

/**
 * Defines whether we should do strict checking on Bad Behavior. This may give
 * more false positives, however, so it's best left off.
 */

define('THREE_STRIKES_BB_STRICT', false);

// For compatibility with WP 2.0

if (!function_exists('wp_die')) {
	function wp_die($msg) {
		die($msg);
	}
}

class jm_ThreeStrikes
{
	var $one_week_ago_sql;

	function jm_ThreeStrikes()
	{
		global $wpdb;
		
		add_filter('the_posts', array(&$this, 'process_posts'));
		add_filter('three_strikes_count', array(&$this, 'three_strikes_spam'), 0, 2);
		add_filter('three_strikes_count', array(&$this, 'three_strikes_bb'), 0, 2);
		add_filter('three_strikes_count', array(&$this, 'three_strikes_log'), 0, 2);
		add_action('preprocess_comment', array(&$this, 'process_comment'), 0); // Run this before Akismet
		add_action('three_strikes', array(&$this, 'add_log'), 10, 2);
		
		$this->one_week_ago_sql = date('Y-m-d H:i:s', 
			time() - 86400 * THREE_STRIKES_TIMEOUT);
		$wpdb->three_strikes = $wpdb->prefix . 'three_strikes';
	}
	

	/* ====== process_posts ====== */
	
	/**
	 * Processes all the posts returned from the query before they are passed
	 * into the loop. If the IP address is OK, we simply return the list of
	 * posts unchanged. If the IP address is not OK, however, we set
	 * comment_status and ping_status for each post to "closed".
	 */
	
	function process_posts($posts)
	{
		if (!$this->allow_ip_address()) {
			foreach ($posts as $k => $v) {
				$posts[$k]->comment_status = 'closed';
				$posts[$k]->ping_status = 'closed';
			}
		}
		return $posts;
	}
	
	
	/* ====== process_comment ====== */
	
	/**
	 * Checks a comment to see if it can be accepted or not, and croaks on a
	 * bad IP address.
	 */
	
	function process_comment($comment)
	{
		if (!$this->allow_ip_address()) {
			wp_die('Sorry, you can not comment on this blog.');
		}
		return $comment; // for some strange reason it gives duplicate comment errors if you don't
	}


	/* ====== allow_ip_address ====== */
	
	/**
	 * This is where we test the IP address to see if it has been up to no good.
	 * It calls a new filter, "three_strikes", that has been defined by this
	 * plugin. 
	 * 
	 * @returns TRUE if the IP address is OK, otherwise FALSE.
	 */

	function allow_ip_address()
	{
		$bad = apply_filters('three_strikes_count', 0, $_SERVER['REMOTE_ADDR']);
		return ($bad < THREE_STRIKES_LIMIT);
	}
	 
	/* ====== three_strikes filters ====== */
	
	/*
	 * These methods are where all the work is done. They are implementations of
	 * the three_strikes filter that we defined in allow_ip_address above.
	 * 
	 * Two such filters are defined by default: one for the spam queue and one
	 * for your Bad Behavior logs. Other plugins can extend this plugin by
	 * adding their own three_strikes filters.
	 * 
	 * Each filter takes two arguments, the count of misbehaviour events so far
	 * and the IP address of the current request.
	 */
	
	/* ====== three_strikes_spam ====== */

	/**
	 * Checks the spam queue for recent nefarious business.
	 *  
	 * @param $count The number of bad hits reported by previous filters
	 * @param $ip The visitor's IP address
	 * @returns The total number of bad hits reported by all filters so far
	 * 		including this one.  
	 */
	
	function three_strikes_spam($count, $ip)
	{
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM $wpdb->comments " 
			. "WHERE comment_approved='spam' AND comment_author_IP='"
			. $wpdb->escape($ip) . "'";
		return $count + $wpdb->get_var($sql);
	}
	
	/* ====== three_strikes_bb ====== */
	
	/**
	 * Checks the Bad Behavior logs for recent nefarious business.
	 */
	
	function three_strikes_bb($count, $ip)
	{
		global $wpdb;
		if (defined('BB2_VERSION')) {
			$sql = 'select count(*) from `' . $wpdb->prefix . 'bad_behavior` '
				. 'where `ip` = \'' . $wpdb->escape($ip) . '\'';
			if (!THREE_STRIKES_BB_STRICT) {
				$sql .= ' and `key` != \'00000000\'';
			}
			$sql .= ' and `date` > \'';
			$sql .= $this->one_week_ago_sql;
			$sql .= '\'';
			return $count + $wpdb->get_var($sql);
		}
		else {
			return $count;
		}
	}

	/* ====== update_log ====== */
	
	/**
	 * Ensures that the logging table exists.
	 */
	
	function ensure_log()
	{
		global $wpdb;
		// Create table if it doesn't exist, log the hit, delete outdated hits
		$sql = "create table if not exists `$wpdb->three_strikes` (" .
				"`strike_id` bigint(20) not null auto_increment, " .
				"`strike_ip` varchar(50) not null default '', " .
				"`strike_time` datetime not null default '0000-00-00 00:00:00', " .
				"`source` varchar(100) not null default '', " .
				"`message` varchar(100) not null default '', " .
				"PRIMARY KEY(`strike_id`))";
		$wpdb->query($sql);
	}

	/**
	 * Adds a new event to the three strikes log.
	 */
	
	function add_log($source, $message)
	{		
		global $wpdb;
		$this->ensure_log();
		$sql = "insert into `$wpdb->three_strikes` " .
				"(`strike_ip`, `strike_time`, `source`, `message`) " .
				"values (" .
				"'" . $wpdb->escape($_SERVER['REMOTE_ADDR']) . "', " .
				"'" . gmdate('Y-m-d H:i:s') . "', " .
				"'" . $wpdb->escape($source) . "', " .
				"'" . $wpdb->escape($message) . "')";
		$wpdb->query($sql);
		$sql = "delete from $wpdb->three_strikes " .
				"where strike_time < '$this->one_week_ago_sql'";
		$wpdb->query($sql);
	}
	
	/**
	 * Tots up the number of bad hits that have been logged so far for the final
	 * decision on whether to allow this request 
	 */
	
	function three_strikes_log($count, $ip)
	{
		global $wpdb;
		$this->ensure_log();
		
		$sql = "select count(*) from $wpdb->three_strikes " .
				"where strike_ip='" . $wpdb->escape($ip) . "'";
		$count += $wpdb->get_var($sql);
		return $count;
	}
}

$myThreeStrikes = new jm_ThreeStrikes();
?>