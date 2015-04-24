<?php
error_reporting(-1);
ini_set('display_errors', true);

$html            = <<<HTML
<html>
    <head>
        <title>Test</title>
        <script type="text/javascript">
            var test = {
                hello: 'hello',
                world: 'world'
            };
        </script>
        <script type="application/ld+json">
            {
              "@context": "http://schema.org",
              "@type": "Person",
              "name": "John Doe",
              "jobTitle": "Graduate research assistant",
              "affiliation": "University of Dreams",
              "additionalName": "Johnny",
              "url": "http://www.example.com",
              "address": {
                "@type": "PostalAddress",
                "streetAddress": "1234 Peach Drive",
                "addressLocality": "Wonderland",
                "addressRegion": "Georgia"
              }
            }
        </script>
        <style type="text/css">
            #test > .test {
                font-size: 100%;
                color: #FFFFFF;
            }
        </style>
    </head>
    <body>
        Testing one, two, three.
    </body>
</html>
HTML;

$html_compressor_options = array(
    'css_exclusions'                 => array(),
    'js_exclusions'                  => array('.php?'),

    'cache_dir_url_public'           => 'http://example.com/htmlc/cache/public',
    'cache_dir_public'               => dirname(__FILE__).'/htmlc/cache/public',
    'cache_dir_private'              => dirname(__FILE__).'/htmlc/cache/private',

    'current_url_scheme'             => 'http',
    'current_url_host'               => 'www.example.com',
    'current_url_uri'                => '/test.php?one=1&two=2',

    'compress_combine_head_body_css' => true,
    'compress_combine_head_js'       => true,
    'compress_combine_footer_js'     => true,
    'compress_inline_js_code'        => true,
    'compress_css_code'              => true,
    'compress_js_code'               => true,
    'compress_html_code'             => true,

    'benchmark'                      => true,
);
require_once dirname(dirname(__FILE__)).'/src/stub.php';
# require_once dirname(dirname(__FILE__)).'/.~build/websharks-html-compressor.phar';
$html_compressor = new WebSharks\HtmlCompressor\Core($html_compressor_options);
$html            = $html_compressor->compress($html);

echo $html;
