<?php
/**
 * HTML Compressor Core (stub loader).
 *
 * @since 140417 Initial release.
 * @package websharks\html_compressor
 * @author JasWSInc <https://github.com/JasWSInc>
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 2
 *
 * @note Loads the core class from the PHAR file; if using the PHAR.
 *    Otherwise, the core class is loaded from the source `includes` directory.
 */
namespace websharks\html_compressor
	{
		if(!empty($GLOBALS['is_phar_html_compressor']))
			require_once $GLOBALS['is_phar_html_compressor'].'/includes/core.php';
		else require_once dirname(__FILE__).'/includes/core.php';
	}