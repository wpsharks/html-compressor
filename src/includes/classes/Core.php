<?php
namespace WebSharks\HtmlCompressor;

/**
 * HTML Compressor (core class).
 *
 * @since 140417 Initial release.
 *
 * @property-read string    $version Read-only access to version string.
 * @property-read array     $options Read-only access to current options.
 * @property-read HookApi   $hook_api Read-only access to hook API class.
 * @property-read Benchmark $benchmark Read-only access to benchmark class.
 */
class Core // Heart of the HTML Compressor.
{
    /********************************************************************************************************/

    /*
     * Private Properties
     */

    /**
     * Current product title.
     *
     * @since 140926 Enhance JS error reporting.
     *
     * @var string Current product title.
     */
    protected $product_title = 'HTML Compressor';

    /**
     * Current version string.
     *
     * @since 140418 Version indicates release date.
     *
     * @var string Dated version string: `YYMMDD`.
     */
    protected $version = '161108'; //version//

    /**
     * An array of class options.
     *
     * @since 140417 Initial release.
     *
     * @var array Set dynamically by class constructor.
     */
    protected $options = [];

    /**
     * Hook API class instance.
     *
     * @since 150321 Adding hook API for plugins.
     *
     * @var HookApi Hook API class instance.
     */
    protected $hook_api; // Class instance.

    /**
     * Benchmark class instance.
     *
     * @since 150315 Adding additional benchmarks.
     *
     * @var Benchmark Filled by various methods.
     */
    protected $benchmark; // Class instance.

    /**
     * Compatible with PHP's `strtotime()` function.
     *
     * @since 140417 Initial release.
     *
     * @note This indicates how long cache files can live.
     *
     * @var string Set dynamically by class constructor.
     */
    protected $cache_expiration_time = '14 days';

    /**
     * Vendor-specific CSS prefixes (regex).
     *
     * @since 140417 Initial release.
     *
     * @var string Set dynamically by class constructor.
     */
    protected $regex_vendor_css_prefixes = '';

    /**
     * Default set of CSS exclusions (array).
     *
     * @since 140417 Initial release.
     *
     * @var array These are used if no option value is supplied to override them.
     */
    protected $default_vendor_css_prefixes = [
        'moz',
        'webkit',
        'khtml',
        'ms',
        'o',
    ];

    /**
     * CSS exclusions (regex).
     *
     * @since 140417 Initial release.
     *
     * @var string Set dynamically by class constructor.
     */
    protected $regex_css_exclusions = '';

    /**
     * Default set of CSS exclusions (array).
     *
     * @since 140417 Initial release.
     *
     * @var array These are used if no option value is supplied to override them.
     */
    protected $default_css_exclusions = [];

    /**
     * Built-in CSS exclusions (regex).
     *
     * @since 140422 Changing the way CSS exclusions operate.
     *
     * @var string Set dynamically by class constructor.
     */
    protected $built_in_regex_css_exclusions = '';

    /**
     * A set of built-in CSS exclusions (regex patterns).
     *
     * @since 140422 Changing the way CSS exclusions operate.
     *
     * @var array These are on at all times; UNLESS options dictate otherwise.
     *            To disable these built-in CSS exclusions pass the option
     *            `disable_built_in_css_exclusions` as TRUE.
     *
     * @note These get converted to a regex pattern by the class constructor.
     *  Reference {@link $built_in_regex_css_exclusions}.
     */
    protected $built_in_regex_css_exclusion_patterns = [
        '\W#post\-[0-9]+\W',
    ];

    /**
     * JS exclusions (regex).
     *
     * @since 140417 Initial release.
     *
     * @var string Set dynamically by class constructor.
     */
    protected $regex_js_exclusions = '';

    /**
     * Default set of JS exclusions (array).
     *
     * @since 140417 Initial release.
     *
     * @var array These are used if no option value is supplied to override them.
     */
    protected $default_js_exclusions = [
        '.php?',
    ];

    /**
     * Built-in JS exclusions (regex).
     *
     * @since 140422 Changing the way JS exclusions operate.
     *
     * @var string Set dynamically by class constructor.
     */
    protected $built_in_regex_js_exclusions = '';

    /**
     * A set of built-in JS exclusions (regex patterns).
     *
     * @since 140422 Changing the way JS exclusions operate.
     *
     * @var array These are on at all times; UNLESS options dictate otherwise.
     *            To disable these built-in JS exclusions pass the option
     *            `disable_built_in_js_exclusions` as TRUE.
     *
     * @note These get converted to a regex pattern by the class constructor.
     *  Reference {@link $built_in_regex_js_exclusions}.
     */
    protected $built_in_regex_js_exclusion_patterns = [
        '\.js#.',
        '\.google\-analytics\.com\/',
        '\Wga\s*\(',
        '\W_gaq\.push\s*\(',
    ];

    /**
     * URI exclusions (regex).
     *
     * @since 160117 Adding URI exclusion support.
     *
     * @var string Set dynamically by class constructor.
     */
    protected $regex_uri_exclusions = '';

    /**
     * Default set of URI exclusions (array).
     *
     * @since 160117 Adding URI exclusion support.
     *
     * @var array These are used if no option value is supplied to override them.
     */
    protected $default_uri_exclusions = [];

    /**
     * Built-in URI exclusions (regex).
     *
     * @since 160117 Adding URI exclusion support.
     *
     * @var string Set dynamically by class constructor.
     */
    protected $built_in_regex_uri_exclusions = '';

    /**
     * A set of built-in URI exclusions (regex patterns).
     *
     * @since 160117 Adding URI exclusion support.
     *
     * @var array These are on at all times; UNLESS options dictate otherwise.
     *            To disable these built-in URI exclusions pass the option
     *            `disable_built_in_uri_exclusions` as TRUE.
     *
     * @note These get converted to a regex pattern by the class constructor.
     *  Reference {@link $built_in_regex_uri_exclusions}.
     */
    protected $built_in_regex_uri_exclusion_patterns = [];

    /**
     * Current base HREF value.
     *
     * @since 140417 Initial release.
     *
     * @var string Set by various routines that work together.
     */
    protected $current_base = '';

    /**
     * Current CSS `@media` value.
     *
     * @since 140519 Improving CSS `@media` query support.
     *
     * @var string Set by various routines that work together.
     */
    protected $current_css_media = '';

    /**
     * Current global exclusion tokens.
     *
     * @since 150821 Adding global exclusion tokenizer.
     *
     * @var array Current global exclusion tokens.
     */
    protected $current_global_exclusion_tokens = [];

    /**
     * Static cache array for this class.
     *
     * @since 140417 Initial release.
     *
     * @var array Used by various routines for optimization.
     */
    protected static $static = [];

    /**
     * Data cache for this class instance.
     *
     * @since 140417 Initial release.
     *
     * @var array Used by various routines for optimization.
     */
    protected $cache = [];

    /********************************************************************************************************/

    /*
     * Constructor (Accepts Options)
     */

    /**
     * Class Constructor.
     *
     * Full instructions and all `$options` are listed in the
     *    [README.md](http://github.com/WebSharks/HTML-Compressor) file.
     *    See: <http://github.com/WebSharks/HTML-Compressor>
     *
     * @since 140417 Initial release.
     *
     * @api Constructor is available for public use.
     *
     * @param array $options Optional array of instance options.
     *                       Check README.md for a list of all possible option keys.
     *                       See: <http://github.com/websharks/html-compressor>
     */
    public function __construct(array $options = [])
    {
        # Set Options

        $this->options = $options; // Config.

        # Benchmark and Hook API instances.

        $this->benchmark = new Benchmark();
        $this->hook_api  = new HookApi();

        # Product Title; i.e., White-Label HTML Compressor

        if (!empty($this->options['product_title']) && is_string($this->options['product_title'])) {
            $this->product_title = (string) $this->options['product_title'];
        }

        # Cache Expiration Time Configuration

        if (!empty($this->options['cache_expiration_time']) && is_string($this->options['cache_expiration_time'])) {
            $this->cache_expiration_time = (string) $this->options['cache_expiration_time'];
        }

        # Vendor-Specific CSS Prefixes

        if (isset($this->options['vendor_css_prefixes']) && is_array($this->options['vendor_css_prefixes'])) {
            $this->regex_vendor_css_prefixes = implode('|', $this->pregQuote($this->options['vendor_css_prefixes']));
        } else {
            $this->regex_vendor_css_prefixes = implode('|', $this->pregQuote($this->default_vendor_css_prefixes));
        }

        # CSS Exclusions (If Applicable)

        if (isset($this->options['regex_css_exclusions']) && is_string($this->options['regex_css_exclusions'])) {
            $this->regex_css_exclusions = $this->options['regex_css_exclusions'];
        } elseif (isset($this->options['css_exclusions']) && is_array($this->options['css_exclusions'])) {
            if ($this->options['css_exclusions']) {
                $this->regex_css_exclusions = '/'.implode('|', $this->pregQuote($this->options['css_exclusions'])).'/ui';
            }
        } elseif ($this->default_css_exclusions) {
            $this->regex_css_exclusions = '/'.implode('|', $this->pregQuote($this->default_css_exclusions)).'/ui';
        }
        if ($this->built_in_regex_css_exclusion_patterns && empty($this->options['disable_built_in_css_exclusions'])) {
            $this->built_in_regex_css_exclusions = '/'.implode('|', $this->built_in_regex_css_exclusion_patterns).'/ui';
        }

        # JavaScript Exclusions (If Applicable)

        if (isset($this->options['regex_js_exclusions']) && is_string($this->options['regex_js_exclusions'])) {
            $this->regex_js_exclusions = $this->options['regex_js_exclusions'];
        } elseif (isset($this->options['js_exclusions']) && is_array($this->options['js_exclusions'])) {
            if ($this->options['js_exclusions']) {
                $this->regex_js_exclusions = '/'.implode('|', $this->pregQuote($this->options['js_exclusions'])).'/ui';
            }
        } elseif ($this->default_js_exclusions) {
            $this->regex_js_exclusions = '/'.implode('|', $this->pregQuote($this->default_js_exclusions)).'/ui';
        }
        if ($this->built_in_regex_js_exclusion_patterns && empty($this->options['disable_built_in_js_exclusions'])) {
            $this->built_in_regex_js_exclusions = '/'.implode('|', $this->built_in_regex_js_exclusion_patterns).'/ui';
        }

        # URI Exclusions; i.e., Exclude from Everything (If Applicable)

        if (isset($this->options['regex_uri_exclusions']) && is_string($this->options['regex_uri_exclusions'])) {
            $this->regex_uri_exclusions = $this->options['regex_uri_exclusions'];
        } elseif (isset($this->options['uri_exclusions']) && is_array($this->options['uri_exclusions'])) {
            if ($this->options['uri_exclusions']) {
                $this->regex_uri_exclusions = '/'.implode('|', $this->pregQuote($this->options['uri_exclusions'])).'/ui';
            }
        } elseif ($this->default_uri_exclusions) {
            $this->regex_uri_exclusions = '/'.implode('|', $this->pregQuote($this->default_uri_exclusions)).'/ui';
        }
        if ($this->built_in_regex_uri_exclusion_patterns && empty($this->options['disable_built_in_uri_exclusions'])) {
            $this->built_in_regex_uri_exclusions = '/'.implode('|', $this->built_in_regex_uri_exclusion_patterns).'/ui';
        }

        # Automatic APM Exclusions; i.e., Auto-Exclude Features (If Applicable)

        if (!isset($this->options['amp_exclusions_enable'])) {
            $this->options['amp_exclusions_enable'] = true;
        }
    }

    /********************************************************************************************************/

    /*
     * Public API Methods
     */

    /**
     * Handles compression. The heart of this class.
     *
     * Full instructions and all `$options` are listed in the
     *    [README.md](http://github.com/WebSharks/HTML-Compressor) file.
     *    See: <http://github.com/WebSharks/HTML-Compressor>
     *
     * @since 140417 Initial release.
     *
     * @api This method is available for public use.
     *
     * @param string $input The input passed into this routine.
     *
     * @return string Compressed HTML code (if at all possible). Note that `$input` must be HTML code.
     *                i.e. It must contain a closing `</html>` tag; otherwise no compression will occur.
     */
    public function compress($input)
    {
        if (!($input = trim((string) $input))) {
            return $input; // Nothing to do.
        }
        if (mb_stripos($input, '</html>') === false) {
            return $input; // Not an HTML doc.
        }
        if ($this->isCurrentUrlUriExcluded()) {
            return $input; // Nothing to do.
        }
        if (($benchmark = !empty($this->options['benchmark']))) {
            $time = microtime(true);
        }
        $html = &$input; // Let's call this HTML now.

        if (!empty($this->options['amp_exclusions_enable']) && $this->isDocAmpd($html)) {
            $this->options['compress_combine_head_body_css'] = false;
            $this->options['compress_combine_head_js']       = false;
            $this->options['compress_combine_footer_js']     = false;
            $this->options['compress_combine_remote_css_js'] = false;
        }
        $html = $this->tokenizeGlobalExclusions($html);
        $html = $this->maybeCompressCombineHeadBodyCss($html);
        $html = $this->maybeCompressCombineHeadJs($html);
        $html = $this->maybeCompressCombineFooterJs($html);
        $html = $this->maybeCompressInlineJsCode($html);
        $html = $this->maybeCompressInlineJsonCode($html);
        $html = $this->restoreGlobalExclusions($html);
        $html = $this->maybeCompressHtmlCode($html);

        if (!isset($this->options['cleanup_cache_dirs']) || $this->options['cleanup_cache_dirs']) {
            if (mt_rand(1, 20) === 1) {
                $this->cleanupCacheDirs();
            }
        }
        if ($benchmark && !empty($time)) {
            $time = number_format(microtime(true) - $time, 5, '.', '');

            if ($this->benchmark->times) {
                $html .= "\n";
            }
            foreach ($this->benchmark->times as $_benchmark_time) {
                $html .= "\n".'<!-- '.sprintf(
                    '%1$s took %2$s seconds %3$s.',
                    htmlspecialchars($this->product_title, ENT_NOQUOTES, 'UTF-8'),
                    htmlspecialchars($_benchmark_time['time'], ENT_NOQUOTES, 'UTF-8'),
                    htmlspecialchars($_benchmark_time['task'], ENT_NOQUOTES, 'UTF-8')
                ).' -->';
            } // unset($_benchmark_time); // Housekeeping.

            $html .= "\n\n".'<!-- '.sprintf(
                '%1$s took %2$s seconds (overall).',
                htmlspecialchars($this->product_title, ENT_NOQUOTES, 'UTF-8'),
                htmlspecialchars($time, ENT_NOQUOTES, 'UTF-8')
            ).' -->';
        }
        return $html; // HTML markup.
    }

    /********************************************************************************************************/

    /*
     * Other API/Magic Methods
     */

    /**
     * Magic method for access to read-only properties.
     *
     * @since 140418 Initial release.
     *
     * @param string $property Propery by name.
     *
     * @throws \Exception If `$property` does not exist for any reason.
     *
     * @return mixed Property value.
     *
     * @internal For internal magic use only.
     */
    public function __get($property)
    {
        $property = (string) $property;

        if (property_exists($this, $property)) {
            return $this->{$property};
        }
        throw new \Exception(sprintf('Undefined property: `%1$s`.', $property));
    }

    /********************************************************************************************************/

    /*
     * Exclusion-Related Methods
     */

    /**
     * Global exclusion tokenizer.
     *
     * @since 150821 Adding global exclusion tokenizer.
     *
     * @param string $html Input HTML code.
     *
     * @return string HTML code, after tokenizing exclusions.
     */
    protected function tokenizeGlobalExclusions($html)
    {
        $html              = (string) $html;
        $_this             = $this;
        $global_exclusions = [
            '/\<noscript(?:\s[^>]*)?\>.*?\<\/noscript\>/uis',
        ];
        $html = preg_replace_callback(
            $global_exclusions,
            function ($m) use ($_this) {
                $_this->current_global_exclusion_tokens[] = $m[0]; // Tokenize.
                return '<htmlc-gxt-'.(count($_this->current_global_exclusion_tokens) - 1).' />';
            },
            $html // Exclusions replaced by tokens.
        );
        return $html;
    }

    /**
     * Restore global exclusions.
     *
     * @since 150821 Adding global exclusion tokenizer.
     *
     * @param string $html Input HTML code.
     *
     * @return string HTML code, after restoring exclusions.
     */
    protected function restoreGlobalExclusions($html)
    {
        $html = (string) $html;

        if (!$this->current_global_exclusion_tokens) {
            return $html; // Nothing to restore.
        }
        if (mb_stripos($html, '<htmlc-gxt-') === false) {
            return $html; // Nothing to restore.
        }
        foreach (array_reverse($this->current_global_exclusion_tokens, true) as $_token => $_value) {
            // Must go in reverse order so nested tokens unfold properly.
            $html = str_ireplace('<htmlc-gxt-'.$_token.' />', $_value, $html);
        } // unset($_token, $_value); // Housekeeping.

        $this->current_global_exclusion_tokens = [];

        return $html;
    }

    /**
     * Document is AMPd?
     *
     * @since 16xxxx AMP exclusions.
     *
     * @param string $html HTML markup.
     *
     * @return bool Returns `TRUE` if AMPd.
     */
    protected function isDocAmpd($html)
    {
        $html = (string) $html;

        if (preg_match('/\/amp\/(?:$|[?&#])/ui', $this->currentUrlUri())) {
            return true;
        } elseif ($html && preg_match('/\<html(?:\s[^>]+?\s|\s)(?:⚡|amp)[\s=\>]/ui', $html)) {
            return true;
        }
        return false;
    }

    /********************************************************************************************************/

    /*
     * CSS-Related Methods
     * ~ See also: CSS Compression Utilities
     */

    /**
     * Handles possible compression of head/body CSS.
     *
     * @since 140417 Initial release.
     *
     * @param string $html Input HTML code.
     *
     * @return string HTML code, after possible CSS compression.
     */
    protected function maybeCompressCombineHeadBodyCss($html)
    {
        if (($benchmark = !empty($this->options['benchmark']) && $this->options['benchmark'] === 'details')) {
            $time = microtime(true);
        }
        $html = (string) $html; // Force string value.

        if (isset($this->options['compress_combine_head_body_css'])) {
            if (!$this->options['compress_combine_head_body_css']) {
                $disabled = true; // Disabled flag.
            }
        }
        if (!$html || !empty($disabled)) {
            goto finale; // Nothing to do.
        }
        if (($html_frag = $this->getHtmlFrag($html)) && ($head_frag = $this->getHeadFrag($html))) {
            if (($css_tag_frags = $this->getCssTagFrags($html_frag)) && ($css_parts = $this->compileCssTagFragsIntoParts($css_tag_frags, 'head'))) {
                $css_tag_frags_all_compiled = $this->compileKeyElementsDeep($css_tag_frags, 'all');
                $html                       = $this->replaceOnce($head_frag['all'], '%%htmlc-head%%', $html);
                $html                       = $this->replaceOnce($css_tag_frags_all_compiled, '', $html);
                $cleaned_head_contents      = $this->replaceOnce($css_tag_frags_all_compiled, '', $head_frag['contents']);
                $cleaned_head_contents      = $this->cleanupSelfClosingHtmlTagLines($cleaned_head_contents);

                $compressed_css_tags = []; // Initialize.

                foreach ($css_parts as $_css_part) {
                    if (isset($_css_part['exclude_frag'], $css_tag_frags[$_css_part['exclude_frag']]['all'])) {
                        $compressed_css_tags[] = $css_tag_frags[$_css_part['exclude_frag']]['all'];
                    } else {
                        $compressed_css_tags[] = $_css_part['tag'];
                    }
                } // unset($_css_part); // Housekeeping.

                $compressed_css_tags   = implode("\n", $compressed_css_tags);
                $compressed_head_parts = [$head_frag['open_tag'], $cleaned_head_contents, $compressed_css_tags, $head_frag['closing_tag']];
                $html                  = $this->replaceOnce('%%htmlc-head%%', implode("\n", $compressed_head_parts), $html);

                if ($benchmark) {
                    $this->benchmark->addData(
                        __FUNCTION__,
                        compact(
                            'head_frag',
                            'css_tag_frags',
                            'css_parts',
                            'cleaned_head_contents',
                            'compressed_css_tags',
                            'compressed_head_parts'
                        )
                    );
                }
            }
        }
        finale: // Target point; finale/return value.

        if ($html) {
            $html = trim($html);
        } // Trim it up now!

        if ($benchmark && !empty($time) && $html && empty($disabled)) {
            $this->benchmark->addTime(
                __FUNCTION__,
                $time, // Caller, start time, task performed.
                sprintf('compressing/combining head/body CSS in checksum: `%1$s`', md5($html))
            );
        }
        return $html; // With possible compression having been applied here.
    }

    /**
     * Compiles CSS tag fragments into CSS parts with compression.
     *
     * @since 140417 Initial release.
     *
     * @param array  $css_tag_frags CSS tag fragments.
     * @param string $for           Where will these parts go? One of `head`, `body`, `foot`.
     *
     * @throws \Exception If unable to cache CSS parts.
     *
     * @return array Array of CSS parts, else an empty array on failure.
     */
    protected function compileCssTagFragsIntoParts(array $css_tag_frags, $for)
    {
        if (($benchmark = !empty($this->options['benchmark']) && $this->options['benchmark'] === 'details')) {
            $time = microtime(true);
        }
        $for                = (string) $for; // Force string.
        $css_parts          = []; // Initialize.
        $css_parts_checksum = ''; // Initialize.

        if (!$css_tag_frags) {
            goto finale; // Nothing to do.
        }
        $css_parts_checksum   = $this->getTagFragsChecksum($css_tag_frags);
        $public_cache_dir     = $this->cacheDir($this::DIR_PUBLIC_TYPE, $css_parts_checksum);
        $private_cache_dir    = $this->cacheDir($this::DIR_PRIVATE_TYPE, $css_parts_checksum);
        $public_cache_dir_url = $this->cacheDirUrl($this::DIR_PUBLIC_TYPE, $css_parts_checksum);

        $cache_parts_file          = $css_parts_checksum.'-compressor-parts.css-cache';
        $cache_parts_file_path     = $private_cache_dir.'/'.$cache_parts_file;
        $cache_parts_file_path_tmp = $cache_parts_file_path.'.'.uniqid('', true).'.tmp';
        // Cache file creation is atomic; i.e. tmp file w/ rename.

        $cache_part_file      = '%%code-checksum%%-compressor-part.css';
        $cache_part_file_path = $public_cache_dir.'/'.$cache_part_file;
        $cache_part_file_url  = $public_cache_dir_url.'/'.$cache_part_file;

        if (is_file($cache_parts_file_path) && filemtime($cache_parts_file_path) > strtotime('-'.$this->cache_expiration_time)) {
            if (is_array($cached_parts = unserialize(file_get_contents($cache_parts_file_path)))) {
                $css_parts = $cached_parts; // Use cached parts.
                goto finale; // Using the cache; all done here.
            }
        }
        $_css_part                = 0; // Initialize part counter.
        $_last_css_tag_frag_media = 'all'; // Initialize.

        foreach ($css_tag_frags as $_css_tag_frag_pos => $_css_tag_frag) {
            if ($_css_tag_frag['exclude']) {
                if ($_css_tag_frag['link_href'] || $_css_tag_frag['style_css']) {
                    if ($css_parts) {
                        ++$_css_part; // Starts new part.
                    }
                    $css_parts[$_css_part]['tag']          = '';
                    $css_parts[$_css_part]['exclude_frag'] = $_css_tag_frag_pos;
                    ++$_css_part; // Always indicates a new part in the next iteration.
                }
            } elseif ($_css_tag_frag['link_href']) {
                if (($_css_tag_frag['link_href'] = $this->resolveRelativeUrl($_css_tag_frag['link_href']))) {
                    if (($_css_code = $this->stripUtf8Bom($this->mustGetUrl($_css_tag_frag['link_href'])))) {
                        $_css_code = $this->resolveCssRelatives($_css_code, $_css_tag_frag['link_href']);
                        $_css_code = $this->resolveResolvedCssImports($_css_code, $_css_tag_frag['media']);

                        if ($_css_code) {
                            if ($_css_tag_frag['media'] !== $_last_css_tag_frag_media) {
                                ++$_css_part; // Starts new part; different `@media` spec here.
                            } elseif (!empty($css_parts[$_css_part]['code']) && mb_stripos($css_parts[$_css_part]['code'], '@import') !== false) {
                                ++$_css_part; // Starts new part; existing code contains an @import.
                            }
                            $css_parts[$_css_part]['media'] = $_css_tag_frag['media'];

                            if (!empty($css_parts[$_css_part]['code'])) {
                                $css_parts[$_css_part]['code'] .= "\n\n".$_css_code;
                            } else {
                                $css_parts[$_css_part]['code'] = $_css_code;
                            }
                        }
                    }
                }
            } elseif ($_css_tag_frag['style_css']) {
                $_css_code = $_css_tag_frag['style_css'];
                $_css_code = $this->stripUtf8Bom($_css_code);
                $_css_code = $this->resolveCssRelatives($_css_code);
                $_css_code = $this->resolveResolvedCssImports($_css_code, $_css_tag_frag['media']);

                if ($_css_code) {
                    if ($_css_tag_frag['media'] !== $_last_css_tag_frag_media) {
                        ++$_css_part; // Starts new part; different `@media` spec here.
                    } elseif (!empty($css_parts[$_css_part]['code']) && mb_stripos($css_parts[$_css_part]['code'], '@import') !== false) {
                        ++$_css_part; // Starts new part; existing code contains an @import.
                    }
                    $css_parts[$_css_part]['media'] = $_css_tag_frag['media'];

                    if (!empty($css_parts[$_css_part]['code'])) {
                        $css_parts[$_css_part]['code'] .= "\n\n".$_css_code;
                    } else {
                        $css_parts[$_css_part]['code'] = $_css_code;
                    }
                }
            }
            $_last_css_tag_frag_media = $_css_tag_frag['media'];
        } // unset($_css_part, $_last_css_tag_frag_media, $_css_tag_frag_pos, $_css_tag_frag, $_css_code);

        foreach (array_keys($css_parts = array_values($css_parts)) as $_css_part) {
            if (!isset($css_parts[$_css_part]['exclude_frag']) && !empty($css_parts[$_css_part]['code'])) {
                $_css_media = 'all'; // Default media value; i.e., `all` media queries.
                if (!empty($css_parts[$_css_part]['media'])) {
                    $_css_media = $css_parts[$_css_part]['media'];
                }
                $_css_code    = $css_parts[$_css_part]['code'];
                $_css_code    = $this->moveSpecialCssAtRulesToTop($_css_code);
                $_css_code    = $this->stripPrependCssCharsetUtf8($_css_code);
                $_css_code    = $this->forceAbsRelativePathsInCss($_css_code);
                $_css_code    = $this->maybeFilterCssUrls($_css_code);
                $_css_code_cs = md5($_css_code); // Before compression.
                $_css_code    = $this->maybeCompressCssCode($_css_code);

                $_css_code_path     = str_replace('%%code-checksum%%', $_css_code_cs, $cache_part_file_path);
                $_css_code_url      = str_replace('%%code-checksum%%', $_css_code_cs, $cache_part_file_url);
                $_css_code_url      = $this->hook_api->applyFilters('part_url', $_css_code_url, $for);
                $_css_code_path_tmp = $_css_code_path.'.'.uniqid('', true).'.tmp';
                // Cache file creation is atomic; i.e. tmp file w/ rename.

                if (!(file_put_contents($_css_code_path_tmp, $_css_code) && rename($_css_code_path_tmp, $_css_code_path))) {
                    throw new \Exception(sprintf('Unable to cache CSS code file: `%1$s`.', $_css_code_path));
                }
                $css_parts[$_css_part]['tag'] = '<link type="text/css" rel="stylesheet" href="'.htmlspecialchars($_css_code_url, ENT_QUOTES, 'UTF-8').'" media="'.htmlspecialchars($_css_media, ENT_QUOTES, 'UTF-8').'" />';

                unset($css_parts[$_css_part]['code']); // Ditch this; no need to cache this code too.
            }
        } // unset($_css_part, $_css_media, $_css_code, $_css_code_cs, $_css_code_path, $_css_code_path_tmp, $_css_code_url);

        if (!(file_put_contents($cache_parts_file_path_tmp, serialize($css_parts)) && rename($cache_parts_file_path_tmp, $cache_parts_file_path))) {
            throw new \Exception(sprintf('Unable to cache CSS parts into: `%1$s`.', $cache_parts_file_path));
        }
        finale: // Target point; finale/return value.

        if ($benchmark && !empty($time) && $css_parts_checksum) {
            $this->benchmark->addTime(
                __FUNCTION__,
                $time, // Caller, start time, task performed.
                sprintf('building parts based on CSS tag frags in checksum: `%1$s`', $css_parts_checksum)
            );
        }
        return $css_parts;
    }

    /**
     * Parses and returns an array of CSS tag fragments.
     *
     * @since 140417 Initial release.
     *
     * @param array $html_frag An HTML tag fragment array.
     *
     * @return array An array of CSS tag fragments (ready to be converted into CSS parts).
     *               Else an empty array (i.e. no CSS tag fragments in the HTML fragment array).
     *
     * @see http://css-tricks.com/how-to-create-an-ie-only-stylesheet/
     * @see http://stackoverflow.com/a/12102131
     */
    protected function getCssTagFrags(array $html_frag)
    {
        if (($benchmark = !empty($this->options['benchmark']) && $this->options['benchmark'] === 'details')) {
            $time = microtime(true);
        }
        $css_tag_frags = []; // Initialize.

        if (!$html_frag) {
            goto finale; // Nothing to do.
        }
        $regex = '/(?P<all>'.// Entire match.
                 '(?P<if_open_tag>\<\![^[>]*?\[if\W[^\]]*?\][^>]*?\>\s*)?'.
                 '(?:(?P<link_self_closing_tag>\<link(?:\s+[^>]*?)?\>)'.// Or a <style></style> tag.
                 '|(?P<style_open_tag>\<style(?:\s+[^>]*?)?\>)(?P<style_css>.*?)(?P<style_closing_tag>\<\/style\>))'.
                 '(?P<if_closing_tag>\s*\<\![^[>]*?\[endif\][^>]*?\>)?'.
                 ')/uis'; // Dot matches line breaks.

        if (!empty($html_frag['contents']) && preg_match_all($regex, $html_frag['contents'], $_tag_frags, PREG_SET_ORDER)) {
            foreach ($_tag_frags as $_tag_frag) {
                $_link_href = $_style_css = $_media = ''; // Initialize.

                if (($_link_href = $this->getLinkCssHref($_tag_frag, true))) {
                    $_media = $this->getLinkCssMedia($_tag_frag, false);
                } elseif (($_style_css = $this->getStyleCss($_tag_frag, true))) {
                    $_media = $this->getStyleCssMedia($_tag_frag, false);
                }
                if ($_link_href || $_style_css) {
                    $css_tag_frags[] = [
                        'all' => $_tag_frag['all'],

                        'if_open_tag'    => isset($_tag_frag['if_open_tag']) ? $_tag_frag['if_open_tag'] : '',
                        'if_closing_tag' => isset($_tag_frag['if_closing_tag']) ? $_tag_frag['if_closing_tag'] : '',

                        'link_self_closing_tag' => isset($_tag_frag['link_self_closing_tag']) ? $_tag_frag['link_self_closing_tag'] : '',
                        'link_href_external'    => ($_link_href) ? $this->isUrlExternal($_link_href) : false,
                        'link_href'             => $_link_href, // This could also be empty.

                        'style_open_tag'    => isset($_tag_frag['style_open_tag']) ? $_tag_frag['style_open_tag'] : '',
                        'style_css'         => $_style_css, // This could also be empty.
                        'style_closing_tag' => isset($_tag_frag['style_closing_tag']) ? $_tag_frag['style_closing_tag'] : '',

                        'media' => $_media ? $_media : 'all', // Default value.

                        'exclude' => false, // Default value.
                    ];
                    $_tag_frag_r = &$css_tag_frags[count($css_tag_frags) - 1];

                    if ($_tag_frag_r['if_open_tag'] || $_tag_frag_r['if_closing_tag']) {
                        $_tag_frag_r['exclude'] = true;
                    } elseif ($_tag_frag_r['link_href'] && $_tag_frag_r['link_href_external'] && isset($this->options['compress_combine_remote_css_js']) && !$this->options['compress_combine_remote_css_js']) {
                        $_tag_frag_r['exclude'] = true;
                    } elseif ($this->regex_css_exclusions && preg_match($this->regex_css_exclusions, $_tag_frag_r['link_self_closing_tag'].' '.$_tag_frag_r['style_open_tag'].' '.$_tag_frag_r['style_css'])) {
                        $_tag_frag_r['exclude'] = true;
                    } elseif ($this->built_in_regex_css_exclusions && preg_match($this->built_in_regex_css_exclusions, $_tag_frag_r['link_self_closing_tag'].' '.$_tag_frag_r['style_open_tag'].' '.$_tag_frag_r['style_css'])) {
                        $_tag_frag_r['exclude'] = true;
                    }
                }
            }
        } // unset($_tag_frags, $_tag_frag, $_tag_frag_r, $_link_href, $_style_css, $_media);

        finale: // Target point; finale/return value.

        if ($benchmark && !empty($time) && $html_frag) {
            $this->benchmark->addTime(
                __FUNCTION__,
                $time, // Caller, start time, task performed.
                sprintf('compiling CSS tag frags in checksum: `%1$s`', md5(serialize($html_frag)))
            );
        }
        return $css_tag_frags;
    }

    /**
     * Test a tag fragment to see if it's CSS.
     *
     * @since 140922 Improving tag tests.
     *
     * @param array $tag_frag A tag fragment.
     *
     * @return bool TRUE if it contains CSS.
     */
    protected function isLinkTagFragCss(array $tag_frag)
    {
        if (empty($tag_frag['link_self_closing_tag'])) {
            return false; // Nope; missing tag.
        }
        $type = $rel = ''; // Initialize.

        if (mb_stripos($tag_frag['link_self_closing_tag'], 'type') !== 0) {
            if (preg_match('/\stype\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['link_self_closing_tag'], $_m)) {
                $type = $_m['value'];
            }
        } // unset($_m); // Just a little housekeeping.

        if (mb_stripos($tag_frag['link_self_closing_tag'], 'rel') !== 0) {
            if (preg_match('/\srel\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['link_self_closing_tag'], $_m)) {
                $rel = $_m['value'];
            }
        } // unset($_m); // Just a little housekeeping.

        if ($type && mb_stripos($type, 'css') === false) {
            return false; // Not CSS.
        }
        if ($rel && mb_stripos($rel, 'stylesheet') === false) {
            return false; // Not CSS.
        }
        return true; // Yes, this is CSS.
    }

    /**
     * Test a tag fragment to see if it's CSS.
     *
     * @since 140922 Improving tag tests.
     *
     * @param array $tag_frag A tag fragment.
     *
     * @return bool TRUE if it contains CSS.
     */
    protected function isStyleTagFragCss(array $tag_frag)
    {
        if (empty($tag_frag['style_open_tag']) || empty($tag_frag['style_closing_tag'])) {
            return false; // Nope; missing open|closing tag.
        }
        $type = ''; // Initialize.

        if (mb_stripos($tag_frag['style_open_tag'], 'type') !== 0) {
            if (preg_match('/\stype\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['style_open_tag'], $_m)) {
                $type = $_m['value'];
            }
        } // unset($_m); // Just a little housekeeping.

        if ($type && mb_stripos($type, 'css') === false) {
            return false; // Not CSS.
        }
        return true; // Yes, this is CSS.
    }

    /**
     * Get a CSS link href value from a tag fragment.
     *
     * @since 140417 Initial release.
     *
     * @param array $tag_frag     A CSS tag fragment.
     * @param bool  $test_for_css Defaults to a TRUE value.
     *                            If TRUE, we will test tag fragment to make sure it's CSS.
     *
     * @return string The link href value if possible; else an empty string.
     */
    protected function getLinkCssHref(array $tag_frag, $test_for_css = true)
    {
        if ($test_for_css && !$this->isLinkTagFragCss($tag_frag)) {
            return ''; // This tag does not contain CSS.
        }
        if (preg_match('/\shref\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['link_self_closing_tag'], $_m)) {
            return trim($this->nUrlAmps($_m['value']));
        } // unset($_m); // Just a little housekeeping.

        return ''; // Unable to find an `href` attribute value.
    }

    /**
     * Get a CSS link media rule from a tag fragment.
     *
     * @since 140417 Initial release.
     *
     * @param array $tag_frag     A CSS tag fragment.
     * @param bool  $test_for_css Defaults to a TRUE value.
     *                            If TRUE, we will test tag fragment to make sure it's CSS.
     *
     * @return string The link media value if possible; else an empty string.
     */
    protected function getLinkCssMedia(array $tag_frag, $test_for_css = true)
    {
        if ($test_for_css && !$this->isLinkTagFragCss($tag_frag)) {
            return ''; // This tag does not contain CSS.
        }
        if (preg_match('/\smedia\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['link_self_closing_tag'], $_m)) {
            return trim(mb_strtolower($_m['value']));
        } // unset($_m); // Just a little housekeeping.

        return ''; // Unable to find a `media` attribute value.
    }

    /**
     * Get a CSS style media rule from a tag fragment.
     *
     * @since 140417 Initial release.
     *
     * @param array $tag_frag     A CSS tag fragment.
     * @param bool  $test_for_css Defaults to a TRUE value.
     *                            If TRUE, we will test tag fragment to make sure it's CSS.
     *
     * @return string The style media value if possible; else an empty string.
     */
    protected function getStyleCssMedia(array $tag_frag, $test_for_css = true)
    {
        if ($test_for_css && !$this->isStyleTagFragCss($tag_frag)) {
            return ''; // This tag does not contain CSS.
        }
        if (preg_match('/\smedia\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['style_open_tag'], $_m)) {
            return trim(mb_strtolower($_m['value']));
        } // unset($_m); // Just a little housekeeping.

        return ''; // Unable to find a `media` attribute value.
    }

    /**
     * Get style CSS from a CSS tag fragment.
     *
     * @since 140417 Initial release.
     *
     * @param array $tag_frag     A CSS tag fragment.
     * @param bool  $test_for_css Defaults to a TRUE value.
     *                            If TRUE, we will test tag fragment to make sure it's CSS.
     *
     * @return string The style CSS code (if possible); else an empty string.
     */
    protected function getStyleCss(array $tag_frag, $test_for_css = true)
    {
        if (empty($tag_frag['style_css'])) {
            return ''; // Not possible; no CSS code.
        }
        if ($test_for_css && !$this->isStyleTagFragCss($tag_frag)) {
            return ''; // This tag does not contain CSS.
        }
        return trim($tag_frag['style_css']); // CSS code.
    }

    /**
     * Strip existing charset rules from CSS code.
     *
     * @since 140417 Initial release.
     *
     * @param string $css CSS code.
     *
     * @return string CSS after having stripped away existing charset rules.
     */
    protected function stripExistingCssCharsets($css)
    {
        if (!($css = (string) $css)) {
            return $css; // Nothing to do.
        }
        $css = preg_replace('/@(?:\-(?:'.$this->regex_vendor_css_prefixes.')\-)?charset(?:\s+[^;]*?)?;/ui', '', $css);
        if ($css) {
            $css = trim($css);
        }
        return $css;
    }

    /**
     * Strip existing charsets and add a UTF-8 `@charset` rule.
     *
     * @since 140417 Initial release.
     *
     * @param string $css CSS code.
     *
     * @return string CSS code (possibly with a prepended UTF-8 charset rule).
     */
    protected function stripPrependCssCharsetUtf8($css)
    {
        if (!($css = (string) $css)) {
            return $css; // Nothing to do.
        }
        $css = $this->stripExistingCssCharsets($css);
        if ($css) {
            $css = '@charset "UTF-8";'."\n".$css;
        }
        return $css;
    }

    /**
     * Moves special CSS `@rules` to the top.
     *
     * @since 140417 Initial release.
     *
     * @param string $css          CSS code.
     * @param int    $___recursion Internal use only.
     *
     * @return string CSS code after having moved special `@rules` to the top.
     *
     * @see <https://developer.mozilla.org/en-US/docs/Web/CSS/@charset>
     * @see <http://stackoverflow.com/questions/11746581/nesting-media-rules-in-css>
     */
    protected function moveSpecialCssAtRulesToTop($css, $___recursion = 0)
    {
        if (!($css = (string) $css)) {
            return $css; // Nothing to do.
        }
        $max_recursions = 2; // `preg_match_all()` calls.
        if ($___recursion >= $max_recursions) {
            return $css; // All done.
        }
        if (mb_stripos($css, 'charset') === false && mb_stripos($css, 'import') === false) {
            return $css; // Save some time. Nothing to do here.
        }
        if (preg_match_all('/(?P<rule>@(?:\-(?:'.$this->regex_vendor_css_prefixes.')\-)?charset(?:\s+[^;]*?)?;)/ui', $css, $rules, PREG_SET_ORDER)
           || preg_match_all('/(?P<rule>@(?:\-(?:'.$this->regex_vendor_css_prefixes.')\-)?import(?:\s+[^;]*?)?;)/ui', $css, $rules, PREG_SET_ORDER)
        ) { // Searched in a specific order. Recursion dictates a precise order.
            $top_rules = []; // Initialize.
            foreach ($rules as $_rule) {
                $top_rules[] = $_rule['rule'];
            } // unset($_rule); // Just a little housekeeping.

            $css = $this->replaceOnce($top_rules, '', $css);
            $css = $this->moveSpecialCssAtRulesToTop($css, $___recursion + 1);
            $css = implode("\n\n", $top_rules)."\n\n".$css;
        }
        return $css; // With special `@rules` to the top.
    }

    /**
     * Resolves `@import` rules in CSS code recursively.
     *
     * @since 140417 Initial release.
     *
     * @param string $css          CSS code.
     * @param string $media        Current media specification.
     * @param bool   $___recursion Internal use only.
     *
     * @return string CSS code after all `@import` rules have been resolved recursively.
     */
    protected function resolveResolvedCssImports($css, $media, $___recursion = false)
    {
        if (!($css = (string) $css)) {
            return $css; // Nothing to do.
        }
        $media = $this->current_css_media = (string) $media;
        if (!$media) {
            $media = $this->current_css_media = 'all';
        }
        $import_media_without_url_regex = '/@(?:\-(?:'.$this->regex_vendor_css_prefixes.')\-)?import\s*(["\'])(?P<url>.+?)\\1(?P<media>[^;]*?);/ui';
        $import_media_with_url_regex    = '/@(?:\-(?:'.$this->regex_vendor_css_prefixes.')\-)?import\s+url\s*\(\s*(["\']?)(?P<url>.+?)\\1\s*\)(?P<media>[^;]*?);/ui';

        $css = preg_replace_callback($import_media_without_url_regex, [$this, 'resolveResolvedCssImportsCb'], $css);
        $css = preg_replace_callback($import_media_with_url_regex, [$this, 'resolveResolvedCssImportsCb'], $css);

        if (preg_match_all($import_media_without_url_regex, $css, $_m)) {
            foreach ($_m['media'] as $_media) {
                if (!$_media || $_media === $this->current_css_media) {
                    return $this->resolveResolvedCssImports($css, $this->current_css_media, true);
                }
            } // unset($_media);
        } // unset($_m); // Housekeeping.

        if (preg_match_all($import_media_with_url_regex, $css, $_m)) {
            foreach ($_m['media'] as $_media) {
                if (!$_media || $_media === $this->current_css_media) {
                    return $this->resolveResolvedCssImports($css, $this->current_css_media, true);
                }
            } // unset($_media);
        } // unset($_m); // Housekeeping.

        return $css;
    }

    /**
     * Callback handler for resolving @ import rules.
     *
     * @since 140417 Initial release.
     *
     * @param array $m An array of regex matches.
     *
     * @return string CSS after import resolution, else an empty string.
     */
    protected function resolveResolvedCssImportsCb(array $m)
    {
        if (empty($m['url'])) {
            return ''; // Nothing to resolve.
        }
        if (!empty($m['media']) && $m['media'] !== $this->current_css_media) {
            return $m[0]; // Not possible; different media.
        }
        if (($css = $this->stripUtf8Bom($this->mustGetUrl($m['url'])))) {
            $css = $this->resolveCssRelatives($css, $m['url']);
        }
        return $css;
    }

    /**
     * Resolve relative URLs in CSS code.
     *
     * @since 140417 Initial release.
     *
     * @param string $css  CSS code.
     * @param string $base Optional. Base URL to calculate from.
     *                     Defaults to the current HTTP location for the browser.
     *
     * @return string CSS code after having all URLs resolved.
     */
    protected function resolveCssRelatives($css, $base = '')
    {
        if (!($css = (string) $css)) {
            return $css; // Nothing to do.
        }
        $this->current_base = $base; // Make this available to callback handlers (possible empty string here).

        $import_without_url_regex = '/(?P<import>@(?:\-(?:'.$this->regex_vendor_css_prefixes.')\-)?import\s*)(?P<open_encap>["\'])(?P<url>.+?)(?P<close_encap>\\2)/ui';
        $any_url_regex            = '/(?P<url_>url\s*)(?P<open_bracket>\(\s*)(?P<open_encap>["\']?)(?P<url>.+?)(?P<close_encap>\\3)(?P<close_bracket>\s*\))/ui';

        $css = preg_replace_callback($import_without_url_regex, [$this, 'resolveCssRelativesImportCb'], $css);
        $css = preg_replace_callback($any_url_regex, [$this, 'resolveCssRelativesUrlCb'], $css);

        return $css;
    }

    /**
     * Callback handler for CSS relative URL resolutions.
     *
     * @since 140417 Initial release.
     *
     * @param array $m An array of regex matches.
     *
     * @return string CSS `@import` rule with relative URL resolved.
     */
    protected function resolveCssRelativesImportCb(array $m)
    {
        return $m['import'].$m['open_encap'].$this->resolveRelativeUrl($m['url'], $this->current_base).$m['close_encap'];
    }

    /**
     * Callback handler for CSS relative URL resolutions.
     *
     * @since 140417 Initial release.
     *
     * @param array $m An array of regex matches.
     *
     * @return string CSS `url()` resource with relative URL resolved.
     */
    protected function resolveCssRelativesUrlCb(array $m)
    {
        if (mb_stripos($m['url'], 'data:') === 0) {
            return $m[0]; // Don't resolve `data:` URIs.
        }
        return $m['url_'].$m['open_bracket'].$m['open_encap'].$this->resolveRelativeUrl($m['url'], $this->current_base).$m['close_encap'].$m['close_bracket'];
    }

    /**
     * Force absolute relative paths in CSS.
     *
     * @since 150511 Improving CSS handling.
     *
     * @param string $css Raw CSS code.
     *
     * @return string CSS code (possibly altered here).
     */
    protected function forceAbsRelativePathsInCss($css)
    {
        if (!($css = (string) $css)) {
            return $css; // Nothing to do.
        }
        $regex = '/(?:[a-z0-9]+\:)?\/\/'.$this->pregQuote($this->currentUrlHost()).'\//ui';

        return preg_replace($regex, '/', $css); // Absolute relative paths.
    }

    /**
     * Maybe filter URLs in CSS code.
     *
     * @since 150821 Adding URL filter support.
     *
     * @param string $css CSS code.
     *
     * @return string CSS code after having filtered all URLs.
     */
    protected function maybeFilterCssUrls($css)
    {
        if (!($css = (string) $css)) {
            return $css; // Nothing to do.
        }
        if (!$this->hook_api->hasFilter('css_url()')) {
            return $css; // No reason to do this.
        }
        $import_without_url_regex = '/(?P<import>@(?:\-(?:'.$this->regex_vendor_css_prefixes.')\-)?import\s*)(?P<open_encap>["\'])(?P<url>.+?)(?P<close_encap>\\2)/ui';
        $any_url_regex            = '/(?P<url_>url\s*)(?P<open_bracket>\(\s*)(?P<open_encap>["\']?)(?P<url>.+?)(?P<close_encap>\\3)(?P<close_bracket>\s*\))/ui';

        $css = preg_replace_callback($import_without_url_regex, [$this, 'filterCssUrlImportCb'], $css);
        $css = preg_replace_callback($any_url_regex, [$this, 'filterCssUrlCb'], $css);

        return $css;
    }

    /**
     * Callback handler for CSS import URL filters.
     *
     * @since 150821 Adding URL filter support.
     *
     * @param array $m An array of regex matches.
     *
     * @return string CSS `@import` rule with filtered URL.
     */
    protected function filterCssUrlImportCb(array $m)
    {
        return $m['import'].$m['open_encap'].$this->hook_api->applyFilters('css_url()', $m['url']).$m['close_encap'];
    }

    /**
     * Callback handler for CSS URL filters.
     *
     * @since 150821 Adding URL filter support.
     *
     * @param array $m An array of regex matches.
     *
     * @return string CSS `url()` resource with with filtered URL.
     */
    protected function filterCssUrlCb(array $m)
    {
        if (mb_stripos($m['url'], 'data:') === 0) {
            return $m[0]; // Don't filter `data:` URIs.
        }
        return $m['url_'].$m['open_bracket'].$m['open_encap'].$this->hook_api->applyFilters('css_url()', $m['url']).$m['close_encap'].$m['close_bracket'];
    }

    /********************************************************************************************************/

    /*
     * JS-Related Methods
     * ~ See also: JS Compression Utilities
     */

    /**
     * Handles possible compression of head JS.
     *
     * @since 140417 Initial release.
     *
     * @param string $html Input HTML code.
     *
     * @return string HTML code, after possible JS compression.
     */
    protected function maybeCompressCombineHeadJs($html)
    {
        if (($benchmark = !empty($this->options['benchmark']) && $this->options['benchmark'] === 'details')) {
            $time = microtime(true);
        }
        $html = (string) $html; // Force string value.

        if (isset($this->options['compress_combine_head_js'])) {
            if (!$this->options['compress_combine_head_js']) {
                $disabled = true; // Disabled flag.
            }
        }
        if (!$html || !empty($disabled)) {
            goto finale; // Nothing to do.
        }
        if (($head_frag = $this->getHeadFrag($html)) /* No need to get the HTML frag here; we're operating on the `<head>` only. */) {
            if (($js_tag_frags = $this->getJsTagFrags($head_frag)) && ($js_parts = $this->compileJsTagFragsIntoParts($js_tag_frags, 'head'))) {
                $js_tag_frags_all_compiled = $this->compileKeyElementsDeep($js_tag_frags, 'all');
                $html                      = $this->replaceOnce($head_frag['all'], '%%htmlc-head%%', $html);
                $cleaned_head_contents     = $this->replaceOnce($js_tag_frags_all_compiled, '', $head_frag['contents']);
                $cleaned_head_contents     = $this->cleanupSelfClosingHtmlTagLines($cleaned_head_contents);

                $compressed_js_tags = []; // Initialize.

                foreach ($js_parts as $_js_part) {
                    if (isset($_js_part['exclude_frag'], $js_tag_frags[$_js_part['exclude_frag']]['all'])) {
                        $compressed_js_tags[] = $js_tag_frags[$_js_part['exclude_frag']]['all'];
                    } else {
                        $compressed_js_tags[] = $_js_part['tag'];
                    }
                } // unset($_js_part); // Housekeeping.

                $compressed_js_tags    = implode("\n", $compressed_js_tags);
                $compressed_head_parts = [$head_frag['open_tag'], $cleaned_head_contents, $compressed_js_tags, $head_frag['closing_tag']];
                $html                  = $this->replaceOnce('%%htmlc-head%%', implode("\n", $compressed_head_parts), $html);

                if ($benchmark) {
                    $this->benchmark->addData(
                        __FUNCTION__,
                        compact(
                            'head_frag',
                            'js_tag_frags',
                            'js_parts',
                            'cleaned_head_contents',
                            'compressed_js_tags',
                            'compressed_head_parts'
                        )
                    );
                }
            }
        }
        finale: // Target point; finale/return value.

        if ($html) {
            $html = trim($html);
        } // Trim it up now!

        if ($benchmark && !empty($time) && $html && empty($disabled)) {
            $this->benchmark->addTime(
                __FUNCTION__,
                $time, // Caller, start time, task performed.
                sprintf('compressing/combining head JS in checksum: `%1$s`', md5($html))
            );
        }
        return $html; // With possible compression having been applied here.
    }

    /**
     * Handles possible compression of footer JS.
     *
     * @since 140417 Initial release.
     *
     * @param string $html Input HTML code.
     *
     * @return string HTML code, after possible JS compression.
     */
    protected function maybeCompressCombineFooterJs($html)
    {
        if (($benchmark = !empty($this->options['benchmark']) && $this->options['benchmark'] === 'details')) {
            $time = microtime(true);
        }
        $html = (string) $html; // Force string value.

        if (isset($this->options['compress_combine_footer_js'])) {
            if (!$this->options['compress_combine_footer_js']) {
                $disabled = true; // Disabled flag.
            }
        }
        if (!$html || !empty($disabled)) {
            goto finale; // Nothing to do.
        }
        if (($footer_scripts_frag = $this->getFooterScriptsFrag($html)) /* e.g. <!-- footer-scripts --><!-- footer-scripts --> */) {
            if (($js_tag_frags = $this->getJsTagFrags($footer_scripts_frag)) && ($js_parts = $this->compileJsTagFragsIntoParts($js_tag_frags, 'foot'))) {
                $js_tag_frags_all_compiled = $this->compileKeyElementsDeep($js_tag_frags, 'all');
                $html                      = $this->replaceOnce($footer_scripts_frag['all'], '%%htmlc-footer-scripts%%', $html);
                $cleaned_footer_scripts    = $this->replaceOnce($js_tag_frags_all_compiled, '', $footer_scripts_frag['contents']);

                $compressed_js_tags = []; // Initialize.

                foreach ($js_parts as $_js_part) {
                    if (isset($_js_part['exclude_frag'], $js_tag_frags[$_js_part['exclude_frag']]['all'])) {
                        $compressed_js_tags[] = $js_tag_frags[$_js_part['exclude_frag']]['all'];
                    } else {
                        $compressed_js_tags[] = $_js_part['tag'];
                    }
                } // unset($_js_part); // Housekeeping.

                $compressed_js_tags             = implode("\n", $compressed_js_tags);
                $compressed_footer_script_parts = [$footer_scripts_frag['open_tag'], $cleaned_footer_scripts, $compressed_js_tags, $footer_scripts_frag['closing_tag']];
                $html                           = $this->replaceOnce('%%htmlc-footer-scripts%%', implode("\n", $compressed_footer_script_parts), $html);

                if ($benchmark) {
                    $this->benchmark->addData(
                        __FUNCTION__,
                        compact(
                            'footer_scripts_frag',
                            'js_tag_frags',
                            'js_parts',
                            'cleaned_footer_scripts',
                            'compressed_js_tags',
                            'compressed_footer_script_parts'
                        )
                    );
                }
            }
        }
        finale: // Target point; finale/return value.

        if ($html) {
            $html = trim($html);
        } // Trim it up now!

        if ($benchmark && !empty($time) && $html && empty($disabled)) {
            $this->benchmark->addTime(
                __FUNCTION__,
                $time, // Caller, start time, task performed.
                sprintf('compressing/combining footer JS in checksum: `%1$s`', md5($html))
            );
        }
        return $html; // With possible compression having been applied here.
    }

    /**
     * Compiles JS tag fragments into JS parts with compression.
     *
     * @since 140417 Initial release.
     *
     * @param array  $js_tag_frags JS tag fragments.
     * @param string $for          Where will these parts go? One of `head`, `body`, `foot`.
     *
     * @throws \Exception If unable to cache JS parts.
     *
     * @return array Array of JS parts, else an empty array on failure.
     */
    protected function compileJsTagFragsIntoParts(array $js_tag_frags, $for)
    {
        if (($benchmark = !empty($this->options['benchmark']) && $this->options['benchmark'] === 'details')) {
            $time = microtime(true);
        }
        $for               = (string) $for; // Force string.
        $js_parts          = []; // Initialize.
        $js_parts_checksum = ''; // Initialize.

        if (!$js_tag_frags) {
            goto finale; // Nothing to do.
        }
        $js_parts_checksum    = $this->getTagFragsChecksum($js_tag_frags);
        $public_cache_dir     = $this->cacheDir($this::DIR_PUBLIC_TYPE, $js_parts_checksum);
        $private_cache_dir    = $this->cacheDir($this::DIR_PRIVATE_TYPE, $js_parts_checksum);
        $public_cache_dir_url = $this->cacheDirUrl($this::DIR_PUBLIC_TYPE, $js_parts_checksum);

        $cache_parts_file          = $js_parts_checksum.'-compressor-parts.js-cache';
        $cache_parts_file_path     = $private_cache_dir.'/'.$cache_parts_file;
        $cache_parts_file_path_tmp = $cache_parts_file_path.'.'.uniqid('', true).'.tmp';
        // Cache file creation is atomic; i.e. tmp file w/ rename.

        $cache_part_file      = '%%code-checksum%%-compressor-part.js';
        $cache_part_file_path = $public_cache_dir.'/'.$cache_part_file;
        $cache_part_file_url  = $public_cache_dir_url.'/'.$cache_part_file;

        if (is_file($cache_parts_file_path) && filemtime($cache_parts_file_path) > strtotime('-'.$this->cache_expiration_time)) {
            if (is_array($cached_parts = unserialize(file_get_contents($cache_parts_file_path)))) {
                $js_parts = $cached_parts; // Use cached parts.
                goto finale; // Using the cache; we're all done here.
            }
        }
        $_js_part = 0; // Initialize part counter.

        foreach ($js_tag_frags as $_js_tag_frag_pos => $_js_tag_frag) {
            if ($_js_tag_frag['exclude']) {
                if ($_js_tag_frag['script_src'] || $_js_tag_frag['script_js'] || $_js_tag_frag['script_json']) {
                    if ($js_parts) {
                        ++$_js_part; // Starts new part.
                    }
                    $js_parts[$_js_part]['tag']          = '';
                    $js_parts[$_js_part]['exclude_frag'] = $_js_tag_frag_pos;
                    ++$_js_part; // Always indicates a new part in the next iteration.
                }
            } elseif ($_js_tag_frag['script_src']) {
                if (($_js_tag_frag['script_src'] = $this->resolveRelativeUrl($_js_tag_frag['script_src']))) {
                    if (($_js_code = $this->stripUtf8Bom($this->mustGetUrl($_js_tag_frag['script_src'])))) {
                        $_js_code = rtrim($_js_code, ';').';';

                        if ($_js_code) {
                            if (!empty($js_parts[$_js_part]['code'])) {
                                $js_parts[$_js_part]['code'] .= "\n\n".$_js_code;
                            } else {
                                $js_parts[$_js_part]['code'] = $_js_code;
                            }
                        }
                    }
                }
            } elseif ($_js_tag_frag['script_js']) {
                $_js_code = $_js_tag_frag['script_js'];
                $_js_code = $this->stripUtf8Bom($_js_code);
                $_js_code = rtrim($_js_code, ';').';';

                if ($_js_code) {
                    if (!empty($js_parts[$_js_part]['code'])) {
                        $js_parts[$_js_part]['code'] .= "\n\n".$_js_code;
                    } else {
                        $js_parts[$_js_part]['code'] = $_js_code;
                    }
                }
            } elseif ($_js_tag_frag['script_json']) {
                if ($js_parts) {
                    ++$_js_part; // Starts new part.
                }
                $js_parts[$_js_part]['tag'] = $_js_tag_frag['all'];
                ++$_js_part; // Always indicates a new part in the next iteration.
            }
        } // unset($_js_part, $_js_tag_frag_pos, $_js_tag_frag, $_js_code);

        foreach (array_keys($js_parts = array_values($js_parts)) as $_js_part) {
            if (!isset($js_parts[$_js_part]['exclude_frag']) && !empty($js_parts[$_js_part]['code'])) {
                $_js_code    = $js_parts[$_js_part]['code'];
                $_js_code_cs = md5($_js_code); // Before compression.
                $_js_code    = $this->maybeCompressJsCode($_js_code);

                $_js_code_path     = str_replace('%%code-checksum%%', $_js_code_cs, $cache_part_file_path);
                $_js_code_url      = str_replace('%%code-checksum%%', $_js_code_cs, $cache_part_file_url);
                $_js_code_url      = $this->hook_api->applyFilters('part_url', $_js_code_url, $for);
                $_js_code_path_tmp = $_js_code_path.'.'.uniqid('', true).'.tmp';
                // Cache file creation is atomic; e.g. tmp file w/ rename.

                if (!(file_put_contents($_js_code_path_tmp, $_js_code) && rename($_js_code_path_tmp, $_js_code_path))) {
                    throw new \Exception(sprintf('Unable to cache JS code file: `%1$s`.', $_js_code_path));
                }
                $js_parts[$_js_part]['tag'] = '<script type="text/javascript" src="'.htmlspecialchars($_js_code_url, ENT_QUOTES, 'UTF-8').'"></script>';

                unset($js_parts[$_js_part]['code']); // Ditch this; no need to cache this code too.
            }
        } // unset($_js_part, $_js_code, $_js_code_cs, $_js_code_path, $_js_code_path_tmp, $_js_code_url);

        if (!(file_put_contents($cache_parts_file_path_tmp, serialize($js_parts)) && rename($cache_parts_file_path_tmp, $cache_parts_file_path))) {
            throw new \Exception(sprintf('Unable to cache JS parts into: `%1$s`.', $cache_parts_file_path));
        }
        finale: // Target point; finale/return value.

        if ($benchmark && !empty($time) && $js_parts_checksum) {
            $this->benchmark->addTime(
                __FUNCTION__,
                $time, // Caller, start time, task performed.
                sprintf('building parts based on JS tag frags in checksum: `%1$s`', $js_parts_checksum)
            );
        }
        return $js_parts;
    }

    /**
     * Parses and return an array of JS tag fragments.
     *
     * @since 140417 Initial release.
     *
     * @param array $html_frag An HTML tag fragment array.
     *
     * @return array An array of JS tag fragments (ready to be converted into JS parts).
     *               Else an empty array (i.e. no JS tag fragments in the HTML fragment array).
     *
     * @see http://css-tricks.com/how-to-create-an-ie-only-stylesheet/
     * @see http://stackoverflow.com/a/12102131
     */
    protected function getJsTagFrags(array $html_frag)
    {
        if (($benchmark = !empty($this->options['benchmark']) && $this->options['benchmark'] === 'details')) {
            $time = microtime(true);
        }
        $js_tag_frags = []; // Initialize.

        if (!$html_frag) {
            goto finale; // Nothing to do.
        }
        $regex = '/(?P<all>'.// Entire match.
                 '(?P<if_open_tag>\<\![^[>]*?\[if\W[^\]]*?\][^>]*?\>\s*)?'.
                 '(?P<script_open_tag>\<script(?:\s+[^>]*?)?\>)(?P<script_js>.*?)(?P<script_closing_tag>\<\/script\>)'.
                 '(?P<if_closing_tag>\s*\<\![^[>]*?\[endif\][^>]*?\>)?'.
                 ')/uis'; // Dot matches line breaks.

        if (!empty($html_frag['contents']) && preg_match_all($regex, $html_frag['contents'], $_tag_frags, PREG_SET_ORDER)) {
            foreach ($_tag_frags as $_tag_frag) {
                if (isset($_tag_frag['script_js'])) {
                    $_tag_frag['script_json'] = $_tag_frag['script_js'];
                } // Assume that this is either/or for the time being.
                $_script_src = $_script_js = $_script_json = $_script_async = ''; // Initialize.
                $_is_js      = $this->isScriptTagFragJs($_tag_frag); // JavaScript or JSON?
                $_is_json    = !$_is_js && $this->isScriptTagFragJson($_tag_frag);

                if ($_is_js || $_is_json) {
                    if ($_is_js && ($_script_src = $this->getScriptJsSrc($_tag_frag, false))) {
                        $_script_async = $this->getScriptJsAsync($_tag_frag, false);
                    } elseif ($_is_js && ($_script_js = $this->getScriptJs($_tag_frag, false))) {
                        $_script_async = $this->getScriptJsAsync($_tag_frag, false);
                    } elseif ($_is_json && ($_script_json = $this->getScriptJson($_tag_frag, false))) {
                        $_script_async = ''; // Not applicable.
                    }
                    if ($_script_src || $_script_js || $_script_json) {
                        $js_tag_frags[] = [
                            'all' => $_tag_frag['all'],

                            'if_open_tag'    => isset($_tag_frag['if_open_tag']) ? $_tag_frag['if_open_tag'] : '',
                            'if_closing_tag' => isset($_tag_frag['if_closing_tag']) ? $_tag_frag['if_closing_tag'] : '',

                            'script_open_tag'     => isset($_tag_frag['script_open_tag']) ? $_tag_frag['script_open_tag'] : '',
                            'script_src_external' => $_is_js && $_script_src ? $this->isUrlExternal($_script_src) : false,
                            'script_src'          => $_is_js ? $_script_src : '', // This could also be empty.
                            'script_js'           => $_is_js ? $_script_js : '', // This could also be empty.
                            'script_json'         => $_is_json ? $_script_json : '', // This could also be empty.
                            'script_async'        => $_is_js ? $_script_async : '', // This could also be empty.
                            'script_closing_tag'  => isset($_tag_frag['script_closing_tag']) ? $_tag_frag['script_closing_tag'] : '',

                            'exclude' => false, // Default value.
                        ];
                        $_tag_frag_r = &$js_tag_frags[count($js_tag_frags) - 1];

                        if ($_tag_frag_r['if_open_tag'] || $_tag_frag_r['if_closing_tag'] || $_tag_frag_r['script_async']) {
                            $_tag_frag_r['exclude'] = true;
                        } elseif ($_tag_frag_r['script_src'] && $_tag_frag_r['script_src_external'] && isset($this->options['compress_combine_remote_css_js']) && !$this->options['compress_combine_remote_css_js']) {
                            $_tag_frag_r['exclude'] = true;
                        } elseif ($this->regex_js_exclusions && preg_match($this->regex_js_exclusions, $_tag_frag_r['script_open_tag'].' '.$_tag_frag_r['script_js'].$_tag_frag_r['script_json'])) {
                            $_tag_frag_r['exclude'] = true;
                        } elseif ($this->built_in_regex_js_exclusions && preg_match($this->built_in_regex_js_exclusions, $_tag_frag_r['script_open_tag'].' '.$_tag_frag_r['script_js'].$_tag_frag_r['script_json'])) {
                            $_tag_frag_r['exclude'] = true;
                        }
                    }
                }
            } // unset($_tag_frags, $_tag_frag, $_tag_frag_r, $_script_src, $_script_js, $_script_json, $_script_async, $_is_js, $_is_json);
        }
        finale: // Target point; finale/return value.

        if ($benchmark && !empty($time) && $html_frag) {
            $this->benchmark->addTime(
                __FUNCTION__,
                $time, // Caller, start time, task performed.
                sprintf('compiling JS tag frags in checksum: `%1$s`', md5(serialize($html_frag)))
            );
        }
        return $js_tag_frags;
    }

    /**
     * Test a script tag fragment to see if it's JavaScript.
     *
     * @since 140922 Improving attribute tests.
     *
     * @param array $tag_frag A JS tag fragment.
     *
     * @return bool TRUE if it contains JavaScript.
     */
    protected function isScriptTagFragJs(array $tag_frag)
    {
        if (empty($tag_frag['script_open_tag']) || empty($tag_frag['script_closing_tag'])) {
            return false; // Nope; missing open|closing tag.
        }
        $type = $language = ''; // Initialize.

        if (mb_stripos($tag_frag['script_open_tag'], 'type') !== 0) {
            if (preg_match('/\stype\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['script_open_tag'], $_m)) {
                $type = $_m['value'];
            }
        } // unset($_m); // Just a little housekeeping.

        if (mb_stripos($tag_frag['script_open_tag'], 'language') !== 0) {
            if (preg_match('/\slanguage\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['script_open_tag'], $_m)) {
                $language = $_m['value'];
            }
        } // unset($_m); // Just a little housekeeping.

        if ($type && mb_stripos($type, 'json') !== false) {
            return false; // JSON; not JavaScript.
        }
        if ($type && mb_stripos($type, 'javascript') === false) {
            return false; // Not JavaScript.
        }
        if ($language && mb_stripos($language, 'json') !== false) {
            return false; // JSON; not JavaScript.
        }
        if ($language && mb_stripos($language, 'javascript') === false) {
            return false; // Not JavaScript.
        }
        return true; // Yes, this is JavaScript.
    }

    /**
     * Test a script tag fragment to see if it's JSON.
     *
     * @since 150424 Adding support for JSON compression.
     *
     * @param array $tag_frag A JS tag fragment.
     *
     * @return bool TRUE if it contains JSON.
     */
    protected function isScriptTagFragJson(array $tag_frag)
    {
        if (empty($tag_frag['script_open_tag']) || empty($tag_frag['script_closing_tag'])) {
            return false; // Nope; missing open|closing tag.
        }
        $type = $language = ''; // Initialize.

        if (mb_stripos($tag_frag['script_open_tag'], 'type') !== 0) {
            if (preg_match('/\stype\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['script_open_tag'], $_m)) {
                $type = $_m['value'];
            }
        } // unset($_m); // Just a little housekeeping.

        if (mb_stripos($tag_frag['script_open_tag'], 'language') !== 0) {
            if (preg_match('/\slanguage\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['script_open_tag'], $_m)) {
                $language = $_m['value'];
            }
        } // unset($_m); // Just a little housekeeping.

        if (($type && mb_stripos($type, 'javascript') === false) || ($language && mb_stripos($language, 'javascript') === false)) {
            if ($type && mb_stripos($type, 'json') !== false) {
                return true; // Yes, this is JSON.
            }
            if ($language && mb_stripos($language, 'json') !== false) {
                return true; // Yes, this is JSON.
            }
        }
        return false; // No, not JSON.
    }

    /**
     * Get script JS src value from a JS tag fragment.
     *
     * @since 140417 Initial release.
     *
     * @param array $tag_frag    A JS tag fragment.
     * @param bool  $test_for_js Defaults to a TRUE value.
     *                           If TRUE, we will test tag fragment to make sure it's JavaScript.
     *
     * @return string The script JS src value (if possible); else an empty string.
     */
    protected function getScriptJsSrc(array $tag_frag, $test_for_js = true)
    {
        if ($test_for_js && !$this->isScriptTagFragJs($tag_frag)) {
            return ''; // This script tag does not contain JavaScript.
        }
        if (preg_match('/\ssrc\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['script_open_tag'], $_m)) {
            return trim($this->nUrlAmps($_m['value']));
        } // unset($_m); // Just a little housekeeping.

        return ''; // Unable to find an `src` attribute value.
    }

    /**
     * Get script JS async|defer value from a JS tag fragment.
     *
     * @since 140417 Initial release.
     *
     * @param array $tag_frag    A JS tag fragment.
     * @param bool  $test_for_js Defaults to a TRUE value.
     *                           If TRUE, we will test tag fragment to make sure it's JavaScript.
     *
     * @return string The script JS async|defer value (if possible); else an empty string.
     */
    protected function getScriptJsAsync(array $tag_frag, $test_for_js = true)
    {
        if ($test_for_js && !$this->isScriptTagFragJs($tag_frag)) {
            return ''; // This script tag does not contain JavaScript.
        }
        if (preg_match('/\s(?:async|defer)(?:\>|\s+[^=]|\s*\=\s*(["\'])(?:1|on|yes|true|async|defer)\\1)/ui', $tag_frag['script_open_tag'], $_m)) {
            return 'async'; // Yes, load this asynchronously.
        } // unset($_m); // Just a little housekeeping.

        return ''; // Unable to find a TRUE `async|defer` attribute.
    }

    /**
     * Get script JS from a JS tag fragment.
     *
     * @since 140417 Initial release.
     *
     * @param array $tag_frag    A JS tag fragment.
     * @param bool  $test_for_js Defaults to a TRUE value.
     *                           If TRUE, we will test tag fragment to make sure it's JavaScript.
     *
     * @return string The script JS code (if possible); else an empty string.
     */
    protected function getScriptJs(array $tag_frag, $test_for_js = true)
    {
        if (empty($tag_frag['script_js'])) {
            return ''; // Not possible; no JavaScript code.
        }
        if ($test_for_js && !$this->isScriptTagFragJs($tag_frag)) {
            return ''; // This script tag does not contain JavaScript.
        }
        return trim($tag_frag['script_js']); // JavaScript code.
    }

    /**
     * Get script JSON from a JS tag fragment.
     *
     * @since 150424 Adding support for JSON compression.
     *
     * @param array $tag_frag    A JS tag fragment.
     * @param bool  $test_for_js Defaults to a TRUE value.
     *                           If TRUE, we will test tag fragment to make sure it's JSON.
     *
     * @return string The script JSON code (if possible); else an empty string.
     */
    protected function getScriptJson(array $tag_frag, $test_for_json = true)
    {
        if (empty($tag_frag['script_json'])) {
            return ''; // Not possible; no JSON code.
        }
        if ($test_for_json && !$this->isScriptTagFragJson($tag_frag)) {
            return ''; // This script tag does not contain JSON.
        }
        return trim($tag_frag['script_json']); // JSON code.
    }

    /********************************************************************************************************/

    /*
     * Frag-Related Utilities
     */

    /**
     * Build an HTML fragment from HTML source code.
     *
     * @since 140417 Initial release.
     *
     * @param string $html Raw HTML code.
     *
     * @return array An HTML fragment (if possible); else an empty array.
     */
    protected function getHtmlFrag($html)
    {
        if (!($html = (string) $html)) {
            return []; // Nothing to do.
        }
        if (preg_match('/(?P<all>(?P<open_tag>\<html(?:\s+[^>]*?)?\>)(?P<contents>.*?)(?P<closing_tag>\<\/html\>))/uis', $html, $html_frag)) {
            return $this->removeNumericKeysDeep($html_frag);
        }
        return [];
    }

    /**
     * Build a head fragment from HTML source code.
     *
     * @since 140417 Initial release.
     *
     * @param string $html Raw HTML code.
     *
     * @return array A head fragment (if possible); else an empty array.
     */
    protected function getHeadFrag($html)
    {
        if (!($html = (string) $html)) {
            return []; // Nothing to do.
        }
        if (preg_match('/(?P<all>(?P<open_tag>\<head(?:\s+[^>]*?)?\>)(?P<contents>.*?)(?P<closing_tag>\<\/head\>))/uis', $html, $head_frag)) {
            return $this->removeNumericKeysDeep($head_frag);
        }
        return [];
    }

    /**
     * Build a footer scripts fragment from HTML source code.
     *
     * @since 140417 Initial release.
     *
     * @param string $html Raw HTML code.
     *
     * @return array A footer scripts fragment (if possible); else an empty array.
     */
    protected function getFooterScriptsFrag($html)
    {
        if (!($html = (string) $html)) {
            return []; // Nothing to do.
        }
        if (preg_match('/(?P<all>(?P<open_tag>\<\!\-\-\s*footer[\s_\-]+scripts\s*\-\-\>)(?P<contents>.*?)(?P<closing_tag>(?P=open_tag)))/uis', $html, $head_frag)) {
            return $this->removeNumericKeysDeep($head_frag);
        }
        return [];
    }

    /**
     * Construct a checksum for an array of tag fragments.
     *
     * @since 140417 Initial release.
     *
     * @note This routine purposely excludes any "exclusions" from the checksum.
     *    All that's important here is an exclusion's position in the array,
     *    not its fragmentation; it's excluded anyway.
     *
     * @param array $tag_frags Array of tag fragments.
     *
     * @return string MD5 checksum.
     */
    protected function getTagFragsChecksum(array $tag_frags)
    {
        foreach ($tag_frags as &$_frag) {
            $_frag = $_frag['exclude'] ? ['exclude' => true] : $_frag;
        } // unset($_frag); // A little housekeeping.

        return md5(serialize($tag_frags));
    }

    /********************************************************************************************************/

    /*
     * HTML Compression Utilities
     */

    /**
     * Maybe compress HTML code.
     *
     * @since 140417 Initial release.
     *
     * @param string $html Raw HTML code.
     *
     * @return string Possibly compressed HTML code.
     */
    protected function maybeCompressHtmlCode($html)
    {
        if (($benchmark = !empty($this->options['benchmark']) && $this->options['benchmark'] === 'details')) {
            $time = microtime(true);
        }
        $html = (string) $html; // Force string value.

        if (isset($this->options['compress_html_code'])) {
            if (!$this->options['compress_html_code']) {
                $disabled = true; // Disabled flag.
            }
        }
        if (!$html || !empty($disabled)) {
            goto finale; // Nothing to do.
        }
        if (($compressed_html = $this->compressHtml($html))) {
            $html = $compressed_html; // Use it :-)
        }
        finale: // Target point; finale/return value.

        if ($html) {
            $html = trim($html);
        } // Trim it up now!

        if ($benchmark && !empty($time) && $html && empty($disabled)) {
            $this->benchmark->addTime(
                __FUNCTION__,
                $time, // Caller, start time, task performed.
                sprintf('compressing HTML w/ checksum: `%1$s`', md5($html))
            );
        }
        return $html; // With possible compression having been applied here.
    }

    /**
     * Compresses HTML markup (as quickly as possible).
     *
     * @since 140417 Initial release.
     *
     * @param string $html Any HTML markup (no empty strings please).
     *
     * @return string Compressed HTML markup. With all comments and extra whitespace removed as quickly as possible.
     *                This preserves portions of HTML that depend on whitespace. Like `pre/code/script/style/textarea` tags.
     *                It also preserves conditional comments and JavaScript `on(click|blur|etc)` attributes.
     *
     * @see http://stackoverflow.com/a/12102131
     */
    protected function compressHtml($html)
    {
        if (!($html = (string) $html)) {
            return $html; // Nothing to do.
        }
        $static = &static::$static[__FUNCTION__];

        if (!isset($static['preservations'], $static['compressions'], $static['compress_with'])) {
            $static['preservations'] = [
                'special_tags'            => '\<(pre|code|script|style|textarea)(?:\s+[^>]*?)?\>.*?\<\/\\2>',
                'ie_conditional_comments' => '\<\![^[>]*?\[if\W[^\]]*?\][^>]*?\>.*?\<\![^[>]*?\[endif\][^>]*?\>',
                'special_attributes'      => '\s(?:style|on[a-z]+)\s*\=\s*(["\']).*?\\3',
            ];
            $static['preservations'] = // Implode for regex capture.
                '/(?P<preservation>'.implode('|', $static['preservations']).')/uis';

            $static['compressions']['remove_html_comments']  = '/\<\!\-{2}.*?\-{2}\>/uis';
            $static['compress_with']['remove_html_comments'] = '';

            $static['compressions']['remove_extra_whitespace']  = '/\s+/u';
            $static['compress_with']['remove_extra_whitespace'] = ' ';

            $static['compressions']['remove_extra_whitespace_in_self_closing_tags']  = '/\s+\/\>/u';
            $static['compress_with']['remove_extra_whitespace_in_self_closing_tags'] = '/>';
        }
        if (preg_match_all($static['preservations'], $html, $preservation_matches, PREG_SET_ORDER)) {
            foreach ($preservation_matches as $_preservation_match_key => $_preservation_match) {
                $preservations[]             = $_preservation_match['preservation'];
                $preservation_placeholders[] = '%%minify-html-'.$_preservation_match_key.'%%';
            } // unset($_preservation_match_key, $_preservation_match);

            if (isset($preservations, $preservation_placeholders)) {
                $html = $this->replaceOnce($preservations, $preservation_placeholders, $html);
            }
        }
        $html = preg_replace($static['compressions'], $static['compress_with'], $html);

        if (isset($preservations, $preservation_placeholders)) {
            $html = $this->replaceOnce($preservation_placeholders, $preservations, $html);
        }
        return $html ? trim($html) : $html;
    }

    /********************************************************************************************************/

    /*
     * CSS Compression Utilities
     */

    /**
     * Maybe compress CSS code.
     *
     * @since 140417 Initial release.
     *
     * @param string $css Raw CSS code.
     *
     * @return string CSS code (possibly compressed).
     */
    protected function maybeCompressCssCode($css)
    {
        if (($benchmark = !empty($this->options['benchmark']) && $this->options['benchmark'] === 'details')) {
            $time = microtime(true);
        }
        $css = (string) $css; // Force string value.

        if (isset($this->options['compress_css_code'])) {
            if (!$this->options['compress_css_code']) {
                $disabled = true; // Disabled flag.
            }
        }
        if (!$css || !empty($disabled)) {
            goto finale; // Nothing to do.
        }
        if (strlen($css) > 1000000) {
            // Exclude VERY large files. Too time-consuming.
            // Should really be compressed ahead-of-time anyway.
            goto finale; // Don't compress HUGE files.
        }
        try { // Catch CSS compression-related exceptions.
            if (!($compressed_css = \WebSharks\CssMinifier\Core::compress($css))) {
                // `E_USER_NOTICE` to avoid a show-stopping problem.
                trigger_error('CSS compression failure.', E_USER_NOTICE);
            } else {
                $css = $this->stripUtf8Bom($compressed_css);
            } // Use compressed CSS file.
        } catch (\Exception $exception) {
            trigger_error($exception->getMessage(), E_USER_NOTICE);
        }
        finale: // Target point; finale/return value.

        if ($css) {
            $css = trim($css);
        }
        if ($benchmark && !empty($time) && $css && empty($disabled)) {
            $this->benchmark->addTime(
                __FUNCTION__,
                $time, // Caller, start time, task performed.
                sprintf('compressing CSS w/ checksum: `%1$s`', md5($css))
            );
        }
        return $css;
    }

    /********************************************************************************************************/

    /*
     * JS Compression Utilities
     */

    /**
     * Maybe compress JS code.
     *
     * @since 140417 Initial release.
     *
     * @param string $js Raw JS code.
     *
     * @return string JS code (possibly compressed).
     */
    protected function maybeCompressJsCode($js)
    {
        if (($benchmark = !empty($this->options['benchmark']) && $this->options['benchmark'] === 'details')) {
            $time = microtime(true);
        }
        $js = (string) $js; // Force string value.

        if (isset($this->options['compress_js_code'])) {
            if (!$this->options['compress_js_code']) {
                $disabled = true; // Disabled flag.
            }
        }
        if (!$js || !empty($disabled)) {
            goto finale; // Nothing to do.
        }
        if (strlen($js) > 1000000) {
            // Exclude VERY large files. Too time-consuming.
            // Should really be compressed ahead-of-time anyway.
            goto finale; // Don't compress HUGE files.
        }
        try { // Catch JS compression-related exceptions.
            if (!($compressed_js = \WebSharks\JsMinifier\Core::compress($js))) {
                // `E_USER_NOTICE` to avoid a show-stopping problem.
                trigger_error('JS compression failure.', E_USER_NOTICE);
            } else {
                $js = $compressed_js;
            } // Use compressed JS file.
        } catch (\Exception $exception) {
            trigger_error($exception->getMessage(), E_USER_NOTICE);
        }
        finale: // Target point; finale/return value.

        if ($js) {
            $js = trim($js);
        }
        if ($benchmark && !empty($time) && $js && empty($disabled)) {
            $this->benchmark->addTime(
                __FUNCTION__,
                $time, // Caller, start time, task performed.
                sprintf('compressing JS w/ checksum: `%1$s`', md5($js))
            );
        }
        return $js;
    }

    /**
     * Maybe compress inline JS code within the HTML source.
     *
     * @since 140417 Initial release.
     *
     * @param string $html Raw HTML code.
     *
     * @return string HTML source code, with possible inline JS compression.
     */
    protected function maybeCompressInlineJsCode($html)
    {
        if (($benchmark = !empty($this->options['benchmark']) && $this->options['benchmark'] === 'details')) {
            $time = microtime(true);
        }
        $html = (string) $html; // Force string value.

        if (isset($this->options['compress_js_code'])) {
            if (!$this->options['compress_js_code']) {
                $disabled = true; // Disabled flag.
            }
        }
        if (isset($this->options['compress_inline_js_code'])) {
            if (!$this->options['compress_inline_js_code']) {
                $disabled = true; // Disabled flag.
            }
        }
        if (!$html || !empty($disabled)) {
            goto finale; // Nothing to do.
        }
        if (($html_frag = $this->getHtmlFrag($html)) && ($js_tag_frags = $this->getJsTagFrags($html_frag))) {
            foreach ($js_tag_frags as $_js_tag_frag_key => $_js_tag_frag) {
                if (!$_js_tag_frag['exclude'] && $_js_tag_frag['script_js']) {
                    $js_tag_frags_script_js_parts[]                             = $_js_tag_frag['all'];
                    $js_tag_frags_script_js_part_placeholders[]                 = '%%htmlc-'.$_js_tag_frag_key.'%%';
                    $js_tag_frags_script_js_part_placeholder_key_replacements[] = $_js_tag_frag_key;
                }
            } // unset($_js_tag_frag_key, $_js_tag_frag); // Housekeeping.

            if (isset($js_tag_frags_script_js_parts, $js_tag_frags_script_js_part_placeholders, $js_tag_frags_script_js_part_placeholder_key_replacements)) {
                $html = $this->replaceOnce($js_tag_frags_script_js_parts, $js_tag_frags_script_js_part_placeholders, $html);

                foreach ($js_tag_frags_script_js_part_placeholder_key_replacements as &$_js_tag_frag_key_replacement) {
                    $_js_tag_frag = $js_tag_frags[$_js_tag_frag_key_replacement];

                    $_js_tag_frag_key_replacement = $_js_tag_frag['if_open_tag'];
                    $_js_tag_frag_key_replacement .= $_js_tag_frag['script_open_tag'];
                    $_js_tag_frag_key_replacement .= $this->compressInlineJsCode($_js_tag_frag['script_js']);
                    $_js_tag_frag_key_replacement .= $_js_tag_frag['script_closing_tag'];
                    $_js_tag_frag_key_replacement .= $_js_tag_frag['if_closing_tag'];
                } // unset($_js_tag_frag_key_replacement, $_js_tag_frag); // Housekeeping.

                $html = $this->replaceOnce($js_tag_frags_script_js_part_placeholders, $js_tag_frags_script_js_part_placeholder_key_replacements, $html);

                if ($benchmark) {
                    $this->benchmark->addData(
                        __FUNCTION__,
                        compact(
                            'js_tag_frags',
                            'js_tag_frags_script_js_parts',
                            'js_tag_frags_script_js_part_placeholders',
                            'js_tag_frags_script_js_part_placeholder_key_replacements'
                        )
                    );
                }
            }
        }
        finale: // Target point; finale/return value.

        if ($html) {
            $html = trim($html);
        } // Trim it up now!

        if ($benchmark && !empty($time) && $html && empty($disabled)) {
            $this->benchmark->addTime(
                __FUNCTION__,
                $time, // Caller, start time, task performed.
                sprintf('compressing inline JS in checksum: `%1$s`', md5($html))
            );
        }
        return $html; // With possible compression having been applied here.
    }

    /**
     * Helper function; compress inline JS code.
     *
     * @since 140417 Initial release.
     *
     * @param string $js Raw JS code.
     *
     * @return string JS code (possibly minified).
     */
    protected function compressInlineJsCode($js)
    {
        if (!($js = (string) $js)) {
            return $js; // Nothing to do.
        }
        if (($compressed_js = \WebSharks\JsMinifier\Core::compress($js))) {
            return '/*<![CDATA[*/'.$compressed_js.'/*]]>*/';
        }
        return $js;
    }

    /**
     * Maybe compress inline JSON code within the HTML source.
     *
     * @since 150424 Adding support for JSON compression.
     *
     * @param string $html Raw HTML code.
     *
     * @return string HTML source code, with possible inline JSON compression.
     */
    protected function maybeCompressInlineJsonCode($html)
    {
        if (($benchmark = !empty($this->options['benchmark']) && $this->options['benchmark'] === 'details')) {
            $time = microtime(true);
        }
        $html = (string) $html; // Force string value.

        if (isset($this->options['compress_js_code'])) {
            if (!$this->options['compress_js_code']) {
                $disabled = true; // Disabled flag.
            }
        }
        if (isset($this->options['compress_inline_js_code'])) {
            if (!$this->options['compress_inline_js_code']) {
                $disabled = true; // Disabled flag.
            }
        }
        if (!$html || !empty($disabled)) {
            goto finale; // Nothing to do.
        }
        if (($html_frag = $this->getHtmlFrag($html)) && ($js_tag_frags = $this->getJsTagFrags($html_frag))) {
            foreach ($js_tag_frags as $_js_tag_frag_key => $_js_tag_frag) {
                if (!$_js_tag_frag['exclude'] && $_js_tag_frag['script_json']) {
                    $js_tag_frags_script_json_parts[]                             = $_js_tag_frag['all'];
                    $js_tag_frags_script_json_part_placeholders[]                 = '%%htmlc-'.$_js_tag_frag_key.'%%';
                    $js_tag_frags_script_json_part_placeholder_key_replacements[] = $_js_tag_frag_key;
                }
            } // unset($_js_tag_frag_key, $_js_tag_frag); // Housekeeping.

            if (isset($js_tag_frags_script_json_parts, $js_tag_frags_script_json_part_placeholders, $js_tag_frags_script_json_part_placeholder_key_replacements)) {
                $html = $this->replaceOnce($js_tag_frags_script_json_parts, $js_tag_frags_script_json_part_placeholders, $html);

                foreach ($js_tag_frags_script_json_part_placeholder_key_replacements as &$_json_tag_frag_key_replacement) {
                    $_js_tag_frag = $js_tag_frags[$_json_tag_frag_key_replacement];

                    $_json_tag_frag_key_replacement = $_js_tag_frag['if_open_tag'];
                    $_json_tag_frag_key_replacement .= $_js_tag_frag['script_open_tag'];
                    $_json_tag_frag_key_replacement .= $this->compressInlineJsonCode($_js_tag_frag['script_json']);
                    $_json_tag_frag_key_replacement .= $_js_tag_frag['script_closing_tag'];
                    $_json_tag_frag_key_replacement .= $_js_tag_frag['if_closing_tag'];
                } // unset($_json_tag_frag_key_replacement, $_js_tag_frag); // Housekeeping.

                $html = $this->replaceOnce($js_tag_frags_script_json_part_placeholders, $js_tag_frags_script_json_part_placeholder_key_replacements, $html);

                if ($benchmark) {
                    $this->benchmark->addData(
                        __FUNCTION__,
                        compact(
                            'js_tag_frags',
                            'js_tag_frags_script_json_parts',
                            'js_tag_frags_script_json_part_placeholders',
                            'js_tag_frags_script_json_part_placeholder_key_replacements'
                        )
                    );
                }
            }
        }
        finale: // Target point; finale/return value.

        if ($html) {
            $html = trim($html);
        } // Trim it up now!

        if ($benchmark && !empty($time) && $html && empty($disabled)) {
            $this->benchmark->addTime(
                __FUNCTION__,
                $time, // Caller, start time, task performed.
                sprintf('compressing inline JSON in checksum: `%1$s`', md5($html))
            );
        }
        return $html; // With possible compression having been applied here.
    }

    /**
     * Helper function; compress inline JSON code.
     *
     * @since 150424 Adding support for JSON compression.
     *
     * @param string $js Raw JSON code.
     *
     * @return string JSON code (possibly minified).
     */
    protected function compressInlineJsonCode($json)
    {
        if (!($json = (string) $json)) {
            return $json; // Nothing to do.
        }
        if (($compressed_json = \WebSharks\JsMinifier\Core::compress($json))) {
            return '/*<![CDATA[*/'.$compressed_json.'/*]]>*/';
        }
        return $json;
    }

    /********************************************************************************************************/

    /*
     * Array Utilities
     */

    /**
     * Compiles a new array of all `$key` elements (deeply).
     *
     * @since 140417 Initial release.
     *
     * @note This is a recursive scan running deeply into multiple dimensions of arrays.
     *
     * @param array            $array                An input array to search in.
     * @param string|int|array $keys                 An array of `key` elements to compile.
     *                                               In other words, elements with one of these array keys, are what we're looking for.
     *                                               A string|integer is also accepted here (if only one key), and it's converted internally to an array.
     * @param bool             $preserve_keys        Optional. Defaults to a FALSE value.
     *                                               If this is TRUE, the return array WILL preserve numeric/associative keys, instead of forcing a numerically indexed array.
     *                                               This ALSO prevents duplicates in the return array, which may NOT be desirable in certain circumstances.
     *                                               Particularly when/if searching a multidimensional array (where keys could be found in multiple dimensions).
     *                                               In fact, in some cases, this could return data you did NOT want/expect, so please be cautious.
     * @param int              $search_dimensions    The number of dimensions to search. Defaults to `-1` (infinite).
     *                                               If `$preserve_keys` is TRUE, consider setting this to a value of `1`.
     * @param int              $___current_dimension For internal use only; used in recursion.
     *
     * @return array The array of compiled key elements, else an empty array, if no key elements were found.
     *               By default, the return array will be indexed numerically (e.g. keys are NOT preserved here).
     *               If an associative array is preferred, please set `$preserve_keys` to a TRUE value,
     *               and please consider setting `$search_dimensions` to `1`.
     */
    protected function compileKeyElementsDeep(array $array, $keys, $preserve_keys = false, $search_dimensions = -1, $___current_dimension = 1)
    {
        if ($___current_dimension === 1) {
            $keys              = (array) $keys;
            $search_dimensions = (int) $search_dimensions;
        }
        $key_elements = []; // Initialize.

        foreach ($array as $_key => $_value) {
            if (in_array($_key, $keys, true)) {
                if ($preserve_keys) {
                    $key_elements[$_key] = $_value;
                } else {
                    $key_elements[] = $_value;
                }
            }
            if (($search_dimensions < 1 || $___current_dimension < $search_dimensions) && is_array($_value)
               && ($_key_elements = $this->compileKeyElementsDeep($_value, $keys, $preserve_keys, $search_dimensions, $___current_dimension + 1))
            ) {
                $key_elements = array_merge($key_elements, $_key_elements);
            }
        } // unset($_key, $_value, $_key_elements);

        return $key_elements;
    }

    /**
     * Removes all numeric array keys (deeply).
     *
     * @since 140417 Initial release.
     *
     * @note This is a recursive scan running deeply into multiple dimensions of arrays.
     *
     * @param array $array        An input array.
     * @param bool  $___recursion Internal use only.
     *
     * @return array Output array with only non-numeric keys (deeply).
     */
    protected function removeNumericKeysDeep(array $array, $___recursion = false)
    {
        foreach ($array as $_key => &$_value) {
            if (is_numeric($_key)) {
                unset($array[$_key]);
            } elseif (is_array($_value)) {
                $_value = $this->removeNumericKeysDeep($_value, true);
            }
        } // unset($_key, $_value);

        return $array;
    }

    /********************************************************************************************************/

    /*
     * String Utilities
     */

    /**
     * Removes UTF-8 BOM (Byte Order Marker).
     *
     * @since 161108 Correcting bug in CSS compilation.
     *
     * @param string $string Input string to strip.
     *
     * @return string Stripped string.
     */
    protected function stripUtf8Bom($string)
    {
        if (!($string = (string) $string)) {
            return $string;
        }
        return preg_replace('/^\xEF\xBB\xBF/u', '', $string);
    }

    /**
     * Escapes regex special chars deeply.
     *
     * @since 140417 Initial release.
     *
     * @param mixed  $value     Input value.
     * @param string $delimiter Delimiter.
     *
     * @return string|array|object Escaped string, array, object.
     */
    protected function pregQuote($value, $delimiter = '/')
    {
        if (is_array($value) || is_object($value)) {
            foreach ($value as &$_value) {
                $_value = $this->pregQuote($_value, $delimiter);
            } // unset($_value); // Housekeeping.
            return $value;
        }
        return preg_quote((string) $value, (string) $delimiter);
    }

    /**
     * Multibyte `substr_replace()`.
     *
     * @since 16xxxx Enhancing multibyte.
     *
     * @param mixed    $value   Input value.
     * @param string   $replace Replacement string.
     * @param int      $start   Substring start position.
     * @param int|null $length  Substring length.
     *
     * @return string|array|object Output value.
     *
     * @link http://php.net/manual/en/function.substr-replace.php
     *
     * @warning Does NOT support mixed `$replace`, `$start`, `$length` params like `substr_replace()` does.
     */
    protected function substrReplace($value, $replace, $start, $length = null)
    {
        if (is_array($value) || is_object($value)) {
            foreach ($value as $_key => &$_value) {
                $_value = $this->substrReplace($_value, $replace, $start, $length);
            } // unset($_key, $_value);
            return $value;
        }
        $string = (string) $value;

        if (!isset($string[0])) {
            return $string; // Nothing to do.
        }
        $mb_strlen = mb_strlen($string);

        if ($start < 0) {
            $start = max(0, $mb_strlen + $start);
        } elseif ($start > $mb_strlen) {
            $start = $mb_strlen;
        }
        if ($length < 0) {
            $length = max(0, $mb_strlen - $start + $length);
        } elseif (!isset($length) || $length > $mb_strlen) {
            $length = $mb_strlen;
        }
        if ($start + $length > $mb_strlen) {
            $length = $mb_strlen - $start;
        }
        return mb_substr($string, 0, $start).
                $replace.// The replacement string.
            mb_substr($string, $start + $length, $mb_strlen - $start - $length);
    }

    /**
     * String replace (ONE time) deeply.
     *
     * @since 140417 Initial release.
     *
     * @param string|array $needle           String, or an array of strings, to search for.
     * @param string|array $replace          String, or an array of strings, to use as replacements.
     * @param mixed        $value            Any input value will do just fine here.
     * @param bool         $caSe_insensitive Case insensitive? Defaults to FALSE.
     *
     * @return string|array|object Values after ONE string replacement deeply.
     */
    protected function replaceOnce($needle, $replace, $value, $case_insensitive = false)
    {
        if (is_array($value) || is_object($value)) {
            foreach ($value as $_key => &$_value) {
                $_value = $this->replaceOnce($needle, $replace, $_value, $caSe_insensitive);
            } // unset($_key, $_value); // Housekeeping.
            return $value;
        }
        $value     = (string) $value; // Force string (always).
        $mb_strpos = $caSe_insensitive ? 'mb_stripos' : 'mb_strpos';

        if (is_array($needle) || is_object($needle)) {
            $needle = (array) $needle; // Force array.

            if (is_array($replace) || is_object($replace)) {
                $replace = (array) $replace; // Force array.

                foreach ($needle as $_key => $_needle) {
                    $_needle = (string) $_needle;
                    if (($_mb_strpos = $mb_strpos($value, $_needle)) !== false) {
                        $_mb_strlen = mb_strlen($_needle);
                        $_replace   = isset($replace[$_key]) ? (string) $replace[$_key] : '';
                        $value      = $this->substrReplace($value, $_replace, $_mb_strpos, $_mb_strlen);
                    }
                } // unset($_key, $_needle, $_mb_strpos, $_mb_strlen, $_replace);

                return $value;
            } else {
                $replace = (string) $replace; // Force string.

                foreach ($needle as $_key => $_needle) {
                    $_needle = (string) $_needle;
                    if (($_mb_strpos = $mb_strpos($value, $_needle)) !== false) {
                        $_mb_strlen = mb_strlen($_needle);
                        $value      = $this->substrReplace($value, $replace, $_mb_strpos, $_mb_strlen);
                    }
                } // unset($_key, $_needle, $_mb_strpos, $_mb_strlen);

                return $value;
            }
        } else {
            $needle = (string) $needle; // Force string.

            if (($_mb_strpos = $mb_strpos($value, $needle)) !== false) {
                $_mb_strlen = mb_strlen($needle);

                if (is_array($replace) || is_object($replace)) {
                    $replace  = array_values((array) $replace); // Force array.
                    $_replace = isset($replace[0]) ? (string) $replace[0] : '';
                } else {
                    $_replace = (string) $replace; // Force string.
                }
                $value = $this->substrReplace($value, $_replace, $_mb_strpos, $_mb_strlen);
                // unset($_mb_strpos, $_mb_strlen, $_replace);
            }
            return $value;
        }
    }

    /**
     * Escapes regex backreference chars deeply (i.e. `\\$` and `\\\\`).
     *
     * @since 140417 Initial release.
     *
     * @note This is a recursive scan running deeply into multiple dimensions of arrays/objects.
     * @note This routine will usually NOT include private, protected or static properties of an object class.
     *    However, private/protected properties *will* be included, if the current scope allows access to these private/protected properties.
     *    Static properties are NEVER considered by this routine, because static properties are NOT iterated by `foreach()`.
     *
     * @param mixed $value        Any value can be converted into an escaped string.
     *                            Actually, objects can't, but this recurses into objects.
     * @param int   $times        Number of escapes. Defaults to `1`.
     * @param bool  $___recursion Internal use only.
     *
     * @return string|array|object Escaped string, array, object.
     */
    protected function escRefsDeep($value, $times = 1, $___recursion = false)
    {
        if (is_array($value) || is_object($value)) {
            foreach ($value as &$_value) {
                $_value = $this->escRefsDeep($_value, $times, true);
            } // unset($_value); // Housekeeping.
            return $value;
        }
        $value = (string) $value;
        $times = abs((int) $times);

        return str_replace(['\\', '$'], [str_repeat('\\', $times).'\\', str_repeat('\\', $times).'$'], $value);
    }

    /**
     * Escapes regex backreference chars (i.e. `\\$` and `\\\\`).
     *
     * @since 140417 Initial release.
     *
     * @param string $string A string value.
     * @param int    $times  Number of escapes. Defaults to `1`.
     *
     * @return string Escaped string.
     */
    protected function escRefs($string, $times = 1)
    {
        return $this->escRefsDeep((string) $string, $times);
    }

    /**
     * Cleans up self-closing HTML tag lines.
     *
     * @since 140417 Initial release.
     *
     * @param string $html Self-closing HTML tag lines.
     *
     * @return string Cleaned self-closing HTML tag lines.
     */
    protected function cleanupSelfClosingHtmlTagLines($html)
    {
        if (!($html = (string) $html)) {
            return $html; // Nothing to do.
        }
        return trim(preg_replace('/\>\s*?'."[\r\n]+".'\s*\</u', ">\n<", $html));
    }

    /********************************************************************************************************/

    /*
     * Directory Utilities
     */

    /**
     * Public directory type.
     *
     * @since 140417 Initial release.
     *
     * @var string Indicates a public directory type.
     *
     * @internal This is for internal use only.
     */
    const DIR_PUBLIC_TYPE = 'public';

    /**
     * Private directory type.
     *
     * @since 140417 Initial release.
     *
     * @var string Indicates a private directory type.
     *
     * @internal This is for internal use only.
     */
    const DIR_PRIVATE_TYPE = 'private';

    /**
     * Get (and possibly create) the cache dir.
     *
     * @since 140417 Initial release.
     *
     * @param string $type      One of `$this::dir_public_type` or `$this::dir_private_type`.
     * @param string $checksum  Optional. If supplied, we'll build a nested sub-directory based on the checksum.
     * @param bool   $base_only Defaults to a FALSE value. If TRUE, return only the base directory.
     *                          i.e. Do NOT suffix the directory in any way. No host and no checksum.
     *
     * @throws \Exception If unable to create the cache dir.
     * @throws \Exception If cache directory is not readable/writable.
     *
     * @return string Server path to cache dir.
     */
    protected function cacheDir($type, $checksum = '', $base_only = false)
    {
        if ($type !== $this::DIR_PUBLIC_TYPE) {
            if ($type !== $this::DIR_PRIVATE_TYPE) {
                throw new \Exception('Invalid type.');
            }
        }
        $checksum = (string) $checksum;

        if (isset($checksum[4])) {
            $checksum = substr($checksum, 0, 5);
        } else {
            $checksum = ''; // Invalid or empty.
        }
        $cache_key = $type.$checksum.(int) $base_only;

        if (isset($this->cache[__FUNCTION__.'_'.$cache_key])) {
            return $this->cache[__FUNCTION__.'_'.$cache_key];
        }
        if (!empty($this->options['cache_dir_'.$type])) {
            $basedir = $this->nDirSeps($this->options['cache_dir_'.$type]);
        } elseif (defined('WP_CONTENT_DIR')) {
            $basedir = $this->nDirSeps(WP_CONTENT_DIR.'/htmlc/cache/'.$type);
        } elseif (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $basedir = $this->nDirSeps($_SERVER['DOCUMENT_ROOT'].'/htmlc/cache/'.$type);
        } else {
            throw new \Exception(sprintf('Unable to find a good location for the cache directory. Please set option: `%1$s`.', __FUNCTION__.'_'.$type));
        }
        if ($base_only) {
            $dir = $basedir; // Caller wants only the base directory.
        } else {
            $dir = $basedir; // Start with the base directory.
            $dir .= '/'.trim(preg_replace('/[^a-z0-9]/ui', '-', $this->currentUrlHost()), '-');
            $dir .= $checksum ? '/'.implode('/', str_split($checksum)) : '';
        }
        if (!is_dir($dir) && mkdir($dir, 0755, true)) {
            if ($type === $this::DIR_PUBLIC_TYPE && !is_file($basedir.'/.htaccess')) {
                if (!file_put_contents($basedir.'/.htaccess', $this->dir_htaccess_allow)) {
                    throw new \Exception(sprintf('Unable to create `.htaccess` file in public cache directory: `%1$s`.', $basedir));
                }
            }
            if ($type === $this::DIR_PRIVATE_TYPE && !is_file($basedir.'/.htaccess')) {
                if (!file_put_contents($basedir.'/.htaccess', $this->dir_htaccess_deny)) {
                    throw new \Exception(sprintf('Unable to create `.htaccess` file in private cache directory: `%1$s`.', $basedir));
                }
            }
        }
        if (!is_readable($dir) || !is_writable($dir)) {
            throw new \Exception(sprintf('Cache directory not readable/writable: `%1$s`. Failed on `%2$s`.', $basedir, $dir));
        }
        return $this->cache[__FUNCTION__.'_'.$cache_key] = $dir;
    }

    /**
     * Get (and possibly create) the cache dir URL.
     *
     * @since 140417 Initial release.
     *
     * @param string $type      One of `$this::public_type` or `$this::private_type`.
     * @param string $checksum  Optional. If supplied, we'll build a nested sub-directory based on the checksum.
     * @param bool   $base_only Defaults to a FALSE value. If TRUE, return only the base directory.
     *                          i.e. Do NOT suffix the directory in any way. No host and no checksum.
     *
     * @throws \Exception If unable to create the cache dir.
     * @throws \Exception If cache directory is not readable/writable.
     * @throws \Exception If unable to determine the URL for any reason.
     *
     * @return string URL to server-side cache directory.
     */
    protected function cacheDirUrl($type, $checksum = '', $base_only = false)
    {
        if ($type !== $this::DIR_PUBLIC_TYPE) {
            if ($type !== $this::DIR_PRIVATE_TYPE) {
                throw new \Exception('Invalid type.');
            }
        }
        $checksum = (string) $checksum;

        if (isset($checksum[4])) {
            $checksum = substr($checksum, 0, 5);
        } else {
            $checksum = ''; // Invalid or empty.
        }
        $cache_key = $type.$checksum.(int) $base_only;

        if (isset($this->cache[__FUNCTION__.'_'.$cache_key])) {
            return $this->cache[__FUNCTION__.'_'.$cache_key];
        }
        $basedir = $this->cacheDir($type, '', true);

        if (!empty($this->options['cache_dir_url_'.$type])) {
            $baseurl = $this->setUrlScheme(rtrim($this->options['cache_dir_url_'.$type], '/'));
        } elseif (defined('WP_CONTENT_DIR') && defined('WP_CONTENT_URL') && $basedir === $this->nDirSeps(WP_CONTENT_DIR.'/htmlc/cache/'.$type)) {
            $baseurl = $this->setUrlScheme(rtrim(WP_CONTENT_URL, '/').'/htmlc/cache/'.$type);
        } elseif (!empty($_SERVER['DOCUMENT_ROOT']) && mb_strpos($basedir, $_SERVER['DOCUMENT_ROOT']) === 0) {
            $baseurl = $this->currentUrlScheme().'://'.$this->currentUrlHost();
            $baseurl .= str_replace(rtrim($_SERVER['DOCUMENT_ROOT'], '/'), '', $basedir);
        } else {
            throw new \Exception(sprintf('Unable to determine URL to cache directory. Please set option: `%1$s`.', __FUNCTION__.'_'.$type));
        }
        if ($base_only) {
            $url = $baseurl; // Caller wants only the base directory.
        } else {
            $url = $baseurl; // Start with the base URL.
            $url .= '/'.trim(preg_replace('/[^a-z0-9]/ui', '-', $this->currentUrlHost()), '-');
            $url .= $checksum ? '/'.implode('/', str_split($checksum)) : '';
        }
        return $this->cache[__FUNCTION__.'_'.$cache_key] = $url;
    }

    /**
     * Cache cleanup routine.
     *
     * @since 140417 Initial release.
     *
     * @note This routine is always host-specific.
     *    i.e. We cleanup cache files for the current host only.
     */
    protected function cleanupCacheDirs()
    {
        if (($benchmark = !empty($this->options['benchmark']) && $this->options['benchmark'] === 'details')) {
            $time = microtime(true);
        }
        $public_cache_dir  = $this->cacheDir($this::DIR_PUBLIC_TYPE);
        $private_cache_dir = $this->cacheDir($this::DIR_PRIVATE_TYPE);
        $min_mtime         = strtotime('-'.$this->cache_expiration_time);

        /** @var $_dir_file \RecursiveDirectoryIterator For IDEs. */
        foreach ($this->dirRegexIteration($public_cache_dir, '/\/compressor\-part\..*$/') as $_dir_file) {
            if (($_dir_file->isFile() || $_dir_file->isLink()) && $_dir_file->getMTime() < $min_mtime - 3600) {
                if ($_dir_file->isWritable()) {
                    unlink($_dir_file->getPathname());
                }
            }
        }
        /** @var $_dir_file \RecursiveDirectoryIterator For IDEs. */
        foreach ($this->dirRegexIteration($private_cache_dir, '/\/compressor\-parts\..*$/') as $_dir_file) {
            if (($_dir_file->isFile() || $_dir_file->isLink()) && $_dir_file->getMTime() < $min_mtime) {
                if ($_dir_file->isWritable()) {
                    unlink($_dir_file->getPathname());
                }
            }
        } // unset($_dir_file); // Housekeeping.

        if ($benchmark && !empty($time)) {
            $this->benchmark->addTime(
                __FUNCTION__,
                $time, // Caller, start time, task performed.
                'cleaning up the public/private cache directories'
            );
        }
    }

    /**
     * Regex directory iterator.
     *
     * @since 140417 Initial release.
     *
     * @param string $dir   Path to a directory.
     * @param string $regex Regular expression.
     *
     * @return \RegexIterator
     */
    protected function dirRegexIteration($dir, $regex)
    {
        $dir   = (string) $dir;
        $regex = (string) $regex;

        $dir_iterator      = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_SELF | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
        $iterator_iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::CHILD_FIRST);
        $regex_iterator    = new \RegexIterator($iterator_iterator, $regex, \RegexIterator::MATCH, \RegexIterator::USE_KEY);

        return $regex_iterator;
    }

    /**
     * Normalizes directory/file separators.
     *
     * @since 140417 Initial release.
     *
     * @param string $dir_file             Directory/file path.
     * @param bool   $allow_trailing_slash Defaults to FALSE.
     *                                     If TRUE; and `$dir_file` contains a trailing slash; we'll leave it there.
     *
     * @return string Normalized directory/file path.
     */
    protected function nDirSeps($dir_file, $allow_trailing_slash = false)
    {
        if (($dir_file = (string) $dir_file) === '') {
            return $dir_file; // Nothing to do.
        }
        if (mb_strpos($dir_file, '://' !== false)) {
            if (preg_match('/^(?P<stream_wrapper>[a-z0-9]+)\:\/\//ui', $dir_file, $stream_wrapper)) {
                $dir_file = preg_replace('/^(?P<stream_wrapper>[a-z0-9]+)\:\/\//ui', '', $dir_file);
            }
            if (mb_strpos($dir_file, ':' !== false)) {
                if (preg_match('/^(?P<drive_letter>[a-z])\:[\/\\\\]/ui', $dir_file)) {
                    $dir_file = preg_replace_callback('/^(?P<drive_letter>[a-z])\:[\/\\\\]/ui', create_function('$m', 'return mb_strtoupper($m[0]);'), $dir_file);
                }
                $dir_file = preg_replace('/\/+/u', '/', str_replace([DIRECTORY_SEPARATOR, '\\', '/'], '/', $dir_file));
            }
            $dir_file = ($allow_trailing_slash) ? $dir_file : rtrim($dir_file, '/'); // Strip trailing slashes.
        }
        if (!empty($stream_wrapper[0])) {
            $dir_file = mb_strtolower($stream_wrapper[0]).$dir_file;
        }
        return $dir_file; // Normalized now.
    }

    /**
     * Apache `.htaccess` denial snippet.
     *
     * @since 140417 Initial release.
     *
     * @var string Compatible with Apache 2.1+. Tested up to 2.4.7.
     */
    protected $dir_htaccess_deny = "<IfModule authz_core_module>\n\tRequire all denied\n</IfModule>\n<IfModule !authz_core_module>\n\tdeny from all\n</IfModule>";

    /**
     * Apache `.htaccess` for public files.
     *
     * @since 150321 Improving publicly cacheable files.
     *
     * @var string Compatible with Apache 2.1+. Tested up to 2.4.7.
     */
    protected $dir_htaccess_allow = "<IfModule authz_core_module>\n\tRequire all granted\n</IfModule>\n<IfModule !authz_core_module>\n\tallow from all\n</IfModule>\n\n<IfModule headers_module>\n\t<FilesMatch \"\\.(html|js|css)$\">\n\t\tHeader append Vary: Accept-Encoding\n\t</FilesMatch>\n</IfModule>";

    /********************************************************************************************************/

    /*
     * URL Utilities
     */

    /**
     * Indicates scheme component in a URL.
     *
     * @since 140417 Initial release.
     *
     * @var int Part of a bitmask.
     *
     * @internal Internal use only.
     */
    const URL_SCHEME = 1;

    /**
     * Indicates user component in a URL.
     *
     * @since 140417 Initial release.
     *
     * @var int Part of a bitmask.
     *
     * @internal Internal use only.
     */
    const URL_USER = 2;

    /**
     * Indicates pass component in a URL.
     *
     * @since 140417 Initial release.
     *
     * @var int Part of a bitmask.
     *
     * @internal Internal use only.
     */
    const URL_PASS = 4;

    /**
     * Indicates host component in a URL.
     *
     * @since 140417 Initial release.
     *
     * @var int Part of a bitmask.
     *
     * @internal Internal use only.
     */
    const URL_HOST = 8;

    /**
     * Indicates port component in a URL.
     *
     * @since 140417 Initial release.
     *
     * @var int Part of a bitmask.
     *
     * @internal Internal use only.
     */
    const URL_PORT = 16;

    /**
     * Indicates path component in a URL.
     *
     * @since 140417 Initial release.
     *
     * @var int Part of a bitmask.
     *
     * @internal Internal use only.
     */
    const URL_PATH = 32;

    /**
     * Indicates query component in a URL.
     *
     * @since 140417 Initial release.
     *
     * @var int Part of a bitmask.
     *
     * @internal Internal use only.
     */
    const URL_QUERY = 64;

    /**
     * Indicates fragment component in a URL.
     *
     * @since 140417 Initial release.
     *
     * @var int Part of a bitmask.
     *
     * @internal Internal use only.
     */
    const URL_FRAGMENT = 128;

    /**
     * Is the current request over SSL?
     *
     * @since 140417 Initial release.
     *
     * @return bool TRUE if over SSL; else FALSE.
     */
    protected function currentUrlSsl()
    {
        if (isset(static::$static[__FUNCTION__])) {
            return static::$static[__FUNCTION__];
        }
        if (!empty($_SERVER['SERVER_PORT'])) {
            if ((int) $_SERVER['SERVER_PORT'] === 443) {
                return static::$static[__FUNCTION__] = true;
            }
        }
        if (!empty($_SERVER['HTTPS'])) {
            if (filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN)) {
                return static::$static[__FUNCTION__] = true;
            }
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            if (strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0) {
                return static::$static[__FUNCTION__] = true;
            }
        }
        return static::$static[__FUNCTION__] = false;
    }

    /**
     * Gets the current scheme (via environment variables).
     *
     * @since 140417 Initial release.
     *
     * @throws \Exception If unable to determine the current scheme.
     *
     * @return string The current scheme, else an exception is thrown on failure.
     */
    protected function currentUrlScheme()
    {
        if (isset(static::$static[__FUNCTION__])) {
            return static::$static[__FUNCTION__];
        }
        if (!empty($this->options['current_url_scheme'])) {
            return static::$static[__FUNCTION__] = $this->nUrlScheme($this->options['current_url_scheme']);
        }/* See https://github.com/websharks/html-compressor/issues/73
        if (!empty($_SERVER['REQUEST_SCHEME'])) {
            return (static::$static[__FUNCTION__] = $this->nUrlScheme($_SERVER['REQUEST_SCHEME']));
        }*/
        return static::$static[__FUNCTION__] = ($this->currentUrlSsl()) ? 'https' : 'http';
    }

    /**
     * Gets the current host name (via environment variables).
     *
     * @since 140417 Initial release.
     *
     * @throws \Exception If `$_SERVER['HTTP_HOST']` is empty.
     *
     * @return string The current host name, else an exception is thrown on failure.
     */
    protected function currentUrlHost()
    {
        if (isset(static::$static[__FUNCTION__])) {
            return static::$static[__FUNCTION__];
        }
        if (!empty($this->options['current_url_host'])) {
            return static::$static[__FUNCTION__] = $this->nUrlHost($this->options['current_url_host']);
        }
        if (empty($_SERVER['HTTP_HOST'])) {
            throw new \Exception('Missing required `$_SERVER[\'HTTP_HOST\']`.');
        }
        return static::$static[__FUNCTION__] = $this->nUrlHost($_SERVER['HTTP_HOST']);
    }

    /**
     * Gets the current URI (via environment variables).
     *
     * @since 140417 Initial release.
     *
     * @throws \Exception If unable to determine the current URI.
     *
     * @return string The current URI, else an exception is thrown on failure.
     */
    protected function currentUrlUri()
    {
        if (isset(static::$static[__FUNCTION__])) {
            return static::$static[__FUNCTION__];
        }
        if (!empty($this->options['current_url_uri'])) {
            return static::$static[__FUNCTION__] = $this->mustParseUri($this->options['current_url_uri']);
        }
        if (empty($_SERVER['REQUEST_URI'])) {
            throw new \Exception('Missing required `$_SERVER[\'REQUEST_URI\']`.');
        }
        return static::$static[__FUNCTION__] = $this->mustParseUri($_SERVER['REQUEST_URI']);
    }

    /**
     * Current URI is excluded?
     *
     * @since 160117 Adding support for URI exclusions.
     *
     * @return bool Returns `TRUE` if current URI matches an exclusion rule.
     */
    protected function isCurrentUrlUriExcluded()
    {
        if ($this->regex_uri_exclusions && preg_match($this->regex_uri_exclusions, $this->currentUrlUri())) {
            return true;
        } elseif ($this->built_in_regex_uri_exclusions && preg_match($this->built_in_regex_uri_exclusions, $this->currentUrlUri())) {
            return true;
        }
        return false;
    }

    /**
     * URL to current request.
     *
     * @since 140417 Initial release.
     *
     * @return string The current URL.
     */
    protected function currentUrl()
    {
        if (isset(static::$static[__FUNCTION__])) {
            return static::$static[__FUNCTION__];
        }
        $url = $this->currentUrlScheme().'://';
        $url .= $this->currentUrlHost();
        $url .= $this->currentUrlUri();

        return static::$static[__FUNCTION__] = $url;
    }

    /**
     * Normalizes a URL scheme.
     *
     * @since 140417 Initial release.
     *
     * @param string $scheme An input URL scheme.
     *
     * @return string A normalized URL scheme (always lowercase).
     */
    protected function nUrlScheme($scheme)
    {
        if (!($scheme = (string) $scheme)) {
            return $scheme; // Nothing to do.
        }
        if (mb_strpos($scheme, ':') !== false) {
            $scheme = strstr($scheme, ':', true);
        }
        return mb_strtolower($scheme);
    }

    /**
     * Normalizes a URL host name.
     *
     * @since 140417 Initial release.
     *
     * @param string $host An input URL host name.
     *
     * @return string A normalized URL host name (always lowercase).
     */
    protected function nUrlHost($host)
    {
        if (!($host = (string) $host)) {
            return $host; // Nothing to do.
        }
        return mb_strtolower($host);
    }

    /**
     * Converts all ampersand entities in a URL (or a URI/query/fragment only); to just `&`.
     *
     * @since 140417 Initial release.
     *
     * @param string $url_uri_query_fragment A full URL; or a partial URI;
     *                                       or only a query string, or only a fragment. Any of these can be normalized here.
     *
     * @return string Input URL (or a URI/query/fragment only); after having been normalized by this routine.
     */
    protected function nUrlAmps($url_uri_query_fragment)
    {
        if (!($url_uri_query_fragment = (string) $url_uri_query_fragment)) {
            return $url_uri_query_fragment; // Nothing to do.
        }
        if (mb_strpos($url_uri_query_fragment, '&') === false) {
            return $url_uri_query_fragment; // Nothing to do.
        }
        return preg_replace('/&amp;|&#0*38;|&#[xX]0*26;/u', '&', $url_uri_query_fragment);
    }

    /**
     * Normalizes a URL path from a URL (or a URI/query/fragment only).
     *
     * @since 140417 Initial release.
     *
     * @param string $url_uri_query_fragment A full URL; or a partial URI;
     *                                       or only a query string, or only a fragment. Any of these can be normalized here.
     * @param bool   $allow_trailing_slash   Defaults to a FALSE value.
     *                                       If TRUE, and `$url_uri_query_fragment` contains a trailing slash; we'll leave it there.
     *
     * @return string Normalized URL (or a URI/query/fragment only).
     */
    protected function nUrlPathSeps($url_uri_query_fragment, $allow_trailing_slash = false)
    {
        if (($url_uri_query_fragment = (string) $url_uri_query_fragment) === '') {
            return $url_uri_query_fragment; // Nothing to do.
        }
        if (!($parts = $this->parseUrl($url_uri_query_fragment, null, 0))) {
            $parts['path'] = $url_uri_query_fragment;
        }
        if ($parts['path'] !== '') {
            // Normalize directory separators.
            $parts['path'] = $this->nDirSeps($parts['path'], $allow_trailing_slash);
        }
        return $this->unparseUrl($parts, 0); // Back together again.
    }

    /**
     * Sets a particular scheme.
     *
     * @since 140417 Initial release.
     *
     * @param string $url    A full URL.
     * @param string $scheme Optional. The scheme to use (i.e. `//`, `https`, `http`).
     *                       Use `//` to use a cross-protocol compatible scheme.
     *                       Defaults to the current scheme.
     *
     * @return string The full URL w/ `$scheme`.
     */
    protected function setUrlScheme($url, $scheme = '')
    {
        if (!($url = (string) $url)) {
            return $url; // Nothing to do.
        }
        $scheme = (string) $scheme;

        if (!$scheme) {
            $scheme = $this->currentUrlScheme();
        }
        if ($scheme !== '//') {
            $scheme = $this->nUrlScheme($scheme).'://';
        }
        return preg_replace('/^(?:[a-z0-9]+\:)?\/\//ui', $this->escRefs($scheme), $url);
    }

    /**
     * Checks if a given URL is local or external to the current host.
     *
     * @since 140417 Initial release.
     *
     * @note Care should be taken when calling upon this method. We need to be 100% sure
     *    we are NOT calling this against a nested remote/relative URL, URI, query or fragment.
     *    This method assumes the URL being analyzed is from the HTML source code.
     *
     * @param string $url_uri_query_fragment A full URL; or a partial URI;
     *                                       or only a query string, or only a fragment. Any of these can be checked here.
     *
     * @return bool TRUE if external; else FALSE.
     */
    protected function isUrlExternal($url_uri_query_fragment)
    {
        if (mb_strpos($url_uri_query_fragment, '//') === false) {
            return false; // Relative.
        }
        return mb_stripos($url_uri_query_fragment, '//'.$this->currentUrlHost()) === false;
    }

    /**
     * Parses a URL (or a URI/query/fragment only) into an array.
     *
     * @since 140417 Initial release.
     *
     * @param string $url_uri_query_fragment A full URL; or a partial URI;
     *                                       or only a query string, or only a fragment. Any of these can be parsed here.
     *
     * @note A query string or fragment MUST be prefixed with the appropriate delimiters.
     *    This is bad `name=value` (interpreted as path). This is good `?name=value` (query string).
     *    This is bad `anchor` (interpreted as path). This is good `#fragment` (fragment).
     *
     * @param null|int $component Same as PHP's `parse_url()` component.
     *                            Defaults to NULL; which defaults to an internal value of `-1` before we pass to PHP's `parse_url()`.
     * @param null|int $normalize A bitmask. Defaults to NULL (indicating a default bitmask).
     *                            Defaults include: {@link self::url_scheme}, {@link self::url_host}, {@link self::url_path}.
     *                            However, we DO allow a trailing slash (even if path is being normalized by this parameter).
     *
     * @return array|string|int|null If a component is requested, returns a string component (or an integer in the case of `PHP_URL_PORT`).
     *                               If a specific component is NOT requested, this returns a full array, of all component values.
     *                               Else, this returns NULL on any type of failure (even if a component was requested).
     *
     * @note Arrays returned by this method, will include a value for each component (a bit different from PHP's `parse_url()` function).
     *    We start with an array of defaults (i.e. all empty strings, and `0` for the port number).
     *    Components found in the URL are then merged into these default values.
     *    The array is also sorted by key (e.g. alphabetized).
     */
    protected function parseUrl($url_uri_query_fragment, $component = null, $normalize = null)
    {
        $url_uri_query_fragment = (string) $url_uri_query_fragment;

        if (!isset($normalize)) {
            $normalize = $this::URL_SCHEME | $this::URL_HOST | $this::URL_PATH;
        }
        if (mb_strpos($url_uri_query_fragment, '//') === 0) {
            $url_uri_query_fragment = $this->currentUrlScheme().':'.$url_uri_query_fragment; // So URL is parsed properly.
            // Works around a bug in `parse_url()` prior to PHP v5.4.7. See: <http://php.net/manual/en/function.parse-url.php>.
            $x_protocol_scheme = true; // Flag this, so we can remove scheme below.
        } else {
            $x_protocol_scheme = false; // No scheme; or scheme is NOT cross-protocol compatible.
        }
        $parsed = @parse_url($url_uri_query_fragment, !isset($component) ? -1 : $component);

        if ($x_protocol_scheme) {
            if (!isset($component) && is_array($parsed)) {
                $parsed['scheme'] = ''; // No scheme.
            } elseif ($component === PHP_URL_SCHEME) {
                $parsed = ''; // No scheme.
            }
        }
        if ($normalize & $this::URL_SCHEME) {
            if (!isset($component) && is_array($parsed)) {
                if (!isset($parsed['scheme'])) {
                    $parsed['scheme'] = ''; // No scheme.
                }
                $parsed['scheme'] = $this->nUrlScheme($parsed['scheme']);
            } elseif ($component === PHP_URL_SCHEME) {
                if (!is_string($parsed)) {
                    $parsed = ''; // No scheme.
                }
                $parsed = $this->nUrlScheme($parsed);
            }
        }
        if ($normalize & $this::URL_HOST) {
            if (!isset($component) && is_array($parsed)) {
                if (!isset($parsed['host'])) {
                    $parsed['host'] = ''; // No host.
                }
                $parsed['host'] = $this->nUrlHost($parsed['host']);
            } elseif ($component === PHP_URL_HOST) {
                if (!is_string($parsed)) {
                    $parsed = ''; // No scheme.
                }
                $parsed = $this->nUrlHost($parsed);
            }
        }
        if ($normalize & $this::URL_PATH) {
            if (!isset($component) && is_array($parsed)) {
                if (!isset($parsed['path'])) {
                    $parsed['path'] = '/'; // Home directory.
                }
                $parsed['path'] = $this->nUrlPathSeps($parsed['path'], true);
                if (mb_strpos($parsed['path'], '/') !== 0) {
                    $parsed['path'] = '/'.$parsed['path'];
                }
            } elseif ($component === PHP_URL_PATH) {
                if (!is_string($parsed)) {
                    $parsed = '/'; // Home directory.
                }
                $parsed = $this->nUrlPathSeps($parsed, true);
                if (mb_strpos($parsed, '/') !== 0) {
                    $parsed = '/'.$parsed;
                }
            }
        }
        if (in_array(gettype($parsed), ['array', 'string', 'integer'], true)) {
            if (is_array($parsed)) {
                $defaults = [
                    'fragment' => '',
                    'host'     => '',
                    'pass'     => '',
                    'path'     => '',
                    'port'     => 0,
                    'query'    => '',
                    'scheme'   => '',
                    'user'     => '',
                ];
                $parsed         = array_merge($defaults, $parsed);
                $parsed['port'] = (int) $parsed['port'];
                ksort($parsed); // Sort by key.
            }
            return $parsed; // A `string|integer|array`.
        }
        return; // Default return value.
    }

    /**
     * Parses a URL (or a URI/query/fragment only) into an array.
     *
     * @since 140417 Initial release.
     *
     * @throws \Exception If unable to parse.
     *
     * @return array|string|int|null {@inheritdoc}
     *
     * @see parseUrl()
     * {@inheritdoc} parseUrl()
     */
    protected function mustParseUrl() // Arguments are NOT listed here.
    {
        if (is_null($parsed = call_user_func_array([$this, 'parseUrl'], func_get_args()))) {
            throw new \Exception(sprintf('Unable to parse: `%1$s`.', (string) func_get_arg(0)));
        }
        return $parsed;
    }

    /**
     * Unparses a URL (putting it all back together again).
     *
     * @since 140417 Initial release.
     *
     * @param array    $parsed    An array with at least one URL component.
     * @param null|int $normalize A bitmask. Defaults to NULL (indicating a default bitmask).
     *                            Defaults include: {@link self::url_scheme}, {@link self::url_host}, {@link self::url_path}.
     *                            However, we DO allow a trailing slash (even if path is being normalized by this parameter).
     *
     * @return string A full or partial URL, based on components provided in the `$parsed` array.
     *                It IS possible to receive an empty string, when/if `$parsed` does NOT contain any portion of a URL.
     */
    protected function unparseUrl(array $parsed, $normalize = null)
    {
        $unparsed = ''; // Initialize string value.

        if (!isset($normalize)) {
            $normalize = $this::URL_SCHEME | $this::URL_HOST | $this::URL_PATH;
        }
        if ($normalize & $this::URL_SCHEME) {
            if (!isset($parsed['scheme'])) {
                $parsed['scheme'] = ''; // No scheme.
            }
            $parsed['scheme'] = $this->nUrlScheme($parsed['scheme']);
        }
        if (!empty($parsed['scheme'])) {
            $unparsed .= $parsed['scheme'].'://';
        } elseif (isset($parsed['scheme']) && !empty($parsed['host'])) {
            $unparsed .= '//'; // Cross-protocol compatible.
        }
        if (!empty($parsed['user'])) {
            $unparsed .= $parsed['user'];
            if (!empty($parsed['pass'])) {
                $unparsed .= ':'.$parsed['pass'];
            }
            $unparsed .= '@';
        }
        if ($normalize & $this::URL_HOST) {
            if (!isset($parsed['host'])) {
                $parsed['host'] = ''; // No host.
            }
            $parsed['host'] = $this->nUrlHost($parsed['host']);
        }
        if (!empty($parsed['host'])) {
            $unparsed .= $parsed['host'];
        }
        if (!empty($parsed['port'])) {
            $unparsed .= ':'.$parsed['port'];
        } // A `0` value is excluded here.

        if ($normalize & $this::URL_PATH) {
            if (!isset($parsed['path'])) {
                $parsed['path'] = '/'; // Home directory.
            }
            $parsed['path'] = $this->nUrlPathSeps($parsed['path'], true);
            if (mb_strpos($parsed['path'], '/') !== 0) {
                $parsed['path'] = '/'.$parsed['path'];
            }
        }
        if (isset($parsed['path'])) {
            $unparsed .= $parsed['path'];
        }
        if (!empty($parsed['query'])) {
            $unparsed .= '?'.$parsed['query'];
        }
        if (!empty($parsed['fragment'])) {
            $unparsed .= '#'.$parsed['fragment'];
        }
        return $unparsed;
    }

    /**
     * Unparses a URL (putting it all back together again).
     *
     * @since 140417 Initial release.
     *
     * @throws \Exception If unable to unparse.
     *
     * @return string {@inheritdoc}
     *
     * @see unparseUrl()
     * {@inheritdoc} unparseUrl()
     */
    protected function mustUnparseUrl() // Arguments are NOT listed here.
    {
        if (($unparsed = call_user_func_array([$this, 'unparseUrl'], func_get_args())) === '') {
            throw new \Exception(sprintf('Unable to unparse: `%1$s`.', print_r(func_get_arg(0), true)));
        }
        return $unparsed;
    }

    /**
     * Parses URI parts from a URL (or a URI/query/fragment only).
     *
     * @since 140417 Initial release.
     *
     * @param string   $url_uri_query_fragment A full URL; or a partial URI;
     *                                         or only a query string, or only a fragment. Any of these can be parsed here.
     * @param null|int $normalize              A bitmask. Defaults to NULL (indicating a default bitmask).
     *                                         Defaults include: {@link self::url_scheme}, {@link self::url_host}, {@link self::url_path}.
     *                                         However, we DO allow a trailing slash (even if path is being normalized by this parameter).
     *
     * @return array|null An array with the following components, else NULL on any type of failure.
     *
     *    • `path`(string) Possible URI path.
     *    • `query`(string) A possible query string.
     *    • `fragment`(string) A possible fragment.
     */
    protected function parseUriParts($url_uri_query_fragment, $normalize = null)
    {
        if (($parts = $this->parseUrl($url_uri_query_fragment, null, $normalize))) {
            return ['path' => $parts['path'], 'query' => $parts['query'], 'fragment' => $parts['fragment']];
        }
        return; // Default return value.
    }

    /**
     * Parses URI parts from a URL (or a URI/query/fragment only).
     *
     * @since 140417 Initial release.
     *
     * @throws \Exception If unable to parse.
     *
     * @return array|null {@inheritdoc}
     *
     * @see parseUriParts()
     * {@inheritdoc} parseUriParts()
     */
    protected function mustParseUriParts() // Arguments are NOT listed here.
    {
        if (is_null($parts = call_user_func_array([$this, 'parseUriParts'], func_get_args()))) {
            throw new \Exception(sprintf('Unable to parse: `%1$s`.', (string) func_get_arg(0)));
        }
        return $parts;
    }

    /**
     * Parses a URI from a URL (or a URI/query/fragment only).
     *
     * @since 140417 Initial release.
     *
     * @param string   $url_uri_query_fragment A full URL; or a partial URI;
     *                                         or only a query string, or only a fragment. Any of these can be parsed here.
     * @param null|int $normalize              A bitmask. Defaults to NULL (indicating a default bitmask).
     *                                         Defaults include: {@link self::url_scheme}, {@link self::url_host}, {@link self::url_path}.
     *                                         However, we DO allow a trailing slash (even if path is being normalized by this parameter).
     * @param bool     $include_fragment       Defaults to TRUE. Include a possible fragment?
     *
     * @return string|null A URI (i.e. a URL path), else NULL on any type of failure.
     */
    protected function parseUri($url_uri_query_fragment, $normalize = null, $include_fragment = true)
    {
        if (($parts = $this->parseUriParts($url_uri_query_fragment, $normalize))) {
            if (!$include_fragment) {
                unset($parts['fragment']);
            }
            return $this->unparseUrl($parts, $normalize);
        }
        return; // Default return value.
    }

    /**
     * Parses a URI from a URL (or a URI/query/fragment only).
     *
     * @since 140417 Initial release.
     *
     * @throws \Exception If unable to parse.
     *
     * @return string|null {@inheritdoc}
     *
     * @see parseUri()
     * {@inheritdoc} parseUri()
     */
    protected function mustParseUri() // Arguments are NOT listed here.
    {
        if (is_null($parsed = call_user_func_array([$this, 'parseUri'], func_get_args()))) {
            throw new \Exception(sprintf('Unable to parse: `%1$s`.', (string) func_get_arg(0)));
        }
        return $parsed;
    }

    /**
     * Resolves a relative URL into a full URL from a base.
     *
     * @since 140417 Initial release.
     *
     * @param string $relative_url_uri_query_fragment A full URL; or a partial URI;
     *                                                or only a query string, or only a fragment. Any of these can be parsed here.
     * @param string $base_url                        A base URL. Optional. Defaults to current location.
     *                                                This defaults to the current URL. See: {@link current_url()}.
     *
     * @throws \Exception If unable to parse `$relative_url_uri_query_fragment`.
     * @throws \Exception If there is no `$base`, and we're unable to detect current location.
     * @throws \Exception If unable to parse `$base` (or if `$base` has no host name).
     *
     * @return string A full URL; else an exception will be thrown.
     */
    protected function resolveRelativeUrl($relative_url_uri_query_fragment, $base_url = '')
    {
        $relative_url_uri_query_fragment = (string) $relative_url_uri_query_fragment;
        $base_url                        = (string) $base_url;

        if (!$base_url) {
            $base_url = $this->currentUrl();
        } // Auto-detects current URL/location.

        $relative_parts         = $this->mustParseUrl($relative_url_uri_query_fragment, null, 0);
        $relative_parts['path'] = $this->nUrlPathSeps($relative_parts['path'], true);
        $base_parts             = $parts             = $this->mustParseUrl($base_url);

        if ($relative_parts['host']) {
            if (!$relative_parts['scheme']) {
                $relative_parts['scheme'] = $base_parts['scheme'];
            }
            return $this->mustUnparseUrl($relative_parts);
        }
        if (!$base_parts['host']) {
            throw new \Exception(sprintf('Unable to parse (missing base host name): `%1$s`.', $base_url));
        }
        if (isset($relative_parts['path'][0])) {
            if (mb_strpos($relative_parts['path'], '/') === 0) {
                $parts['path'] = ''; // Reduce to nothing if relative is absolute.
            } else {
                $parts['path'] = preg_replace('/\/[^\/]*$/u', '', $parts['path']).'/'; // Reduce to nearest `/`.
            }
            // Replace `/./` and `/foo/../` with `/` (resolve relatives).
            for ($_i = 1, $parts['path'] = $parts['path'].$relative_parts['path']; $_i > 0;) {
                $parts['path'] = preg_replace(['/\/\.\//u', '/\/(?!\.\.)[^\/]+\/\.\.\//u'], '/', $parts['path'], -1, $_i);
            } // unset($_i); // Just a little housekeeping.

            // We can ditch any unresolvable `../` patterns now.
            // For instance, if there were too many `../../../../../` back references.
            $parts['path'] = str_replace('../', '', $parts['path']);

            $parts['query'] = $relative_parts['query'];
            // Use relative query.
        } elseif (isset($relative_parts['query'][0])) {
            $parts['query'] = $relative_parts['query'];
        } // Relative query string supersedes base.

        $parts['fragment'] = $relative_parts['fragment']; // Always changes.

        return $this->mustUnparseUrl($parts); // Resolved now.
    }

    /**
     * Remote HTTP communication.
     *
     * @since 150820 Improving HTTP connection handling.
     *
     * @param string $url A URL to connect to.
     *
     * @throws \Exception If unable to get the URL; i.e., if the response code is >= 400.
     *
     * @return string Output data from the HTTP response; excluding headers (i.e., body only).
     *
     * @note By throwing an exception on any failure, we can avoid a circumstance where
     *  multiple failures and/or timeouts occur in succession against the same host.
     *  Any connection failure stops compression and a caller should catch the exception
     *  and fail softly; using the exception message for debugging purposes.
     */
    protected function mustGetUrl($url)
    {
        $url      = (string) $url; // Force string value.
        $response = $this->remote($url, '', 5, 15, [], '', true, true);

        if ($response['code'] >= 400) {
            throw new \Exception(sprintf('HTTP response code: `%1$s`. Unable to get URL: `%2$s`.', $response['code'], $url));
        }
        return $response['body'];
    }

    /**
     * Remote HTTP communication.
     *
     * @since 140417 Initial release.
     *
     * @param string       $url             A URL to connect to.
     * @param string|array $body            Optional request body.
     * @param int          $max_con_secs    Defaults to `20` seconds.
     * @param int          $max_stream_secs Defaults to `20` seconds.
     * @param array        $headers         Any additional headers to send with the request.
     * @param string       $cookie_file     If cookies are to be collected, store them here.
     * @param bool         $fail_on_error   Defaults to a value of TRUE; fail on status >= `400`.
     * @param bool         $return_array    Defaults to a value of FALSE; response body returned only.
     *
     * @throws \Exception If unable to find a workable HTTP transport layer.
     *                    Supported transports include: `curl` and `fopen`.
     *
     * @return string|array Output data from the HTTP response; excluding headers (i.e., body only).
     */
    protected function remote($url, $body = '', $max_con_secs = 5, $max_stream_secs = 15, array $headers = [], $cookie_file = '', $fail_on_error = true, $return_array = false)
    {
        $can_follow = !filter_var(ini_get('safe_mode'), FILTER_VALIDATE_BOOLEAN) && !ini_get('open_basedir');

        if (($benchmark = !empty($this->options['benchmark']) && $this->options['benchmark'] === 'details')) {
            $time = microtime(true);
        }
        $response_body = ''; // Initialize.
        $response_code = 0; // Initialize.

        $custom_request_method = '';
        $url                   = (string) $url;
        $max_con_secs          = (int) $max_con_secs;
        $max_stream_secs       = (int) $max_stream_secs;

        if (!is_array($headers)) {
            $headers = [];
        }
        $cookie_file = (string) $cookie_file;

        $custom_request_regex = // e.g.`PUT::http://www.example.com/`
            '/^(?P<custom_request_method>(?:GET|POST|PUT|PATCH|DELETE))\:{2}(?P<url>.+)/ui';

        if (preg_match($custom_request_regex, $url, $_url_parts)) {
            $url                   = $_url_parts['url']; // URL after `::`.
            $custom_request_method = mb_strtoupper($_url_parts['custom_request_method']);
        } // unset($_url_parts); // Housekeeping.

        if (is_array($body)) {
            $body = http_build_query($body, '', '&');
        } else {
            $body = (string) $body;
        }
        if (!$url) {
            goto finale;
        } // Nothing to do here.
        /* ---------------------------------------------------------- */

        curl_transport: // cURL transport layer (recommended).

        if (!extension_loaded('curl') || !is_callable('curl_version')
           || (mb_stripos($url, 'https:') === 0 && !(is_array($curl_version = curl_version())
           && $curl_version['features'] & CURL_VERSION_SSL))
        ) {
            goto fopen_transport; // cURL will not work in this case.
        }
        $curl_opts = [
            CURLOPT_URL          => $url,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

            CURLOPT_CONNECTTIMEOUT => $max_con_secs,
            CURLOPT_TIMEOUT        => $max_stream_secs,
            // See: <http://jas.xyz/1gZKj8v>

            CURLOPT_FOLLOWLOCATION => $can_follow,
            CURLOPT_MAXREDIRS      => $can_follow ? 5 : 0,

            CURLOPT_ENCODING    => '',
            CURLOPT_HTTPHEADER  => $headers,
            CURLOPT_REFERER     => $this->currentUrl(),
            CURLOPT_AUTOREFERER => true, // On redirects.
            CURLOPT_USERAGENT   => $this->product_title,

            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_VERBOSE        => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FAILONERROR    => $fail_on_error,
        ];
        if ($body) {
            if ($custom_request_method) {
                $curl_opts += [CURLOPT_CUSTOMREQUEST => $custom_request_method, CURLOPT_POSTFIELDS => $body];
            } else {
                $curl_opts += [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body];
            }
        } elseif ($custom_request_method) {
            $curl_opts += [CURLOPT_CUSTOMREQUEST => $custom_request_method];
        }
        if ($cookie_file) {
            $curl_opts += [CURLOPT_COOKIEJAR => $cookie_file, CURLOPT_COOKIEFILE => $cookie_file];
        }
        if (!($curl = curl_init()) || !curl_setopt_array($curl, $curl_opts)) {
            throw new \Exception(sprintf('Failed to initialize cURL for remote connection to: `%1$s`.', $url).
                                 sprintf(' The following cURL options were necessary: `%1$s`.', print_r($curl_opts, true)));
        }
        $response_body = trim((string) curl_exec($curl));
        $response_code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($benchmark) {
            $this->benchmark->addData(
                __FUNCTION__,
                ['curl_getinfo' => curl_getinfo($curl)]
            );
        }
        curl_close($curl); // Close the resource handle now.

        if ($fail_on_error && $response_code >= 400) {
            $response_body = ''; // Fail silently.
        }
        goto finale; // All done here, jump to finale.

        /* ---------------------------------------------------------- */

        fopen_transport: // Depends on `allow_url_fopen` in `php.ini`.

        if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN) || $cookie_file
           || (mb_stripos($url, 'https:') === 0 && !in_array('ssl', stream_get_transports(), true))
        ) {
            throw new \Exception('Unable to find a workable transport layer for remote HTTP communication.'.
                                 ' Please install the cURL & OpenSSL extensions for PHP.');
        }
        $stream_options = [
            'http' => [
             'protocol_version' => 1.1,
             'method'           => $custom_request_method
                 ? $custom_request_method : ($body ? 'POST' : 'GET'),

             'follow_location' => $can_follow,
             'max_redirects'   => $can_follow ? 5 : 0,

             'header'     => array_merge($headers, ['Referer: '.$this->currentUrl()]),
             'user_agent' => $this->product_title,

             'ignore_errors' => $fail_on_error,
             'timeout'       => $max_stream_secs,

             'content' => $body,
            ],
        ];
        if (!($stream_context = stream_context_create($stream_options)) || !($stream = fopen($url, 'rb', false, $stream_context))) {
            $response_code = 404; // Connection failure.
            $response_body = ''; // Connection failure; empty.
            goto finale; // All done here, jump to finale.
        }
        $response_body    = trim((string) stream_get_contents($stream));
        $stream_meta_data = stream_get_meta_data($stream);

        if (!empty($stream_meta_data['timed_out'])) {
            // Based on `$max_stream_secs`.
            $response_code = 408; // Request timeout.
            $response_body = ''; // Connection timed out; ignore.
        } elseif (!empty($stream_meta_data['wrapper_data']) && is_array($stream_meta_data['wrapper_data'])) {
            foreach (array_reverse($stream_meta_data['wrapper_data']) as $_response_header /* Looking for the last one. */) {
                if (is_string($_response_header) && mb_stripos($_response_header, 'HTTP/') === 0 && mb_strpos($_response_header, ' ')) {
                    list(, $response_code) = explode(' ', $_response_header, 3);
                    $response_code         = (int) trim($response_code);
                    break; // Got the last status code.
                }
            }
        }
        if ($benchmark) {
            $this->benchmark->addData(
                __FUNCTION__,
                compact('stream_meta_data')
            );
        }
        fclose($stream); // Close the resource handle now.

        if ($fail_on_error && $response_code >= 400) {
            $response_body = ''; // Fail silently.
        }
        goto finale; // All done here, jump to finale.

        /* ---------------------------------------------------------- */

        finale: // Target point; finale/return value.

        if ($benchmark && !empty($time) && $url) {
            $this->benchmark->addTime(
                __FUNCTION__,
                $time, // Caller, start time, task performed.
                sprintf('fetching remote resource: `%1$s`; `%2$s` bytes received;', $url, strlen($response_body))
            );
        }
        return $return_array ? ['code' => $response_code, 'body' => $response_body] : $response_body;
    }
}
