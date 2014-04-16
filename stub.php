<?php
/*
 * HTML Compressor Core
 */
if(!empty($GLOBALS['is_phar_html_compressor']))
	require_once $GLOBALS['is_phar_html_compressor'].'/includes/core.php';
else require_once dirname(__FILE__).'/includes/core.php';