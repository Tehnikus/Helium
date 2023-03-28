<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Template class
*/
class Template {
	private $adaptor;
	
	/**
	 * Constructor
	 *
	 * @param	string	$adaptor
	 *
 	*/
  	public function __construct($adaptor) {
	    $class = 'Template\\' . $adaptor;

		if (class_exists($class)) {
			$this->adaptor = new $class();
		} else {
			throw new \Exception('Error: Could not load template adaptor ' . $adaptor . '!');
		}
	}
	
	/**
	 * 
	 *
	 * @param	string	$key
	 * @param	mixed	$value
 	*/	
	public function set($key, $value) {
		$this->adaptor->set($key, $value);
	}
	
	/**
	 * 
	 *
	 * @param	string	$template
	 * @param	bool	$cache
	 *
	 * @return	string
 	*/	
	public function render($template, $cache = false) {
		if (strpos($template, 'template/') !== false) {
			return $this->minify($this->adaptor->render($template, $cache));
		} else {
		    return $this->adaptor->render($template, $cache);
	    }
	}

	public function minify2($body) {
		// return $body;
        $search = array(
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
            '/(\s)+/s',         // shorten multiple whitespace sequences
        );
        $replace = array(
            '>',
            '<',
            '\\1',
            ''
        );
        $body = preg_replace($search, $replace, $body);
        return $body;
    }

	public function minify($body) {

		return $body;

		$replace = array(
			//remove tabs before and after HTML tags
			'/\>[^\S ]+/s'   => '>',
			'/[^\S ]+\</s'   => '<',
			//shorten multiple whitespace sequences; keep new-line characters because they matter in JS!!!
			'/([\t ])+/s'  => ' ',
			//remove leading and trailing spaces
			'/^([\t ])+/m' => '',
			'/([\t ])+$/m' => '',
			// remove JS line comments (simple only); do NOT remove lines containing URL (e.g. 'src="http://server.com/"')!!!
			'~//[a-zA-Z0-9 ]+$~m' => '',
			//remove empty lines (sequence of line-end and white-space characters)
			'/[\r\n]+([\t ]?[\r\n]+)+/s'  => "\n",
			//remove empty lines (between HTML tags); cannot remove just any line-end characters because in inline JS they can matter!
			'/\>[\r\n\t ]+\</s'    => '><',
			//remove "empty" lines containing only JS's block end character; join with next line (e.g. "}\n}\n</script>" --> "}}</script>"
			'/}[\r\n\t ]+/s'  => '}',
			'/}[\r\n\t ]+,[\r\n\t ]+/s'  => '},',
			//remove new-line after JS's function or condition start; join with next line
			'/\)[\r\n\t ]?{[\r\n\t ]+/s'  => '){',
			'/,[\r\n\t ]?{[\r\n\t ]+/s'  => ',{',
			//remove new-line after JS's line end (only most obvious and safe cases)
			'/\),[\r\n\t ]+/s'  => '),',
			//remove quotes from HTML attributes that does not contain spaces; keep quotes around URLs!
			'~([\r\n\t ])?([a-zA-Z0-9]+)="([a-zA-Z0-9_/\\-]+)"([\r\n\t ])?~s' => '$1$2=$3$4', //$1 and $4 insert first white-space character found before/after attribute
		);
		$body = preg_replace(array_keys($replace), array_values($replace), $body);
	
		//remove optional ending tags (see http://www.w3.org/TR/html5/syntax.html#syntax-tag-omission )
		$remove = array(
			'</option>', '</li>', '</dt>', '</dd>', '</tr>', '</th>', '</td>'
		);
		$body = str_ireplace($remove, '', $body);
	
		return $body;
	}
}
