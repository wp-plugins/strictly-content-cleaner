<?php

/**
 * Plugin Name: Strictly Content Cleaner
 * Version: 1.0.0
 * Plugin URI: http://www.strictly-software.com/plugins/strictly-content-cleaner
 * Description: Very basic plugin that corrects content by converting text links into anchor tags and references to youtube videos into actual videos
 * if you are importing content with a scheduled job you will need to ensure the kses file or your functions file allows for object,embed and param tags
 * Author: Rob Reid
 * Author URI: http://www.strictly-software.com 
 * =======================================================================
 */

//error_reporting(E_ALL);

add_action('save_post'	, 'Strictly_Content_Cleaner',5);

function Strictly_Content_Cleaner( $post_id = null, $post_data = null ) {

	ShowDebugContentCleaner("IN Strictly_Content_Cleaner post id = " . $post_id);

	global $wpdb;

	$object = get_post($post_id);
	if ( $object == false || $object == null ) {
		return false;
	}


	// default content
	$newcontent = Strictly_Reformat_Content( $object->post_content );	


	$sql = $wpdb->prepare("UPDATE {$wpdb->posts} SET post_content = %s WHERE id = %d;", $newcontent,$object->ID);

	ShowDebugContentCleaner("SQL is $sql");


	$r = $wpdb->query($sql);

	// Clean cache
	if ( 'page' == $object->post_type ) {
		clean_page_cache($object->ID);
	} else {
		clean_post_cache($object->ID);
	}			
		
		
	return true;
	
}



function Strictly_Reformat_Content($content)
{
	ShowDebugContentCleaner("IN Strictly_Reformat_Content $content");

	$newcontent = $content;

	// better to do positive matches than negative lookaheads that can cause catastrophic backtracking so match then clean >> http://blog.strictly-software.com/2008/10/dangers-of-pattern-matching.html

	// replace any text links into proper links
	$newcontent = preg_replace("@(^|\s|[^'\">])(https?://\w+\.\w{2,}(?:\S+)?)(\s|$)@i","Strictly_GetDomain('$1<a class=\'strictly_clean\' href=\'$2\'>$2</a>$3','$2')",$newcontent);

	ShowDebugContentCleaner("after first replace $newcontent");

	// handle nested <a> in case we just caused them
	$newcontent = preg_replace("@(<a[^>]*>[^<]*?)(<a class='strictly_clean' [^>]+?>)([\s\S]+?)(<\/a>)(.*?<\/?a>)@i","$1$3$5",$newcontent);

	
	// convert youtube links
	$newcontent = preg_replace("@<a href=\"http:\/\/www\.youtube\.com\/watch\?v=([A-Z1-9]+)\">[\s\S]+?<\/a>@i","<div class='strictly_clean_video'><object width=\"500\" height=\"350\"><param name=\"movie\" value=\"http://www.youtube.com/v/$1&hl=en_GB&fs=1&\"></param><param name=\"allowFullScreen\" value=\"true\"></param><param name=\"allowscriptaccess\" value=\"always\"></param><embed src=\"http://www.youtube.com/v/$1&hl=en_GB&fs=1&\" type=\"application/x-shockwave-flash\" allowscriptaccess=\"always\" allowfullscreen=\"true\" width=\"500\" height=\"350\"></embed></object></div>",$newcontent);

	ShowDebugContentCleaner("return $newcontent");

	return $newcontent;
}

function Strictly_GetDomain($link,$url)
{
	$domain = preg_replace("@^https?://@","",$url);

	return preg_replace("@(<a [^>]+?>)([\s\S]+)(</a>)@","$1".$domain."$3",$link);
}

if(!function_exists('is_me')){

	// turn debug on for one IP only
	function is_me(){	
		
		$ip = "";           
		if (getenv("HTTP_CLIENT_IP")){ 
			$ip = getenv("HTTP_CLIENT_IP"); 
		}elseif(getenv("HTTP_X_FORWARDED_FOR")){
			$ip = getenv("HTTP_X_FORWARDED_FOR"); 			
		}elseif(getenv("REMOTE_ADDR")){
			$ip = getenv("REMOTE_ADDR");
		}else {
			$ip = "NA";
		}
		
		// put your IP here
		if($ip == "0.000.00.00"){
			return true;
		}else{
			return false;
		}

	}
}

if(!function_exists('ShowDebugContentCleaner')){

	// if the DEBUG constant hasn't been set then create it and turn it off
	if(!defined('DEBUGCLEANER')){
		if(is_me()){
			define('DEBUGCLEANER',false);
		}else{
			define('DEBUGCLEANER',false);
		}
	}

	/**
	 * function to output debug to page
	 *
	 * @param string $msg
	 */
	function ShowDebugContentCleaner($msg){
		if(DEBUGCLEANER){
			if(!empty($msg)){
				if(is_array($msg)){
					print_r($msg);
					echo "<br />";
				}else{
					echo htmlspecialchars($msg) . "<br>";
				}
			}
		}
	}
}

