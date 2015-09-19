<?php


// stop direct access to the file.
if(!defined('IN_MYBB')) {
	die('no');
}

/**
 * Shortcodes
 *
 * @package shortcodes
 * @author cronhound/senpai & WordPress Team
 **/
class Shortcodes {

	private static $shortcodes;
	public static $strict;

	public function _construct() {
		$shortcodes = Array();
		$strict = true;
	}

	public static function add($shortcode, $function) {
		if(is_callable($function)) {
			self::$shortcodes[$shortcode] = $function;
		}
	}

	//everything below this line was pretty much pulled from wordpress, no need to reinvent the wheel.

	private static function shortcode_regex() {

		$tagnames = array_keys(self::$shortcodes);
		$tagregexp = join( '|', array_map('preg_quote', $tagnames) );

		// WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
		// Also, see shortcode_unautop() and shortcode.js.
		return
			  '\\['                              // Opening bracket
			. '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
			. "($tagregexp)"                     // 2: Shortcode name
			. '(?![\\w-])'                       // Not followed by word character or hyphen
			. '('                                // 3: Unroll the loop: Inside the opening shortcode tag
			.     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
			.     '(?:'
			.         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
			.         '[^\\]\\/]*'               // Not a closing bracket or forward slash
			.     ')*?'
			. ')'
			. '(?:'
			.     '(\\/)'                        // 4: Self closing tag ...
			.     '\\]'                          // ... and closing bracket
			. '|'
			.     '\\]'                          // Closing bracket
			.     '(?:'
			.         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
			.             '[^\\[]*+'             // Not an opening bracket
			.             '(?:'
			.                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
			.                 '[^\\[]*+'         // Not an opening bracket
			.             ')*+'
			.         ')'
			.         '\\[\\/\\2\\]'             // Closing shortcode tag
			.     ')?'
			. ')'
			. '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
	}

	public static function parse($content) {

		if ( false === strpos( $content, '[' ) ) {
			return $content;
		}

		if (empty(self::$shortcodes) || !is_array(self::$shortcodes))
			return $content;

		$pattern = self::shortcode_regex();
		return preg_replace_callback( "/$pattern/s", 'self::run_shortcode', $content );
	}

	private static function run_shortcode( $m ) {

		// allow [[foo]] syntax for escaping a tag
		if ( $m[1] == '[' && $m[6] == ']' ) {
			return substr($m[0], 1, -1);
		}

		$tag = $m[2];
		$attr = self::fetch_attributes( $m[3] );

		if ( isset( $m[5] ) ) {
			// enclosing tag - extra parameter
			return $m[1] . call_user_func( self::$shortcodes[$tag], $attr, $m[5], $tag ) . $m[6];
		} else {
			// self-closing tag
			return $m[1] . call_user_func( self::$shortcodes[$tag], $attr, null,  $tag ) . $m[6];
		}
	}

	private static function fetch_attributes($text) {
		$atts = array();
		$pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
		$text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
		if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
			foreach ($match as $m) {
				if (!empty($m[1]))
					$atts[strtolower($m[1])] = stripcslashes($m[2]);
				elseif (!empty($m[3]))
					$atts[strtolower($m[3])] = stripcslashes($m[4]);
				elseif (!empty($m[5]))
					$atts[strtolower($m[5])] = stripcslashes($m[6]);
				elseif (isset($m[7]) and strlen($m[7]))
					$atts[] = stripcslashes($m[7]);
				elseif (isset($m[8]))
					$atts[] = stripcslashes($m[8]);
			}
		} else {
			$atts = ltrim($text);
		}
		return $atts;
	}

} // END class Shortcodes