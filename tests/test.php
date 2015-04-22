<?php
error_reporting(-1);
ini_set('display_errors', true);

require_once dirname(dirname(__FILE__)).'/.~build/html-compressor.phar';
$html_compressor_options = array(

    'css_exclusions' => array(),
    'js_exclusions'  => array('.php?'),

    'cache_expiration_time' => '14 days',
    'cache_dir_url_public'  => 'http://example.com/htmlc/cache/public',
    'cache_dir_public'      => $_SERVER['WEBSHARK_HOME'].'/temp/htmlc/cache/public',
    'cache_dir_private'     => $_SERVER['WEBSHARK_HOME'].'/temp/htmlc/cache/private',

    'current_url_scheme' => 'http',
    'current_url_host'   => 'www.example.com',
    'current_url_uri'    => '/raw/file/test.html?one=1&two=2',

    'compress_combine_head_body_css' => true,
    'compress_combine_head_js'       => true,
    'compress_combine_footer_js'     => true,
    'compress_inline_js_code'        => true,
    'compress_css_code'              => true,
    'compress_js_code'               => true,
    'compress_html_code'             => true,

    'benchmark'           => false,
    'product_title'       => 'HTML Compressor',
    'vendor_css_prefixes' => array('moz','webkit','khtml','ms','o'),
);
$html            = '<html> ... </html>';
$html_compressor = new WebSharks\HtmlCompressor\Core($html_compressor_options);
$html            = $html_compressor->compress($html);

echo $html;
