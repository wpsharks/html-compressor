<?php
/**
 * HTML Compressor (core class file).
 *
 * @since 140417 Initial release.
 * @package websharks\html_compressor
 * @author JasWSInc <https://github.com/JasWSInc>
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 2
 */
namespace websharks\html_compressor
{
	/**
	 * HTML Compressor (core class).
	 *
	 * @package websharks\html_compressor
	 * @author JasWSInc <https://github.com/JasWSInc>
	 *
	 * @property-read string $version Read-only access to version string.
	 * @property-read array  $options Read-only access to current options.
	 */
	class core // Heart of the HTML Compressor.
	{
		/**
		 * Current version string.
		 *
		 * @since 140418 Version indicates release date.
		 *
		 * @var string Dated version string: `YYMMDD`.
		 */
		protected $version = '140520';

		/**
		 * An array of class options.
		 *
		 * @since 140417 Initial release.
		 *
		 * @var array Set dynamically by class constructor.
		 */
		protected $options = array();

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
		protected $default_vendor_css_prefixes = array('moz', 'webkit', 'khtml', 'ms', 'o');

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
		protected $default_css_exclusions = array();

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
		 *    To disable these built-in CSS exclusions pass the option `disable_built_in_css_exclusions` as TRUE.
		 * @note These get converted to a regex pattern by the class constructor. Reference {@link $built_in_regex_css_exclusions}.
		 */
		protected $built_in_regex_css_exclusion_patterns = array('\W#post\-[0-9]+\W');

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
		protected $default_js_exclusions = array('.php?');

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
		 *    To disable these built-in JS exclusions pass the option `disable_built_in_js_exclusions` as TRUE.
		 * @note These get converted to a regex pattern by the class constructor. Reference {@link $built_in_regex_js_exclusions}.
		 */
		protected $built_in_regex_js_exclusion_patterns = array('\.google\-analytics\.com\/', '\Wga\s*\(', '\W_gaq\.push\s*\(');

		/**
		 * Current base HREF value.
		 *
		 * @since 140519 Adding additional benchmarks.
		 *
		 * @var array Filled by the cURL (and related) methods.
		 */
		protected $remote_connection_times = array();

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
		 * Static cache array for this class.
		 *
		 * @since 140417 Initial release.
		 *
		 * @var array Used by various routines for optimization.
		 */
		protected static $cache = array();

		/**
		 * Data cache for this class instance.
		 *
		 * @since 140417 Initial release.
		 *
		 * @var array Used by various routines for optimization.
		 */
		protected $icache = array();

		/**
		 * Class Constructor.
		 *
		 * Full instructions and all `$options` are listed in the
		 *    [README.md](http://github.com/WebSharks/HTML-Compressor) file.
		 *    See: <http://github.com/WebSharks/HTML-Compressor>
		 *
		 * @since 140417 Initial release.
		 * @api Constructor is available for public use.
		 *
		 * @param array $options Optional array of instance options.
		 *    Check the [README.md](http://github.com/WebSharks/HTML-Compressor) file
		 *    for a list of all possible option keys. See: <http://github.com/WebSharks/HTML-Compressor>
		 */
		public function __construct(array $options = array())
		{
			$this->options = $options; // Instance options.

			# Cache Expiration Time

			if(!empty($this->options['cache_expiration_time']))
				$this->cache_expiration_time = (string)$this->options['cache_expiration_time'];

			# Vendor-Specific CSS Prefixes

			if(isset($this->options['vendor_css_prefixes']) && is_array($this->options['vendor_css_prefixes']))
				$this->regex_vendor_css_prefixes = implode('|', $this->preg_quote_deep($this->options['vendor_css_prefixes'], '/'));
			else $this->regex_vendor_css_prefixes = implode('|', $this->preg_quote_deep($this->default_vendor_css_prefixes, '/'));

			# CSS Exclusions (If Applicable)

			if(isset($this->options['regex_css_exclusions']) && is_string($this->options['regex_css_exclusions']))
				$this->regex_css_exclusions = $this->options['regex_css_exclusions'];

			else if(isset($this->options['css_exclusions']) && is_array($this->options['css_exclusions']))
				$this->regex_css_exclusions = ($this->options['css_exclusions']) // Contains exclusions?
					? '/'.implode('|', $this->preg_quote_deep($this->options['css_exclusions'], '/')).'/i'
					: ''; // No exclusions; i.e. pass an empty array to indicate none.

			else if($this->default_css_exclusions) // Else we will use the default set of exclusions.
				$this->regex_css_exclusions = '/'.implode('|', $this->preg_quote_deep($this->default_css_exclusions, '/')).'/i';

			if($this->built_in_regex_css_exclusion_patterns && empty($this->options['disable_built_in_css_exclusions']))
				$this->built_in_regex_css_exclusions = '/'.implode('|', $this->built_in_regex_css_exclusion_patterns).'/i';

			# JavaScript Exclusions (If Applicable)

			if(isset($this->options['regex_js_exclusions']) && is_string($this->options['regex_js_exclusions']))
				$this->regex_js_exclusions = $this->options['regex_js_exclusions'];

			else if(isset($this->options['js_exclusions']) && is_array($this->options['js_exclusions']))
				$this->regex_js_exclusions = ($this->options['js_exclusions']) // Contains exclusions?
					? '/'.implode('|', $this->preg_quote_deep($this->options['js_exclusions'], '/')).'/i'
					: ''; // No exclusions; i.e. pass an empty array to indicate none.

			else if($this->default_js_exclusions) // Else we will use the default set of exclusions.
				$this->regex_js_exclusions = '/'.implode('|', $this->preg_quote_deep($this->default_js_exclusions, '/')).'/i';

			if($this->built_in_regex_js_exclusion_patterns && empty($this->options['disable_built_in_js_exclusions']))
				$this->built_in_regex_js_exclusions = '/'.implode('|', $this->built_in_regex_js_exclusion_patterns).'/i';

			# Require Additional Libs; i.e. Dependencies

			require_once dirname(__FILE__).'/externals/js-minifier.php';
		}

		/**
		 * Handles compression. The heart of this class.
		 *
		 * Full instructions and all `$options` are listed in the
		 *    [README.md](http://github.com/WebSharks/HTML-Compressor) file.
		 *    See: <http://github.com/WebSharks/HTML-Compressor>
		 *
		 * @since 140417 Initial release.
		 * @api This method is available for public use.
		 *
		 * @param string $input The input passed into this routine.
		 *
		 * @return string Compressed HTML code (if at all possible). Note that `$input` must be HTML code.
		 *    i.e. It must contain a closing `</html>` tag; otherwise no compression will occur.
		 */
		public function compress($input)
		{
			if(!($input = trim((string)$input)))
				return $input; // Nothing to do.

			if(stripos($input, '</html>') === FALSE)
				return $input; // Not an HTML doc.

			$benchmark = !empty($this->options['benchmark']);
			if($benchmark) $time = microtime(TRUE);

			$html = & $input; // Let's call this HTML now.
			$html = $this->maybe_compress_combine_head_body_css($html);
			$html = $this->maybe_compress_combine_head_js($html);
			$html = $this->maybe_compress_combine_footer_js($html);
			$html = $this->maybe_compress_inline_js_code($html);
			$html = $this->maybe_compress_html_code($html);

			if($benchmark && !empty($time))
			{
				$product_title = 'HTML Compressor';
				if(!empty($this->options['product_title']))
					$product_title = (string)$this->options['product_title'];
				$time = number_format(microtime(TRUE) - $time, 5, '.', '');

				foreach($this->remote_connection_times as $_remote_connection)
					$html .= "\n\n".'<!-- '.sprintf('%1$s took %2$s seconds fetching `%3$s`. -->', htmlspecialchars($product_title), htmlspecialchars($_remote_connection['time']), htmlspecialchars($_remote_connection['url']));
				$html .= "\n\n".'<!-- '.sprintf('%1$s took %2$s seconds (total). -->', htmlspecialchars($product_title), htmlspecialchars($time));
			}
			if(!isset($this->options['cleanup_cache_dirs']) || $this->options['cleanup_cache_dirs'])
				if(mt_rand(1, 20) === 1) $this->cleanup_cache_dirs();

			return $html;
		}

		/**
		 * Magic method for access to read-only properties.
		 *
		 * @since 140418 Initial release.
		 *
		 * @param string $property Propery by name.
		 *
		 * @return mixed Property value.
		 * @internal For internal magic use only.
		 *
		 * @throws \exception If `$property` does not exist for any reason.
		 */
		public function __get($property)
		{
			$property = (string)$property;

			if(property_exists($this, $property))
				return $this->{$property};

			throw new \exception(sprintf('Undefined property: `%1$s`.', $property));
		}

		/**
		 * Handles possible compression of head/body CSS.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string $html Input HTML code.
		 *
		 * @return string HTML code, after possible CSS compression.
		 */
		protected function maybe_compress_combine_head_body_css($html)
		{
			if(!($html = (string)$html))
				return $html; // Nothing to do.

			if(isset($this->options['compress_combine_head_body_css']))
				if(!$this->options['compress_combine_head_body_css'])
					return $html; // Nothing to do here.

			if(($html_frag = $this->get_html_frag($html)) && ($head_frag = $this->get_head_frag($html)))
				if(($css_tag_frags = $this->get_css_tag_frags($html_frag)) && ($css_parts = $this->compile_css_tag_frags_into_parts($css_tag_frags)))
				{
					$css_tag_frags_all_compiled = $this->compile_key_elements_deep($css_tag_frags, 'all');
					$html                       = $this->replace_once($head_frag['all'], '%%htmlc-head%%', $html);
					$html                       = $this->replace_once($css_tag_frags_all_compiled, '', $html);
					$cleaned_head_contents      = $this->replace_once($css_tag_frags_all_compiled, '', $head_frag['contents']);
					$cleaned_head_contents      = $this->cleanup_self_closing_html_tag_lines($cleaned_head_contents);

					$compressed_css_tags = array(); // Initialize.

					foreach($css_parts as $_css_part)
					{
						if(isset($_css_part['exclude_frag'], $css_tag_frags[$_css_part['exclude_frag']]['all']))
							$compressed_css_tags[] = $css_tag_frags[$_css_part['exclude_frag']]['all'];
						else $compressed_css_tags[] = $_css_part['tag'];
					}
					unset($_css_part); // Housekeeping.

					$compressed_css_tags   = implode("\n", $compressed_css_tags);
					$compressed_head_parts = array($head_frag['open_tag'], $cleaned_head_contents, $compressed_css_tags, $head_frag['closing_tag']);
					$html                  = $this->replace_once('%%htmlc-head%%', implode("\n", $compressed_head_parts), $html);
				}
			return ($html) ? trim($html) : $html; // With possible compression having been applied here.
		}

		/**
		 * Handles possible compression of head JS.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string $html Input HTML code.
		 *
		 * @return string HTML code, after possible JS compression.
		 */
		protected function maybe_compress_combine_head_js($html)
		{
			if(!($html = (string)$html))
				return $html; // Nothing to do.

			if(isset($this->options['compress_combine_head_js']))
				if(!$this->options['compress_combine_head_js'])
					return $html; // Nothing to do here.

			if(($head_frag = $this->get_head_frag($html)) /* No need to get the HTML frag here; we're operating on the `<head>` only. */)
				if(($js_tag_frags = $this->get_js_tag_frags($head_frag)) && ($js_parts = $this->compile_js_tag_frags_into_parts($js_tag_frags)))
				{
					$js_tag_frags_all_compiled = $this->compile_key_elements_deep($js_tag_frags, 'all');
					$html                      = $this->replace_once($head_frag['all'], '%%htmlc-head%%', $html);
					$cleaned_head_contents     = $this->replace_once($js_tag_frags_all_compiled, '', $head_frag['contents']);
					$cleaned_head_contents     = $this->cleanup_self_closing_html_tag_lines($cleaned_head_contents);

					$compressed_js_tags = array(); // Initialize.

					foreach($js_parts as $_js_part)
					{
						if(isset($_js_part['exclude_frag'], $js_tag_frags[$_js_part['exclude_frag']]['all']))
							$compressed_js_tags[] = $js_tag_frags[$_js_part['exclude_frag']]['all'];
						else $compressed_js_tags[] = $_js_part['tag'];
					}
					unset($_js_part); // Housekeeping.

					$compressed_js_tags    = implode("\n", $compressed_js_tags);
					$compressed_head_parts = array($head_frag['open_tag'], $cleaned_head_contents, $compressed_js_tags, $head_frag['closing_tag']);
					$html                  = $this->replace_once('%%htmlc-head%%', implode("\n", $compressed_head_parts), $html);
				}
			return ($html) ? trim($html) : $html; // With possible compression having been applied here.
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
		protected function maybe_compress_combine_footer_js($html)
		{
			if(!($html = (string)$html))
				return $html; // Nothing to do.

			if(isset($this->options['compress_combine_footer_js']))
				if(!$this->options['compress_combine_footer_js'])
					return $html; // Nothing to do here.

			if(($footer_scripts_frag = $this->get_footer_scripts_frag($html)) /* e.g. <!-- footer-scripts --><!-- footer-scripts --> */)
				if(($js_tag_frags = $this->get_js_tag_frags($footer_scripts_frag)) && ($js_parts = $this->compile_js_tag_frags_into_parts($js_tag_frags)))
				{
					$js_tag_frags_all_compiled = $this->compile_key_elements_deep($js_tag_frags, 'all');
					$html                      = $this->replace_once($footer_scripts_frag['all'], '%%htmlc-footer-scripts%%', $html);
					$cleaned_footer_scripts    = $this->replace_once($js_tag_frags_all_compiled, '', $footer_scripts_frag['contents']);

					$compressed_js_tags = array(); // Initialize.

					foreach($js_parts as $_js_part)
					{
						if(isset($_js_part['exclude_frag'], $js_tag_frags[$_js_part['exclude_frag']]['all']))
							$compressed_js_tags[] = $js_tag_frags[$_js_part['exclude_frag']]['all'];
						else $compressed_js_tags[] = $_js_part['tag'];
					}
					unset($_js_part); // Housekeeping.

					$compressed_js_tags             = implode("\n", $compressed_js_tags);
					$compressed_footer_script_parts = array($footer_scripts_frag['open_tag'], $cleaned_footer_scripts, $compressed_js_tags, $footer_scripts_frag['closing_tag']);
					$html                           = $this->replace_once('%%htmlc-footer-scripts%%', implode("\n", $compressed_footer_script_parts), $html);
				}
			return ($html) ? trim($html) : $html; // With possible compression having been applied here.
		}

		/**
		 * Compiles CSS tag fragments into CSS parts with compression.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param array $css_tag_frags CSS tag fragments.
		 *
		 * @return array Array of CSS parts, else an empty array on failure.
		 *
		 * @throws \exception If unable to cache CSS parts.
		 */
		protected function compile_css_tag_frags_into_parts(array $css_tag_frags)
		{
			if(!$css_tag_frags)
				return array(); // Nothing to do.

			$checksum             = $this->get_tag_frags_checksum($css_tag_frags);
			$public_cache_dir     = $this->cache_dir($this::dir_public_type, $checksum);
			$private_cache_dir    = $this->cache_dir($this::dir_private_type, $checksum);
			$public_cache_dir_url = $this->cache_dir_url($this::dir_public_type, $checksum);

			$cache_parts_file      = $checksum.'-compressor-parts.css-cache';
			$cache_parts_file_path = $private_cache_dir.'/'.$cache_parts_file;

			$cache_part_file      = '%%code-checksum%%-compressor-part.css';
			$cache_part_file_path = $public_cache_dir.'/'.$cache_part_file;
			$cache_part_file_url  = $public_cache_dir_url.'/'.$cache_part_file;

			if(is_file($cache_parts_file_path) && filemtime($cache_parts_file_path) > strtotime('-'.$this->cache_expiration_time))
				if(is_array($cached_parts = unserialize(file_get_contents($cache_parts_file_path))))
					return $cached_parts;

			$css_part                 = 0; // Initialize.
			$css_parts                = array(); // Initialize.
			$_last_css_tag_frag_media = 'all'; // Initialize.

			foreach($css_tag_frags as $_css_tag_frag_pos => $_css_tag_frag)
			{
				if($_css_tag_frag['exclude'])
				{
					if($_css_tag_frag['link_href'] || $_css_tag_frag['style_css'])
					{
						if($css_parts) $css_part++; // Starts new part.

						$css_parts[$css_part]['tag']          = '';
						$css_parts[$css_part]['exclude_frag'] = $_css_tag_frag_pos;

						$css_part++; // Always indicates a new part in the next iteration.
					}
				}
				else if($_css_tag_frag['link_href'])
				{
					if(($_css_tag_frag['link_href'] = $this->resolve_relative_url($_css_tag_frag['link_href'])))
						if(($_css_code = $this->curl($_css_tag_frag['link_href'])))
						{
							$_css_code = $this->resolve_css_relatives($_css_code, $_css_tag_frag['link_href']);
							$_css_code = $this->resolve_resolved_css_imports($_css_code, $_css_tag_frag['media']);

							if($_css_code) // Now, DO we have something here?
							{
								if($_css_tag_frag['media'] !== $_last_css_tag_frag_media)
									$css_part++; // Starts new part; different `@media` spec here.

								else if(!empty($css_parts[$css_part]['code']) && stripos($css_parts[$css_part]['code'], '@import') !== FALSE)
									$css_part++; // Starts new part; existing code contains an @import.

								$css_parts[$css_part]['media'] = $_css_tag_frag['media'];

								if(!empty($css_parts[$css_part]['code']))
									$css_parts[$css_part]['code'] .= "\n\n".$_css_code;
								else $css_parts[$css_part]['code'] = $_css_code;
							}
						}
				}
				else if($_css_tag_frag['style_css'])
				{
					$_css_code = $_css_tag_frag['style_css'];
					$_css_code = $this->resolve_css_relatives($_css_code);
					$_css_code = $this->resolve_resolved_css_imports($_css_code, $_css_tag_frag['media']);

					if($_css_code) // Now, DO we have something here?
					{
						if($_css_tag_frag['media'] !== $_last_css_tag_frag_media)
							$css_part++; // Starts new part; different `@media` spec here.

						else if(!empty($css_parts[$css_part]['code']) && stripos($css_parts[$css_part]['code'], '@import') !== FALSE)
							$css_part++; // Starts new part; existing code contains an @import.

						$css_parts[$css_part]['media'] = $_css_tag_frag['media'];

						if(!empty($css_parts[$css_part]['code']))
							$css_parts[$css_part]['code'] .= "\n\n".$_css_code;
						else $css_parts[$css_part]['code'] = $_css_code;
					}
				}
				$_last_css_tag_frag_media = $_css_tag_frag['media'];
			}
			unset($_last_css_tag_frag_media, $_css_tag_frag_pos, $_css_tag_frag, $_css_code);

			foreach(array_keys($css_parts = array_values($css_parts)) as $css_part)
			{
				if(!empty($css_parts[$css_part]['code']))
				{
					$_css_media = 'all';
					if(!empty($css_parts[$css_part]['media']))
						$_css_media = $css_parts[$css_part]['media'];

					$_css_code    = $css_parts[$css_part]['code'];
					$_css_code    = $this->move_special_css_at_rules_to_top($_css_code);
					$_css_code    = $this->strip_prepend_css_charset_utf8($_css_code);
					$_css_code_cs = md5($_css_code); // Do this before compression.
					$_css_code    = $this->maybe_compress_css_code($_css_code);

					$_css_code_path = str_replace('%%code-checksum%%', $_css_code_cs, $cache_part_file_path);
					$_css_code_url  = str_replace('%%code-checksum%%', $_css_code_cs, $cache_part_file_url);

					if(!file_put_contents($_css_code_path, $_css_code)) // Cache compressed CSS code.
						throw new \exception(sprintf('Unable to cache CSS code file: `%1$s`.', $_css_code_path));

					$css_parts[$css_part]['tag'] = '<link type="text/css" rel="stylesheet" href="'.htmlspecialchars($_css_code_url, ENT_QUOTES).'" media="'.htmlspecialchars($_css_media, ENT_QUOTES).'" />';

					unset($css_parts[$css_part]['code']); // Ditch this; no need to cache this code too.
				}
			}
			unset($_css_media, $_css_code, $_css_code_cs, $_css_code_path, $_css_code_url);

			if(!file_put_contents($cache_parts_file_path, serialize($css_parts)))
				throw new \exception(sprintf('Unable to cache CSS parts into: `%1$s`.', $cache_parts_file_path));

			return $css_parts;
		}

		/**
		 * Compiles JS tag fragments into JS parts with compression.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param array $js_tag_frags JS tag fragments.
		 *
		 * @return array Array of JS parts, else an empty array on failure.
		 *
		 * @throws \exception If unable to cache JS parts.
		 */
		protected function compile_js_tag_frags_into_parts(array $js_tag_frags)
		{
			if(!$js_tag_frags)
				return array(); // Nothing to do.

			$checksum             = $this->get_tag_frags_checksum($js_tag_frags);
			$public_cache_dir     = $this->cache_dir($this::dir_public_type, $checksum);
			$private_cache_dir    = $this->cache_dir($this::dir_private_type, $checksum);
			$public_cache_dir_url = $this->cache_dir_url($this::dir_public_type, $checksum);

			$cache_parts_file      = $checksum.'-compressor-parts.js-cache';
			$cache_parts_file_path = $private_cache_dir.'/'.$cache_parts_file;

			$cache_part_file      = '%%code-checksum%%-compressor-part.js';
			$cache_part_file_path = $public_cache_dir.'/'.$cache_part_file;
			$cache_part_file_url  = $public_cache_dir_url.'/'.$cache_part_file;

			if(is_file($cache_parts_file_path) && filemtime($cache_parts_file_path) > strtotime('-'.$this->cache_expiration_time))
				if(is_array($cached_parts = unserialize(file_get_contents($cache_parts_file_path))))
					return $cached_parts;

			$js_part  = 0; // Initialize.
			$js_parts = array(); // Initialize.

			foreach($js_tag_frags as $_js_tag_frag_pos => $_js_tag_frag)
			{
				if($_js_tag_frag['exclude'])
				{
					if($_js_tag_frag['script_src'] || $_js_tag_frag['script_js'])
					{
						if($js_parts) $js_part++; // Starts new part.

						$js_parts[$js_part]['tag']          = '';
						$js_parts[$js_part]['exclude_frag'] = $_js_tag_frag_pos;

						$js_part++; // Always indicates a new part in the next iteration.
					}
				}
				else if($_js_tag_frag['script_src'])
				{
					if(($_js_tag_frag['script_src'] = $this->resolve_relative_url($_js_tag_frag['script_src'])))
						if(($_js_code = $this->curl($_js_tag_frag['script_src'])))
						{
							$_js_code = rtrim($_js_code, ';').';';

							if($_js_code) // Now, DO we have something here?
							{
								if(!empty($js_parts[$js_part]['code']))
									$js_parts[$js_part]['code'] .= "\n\n".$_js_code;
								else $js_parts[$js_part]['code'] = $_js_code;
							}
						}
				}
				else if($_js_tag_frag['script_js'])
				{
					$_js_code = $_js_tag_frag['script_js'];
					$_js_code = rtrim($_js_code, ';').';';

					if($_js_code) // Now, DO we have something here?
					{
						if(!empty($js_parts[$js_part]['code']))
							$js_parts[$js_part]['code'] .= "\n\n".$_js_code;
						else $js_parts[$js_part]['code'] = $_js_code;
					}
				}
			}
			unset($_js_tag_frag_pos, $_js_tag_frag, $_js_code);

			foreach(array_keys($js_parts = array_values($js_parts)) as $js_part)
			{
				if(!empty($js_parts[$js_part]['code']))
				{
					$_js_code    = $js_parts[$js_part]['code'];
					$_js_code_cs = md5($_js_code); // Before compression.
					$_js_code    = $this->maybe_compress_js_code($_js_code);

					$_js_code_path = str_replace('%%code-checksum%%', $_js_code_cs, $cache_part_file_path);
					$_js_code_url  = str_replace('%%code-checksum%%', $_js_code_cs, $cache_part_file_url);

					if(!file_put_contents($_js_code_path, $_js_code))
						throw new \exception(sprintf('Unable to cache JS code file: `%1$s`.', $_js_code_path));

					$js_parts[$js_part]['tag'] = '<script type="text/javascript" src="'.htmlspecialchars($_js_code_url, ENT_QUOTES).'"></script>';

					unset($js_parts[$js_part]['code']); // Ditch this; no need to cache this code too.
				}
			}
			unset($_js_code, $_js_code_cs, $_js_code_path, $_js_code_url);

			if(!file_put_contents($cache_parts_file_path, serialize($js_parts)))
				throw new \exception(sprintf('Unable to cache JS parts into: `%1$s`.', $cache_parts_file_path));

			return $js_parts;
		}

		/**
		 * Parses and returns an array of CSS tag fragments.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param array $html_frag An HTML tag fragment array.
		 *
		 * @return array An array of CSS tag fragments (ready to be converted into CSS parts).
		 *    Else an empty array (i.e. no CSS tag fragments in the HTML fragment array).
		 */
		protected function get_css_tag_frags(array $html_frag)
		{
			if(!$html_frag)
				return array(); // Nothing to do.

			$regex = '/(?P<all>'. // Entire match.
			         '(?P<if_open_tag>\<\!\-\-\[if\s*[^\]]*?\]\>\s*)?'.
			         '(?:(?P<link_self_closing_tag>\<link(?:\s+[^\>]*?)?\>)'.
			         '|(?P<style_open_tag>\<style(?:\s+[^\>]*?)?\>)(?P<style_css>.*?)(?P<style_closing_tag>\<\/style\>))'.
			         '(?P<if_closing_tag>\s*\<\!\[endif\]\-\-\>)?'.
			         ')/is'; // Dot matches line breaks.

			if(!empty($html_frag['contents']) && preg_match_all($regex, $html_frag['contents'], $_tag_frags, PREG_SET_ORDER))
			{
				foreach($_tag_frags as $_tag_frag)
				{
					$_link_href = $_style_css = $_media = 'all';

					if(($_link_href = $this->get_link_css_href($_tag_frag)))
						$_media = $this->get_link_css_media($_tag_frag);

					else if(($_style_css = $this->get_style_css($_tag_frag)))
						$_media = $this->get_style_css_media($_tag_frag);

					if($_link_href || $_style_css) // One or the other is fine.
					{
						$css_tag_frags[] = array(
							'all'                   => $_tag_frag['all'],

							'if_open_tag'           => isset($_tag_frag['if_open_tag']) ? $_tag_frag['if_open_tag'] : '',
							'if_closing_tag'        => isset($_tag_frag['if_closing_tag']) ? $_tag_frag['if_closing_tag'] : '',

							'link_self_closing_tag' => isset($_tag_frag['link_self_closing_tag']) ? $_tag_frag['link_self_closing_tag'] : '',
							'link_href_external'    => ($_link_href) ? $this->is_url_external($_link_href) : FALSE,
							'link_href'             => $_link_href, // This could also be empty.

							'style_open_tag'        => isset($_tag_frag['style_open_tag']) ? $_tag_frag['style_open_tag'] : '',
							'style_css'             => $_style_css, // This could also be empty.
							'style_closing_tag'     => isset($_tag_frag['style_closing_tag']) ? $_tag_frag['style_closing_tag'] : '',

							'media'                 => ($_media) ? $_media : 'all', // Defaults to `all`.

							'exclude'               => FALSE // Default value.
						);
						$_tag_frag_r     = & $css_tag_frags[count($css_tag_frags) - 1];

						if($_tag_frag_r['if_open_tag'] || $_tag_frag_r['if_closing_tag'])
							$_tag_frag_r['exclude'] = TRUE;

						else if($_tag_frag_r['link_href'] && $_tag_frag_r['link_href_external'] && isset($this->options['compress_combine_remote_css_js']) && !$this->options['compress_combine_remote_css_js'])
							$_tag_frag_r['exclude'] = TRUE;

						else if($this->regex_css_exclusions && preg_match($this->regex_css_exclusions, $_tag_frag_r['link_href'].$_tag_frag_r['style_css']))
							$_tag_frag_r['exclude'] = TRUE;

						else if($this->built_in_regex_js_exclusions && preg_match($this->built_in_regex_js_exclusions, $_tag_frag_r['link_href'].$_tag_frag_r['style_css']))
							$_tag_frag_r['exclude'] = TRUE;
					}
				}
			}
			unset($_tag_frags, $_tag_frag, $_tag_frag_r, $_link_href, $_style_css, $_media);

			return (!empty($css_tag_frags)) ? $css_tag_frags : array();
		}

		/**
		 * Parses and return an array of JS tag fragments.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param array $html_frag An HTML tag fragment array.
		 *
		 * @return array An array of JS tag fragments (ready to be converted into JS parts).
		 *    Else an empty array (i.e. no JS tag fragments in the HTML fragment array).
		 */
		protected function get_js_tag_frags(array $html_frag)
		{
			if(!$html_frag)
				return array(); // Nothing to do.

			$regex = '/(?P<all>'. // Entire match.
			         '(?P<if_open_tag>\<\!\-\-\[if\s*[^\]]*?\]\>\s*)?'.
			         '(?P<script_open_tag>\<script(?:\s+[^\>]*?)?\>)(?P<script_js>.*?)(?P<script_closing_tag>\<\/script\>)'.
			         '(?P<if_closing_tag>\s*\<\!\[endif\]\-\-\>)?'.
			         ')/is'; // Dot matches line breaks.

			if(!empty($html_frag['contents']) && preg_match_all($regex, $html_frag['contents'], $_tag_frags, PREG_SET_ORDER))
			{
				foreach($_tag_frags as $_tag_frag)
				{
					$_script_src = $_script_js = $_script_async = '';

					if(($_script_src = $this->get_script_js_src($_tag_frag)) || ($_script_js = $this->get_script_js($_tag_frag)))
						$_script_async = $this->get_script_js_async($_tag_frag);

					if($_script_src || $_script_js) // One or the other is fine.
					{
						$js_tag_frags[] = array(
							'all'                 => $_tag_frag['all'],

							'if_open_tag'         => isset($_tag_frag['if_open_tag']) ? $_tag_frag['if_open_tag'] : '',
							'if_closing_tag'      => isset($_tag_frag['if_closing_tag']) ? $_tag_frag['if_closing_tag'] : '',

							'script_open_tag'     => isset($_tag_frag['script_open_tag']) ? $_tag_frag['script_open_tag'] : '',
							'script_src_external' => ($_script_src) ? $this->is_url_external($_script_src) : FALSE,
							'script_src'          => $_script_src, // This could also be empty.
							'script_js'           => $_script_js, // This could also be empty.
							'script_async'        => $_script_async, // This could also be empty.
							'script_closing_tag'  => isset($_tag_frag['script_closing_tag']) ? $_tag_frag['script_closing_tag'] : '',

							'exclude'             => FALSE // Default value.
						);
						$_tag_frag_r    = & $js_tag_frags[count($js_tag_frags) - 1];

						if($_tag_frag_r['if_open_tag'] || $_tag_frag_r['if_closing_tag'] || $_tag_frag_r['script_async'])
							$_tag_frag_r['exclude'] = TRUE;

						else if($_tag_frag_r['script_src'] && $_tag_frag_r['script_src_external'] && isset($this->options['compress_combine_remote_css_js']) && !$this->options['compress_combine_remote_css_js'])
							$_tag_frag_r['exclude'] = TRUE;

						else if($this->regex_js_exclusions && preg_match($this->regex_js_exclusions, $_tag_frag_r['script_src'].$_tag_frag_r['script_js']))
							$_tag_frag_r['exclude'] = TRUE;

						else if($this->built_in_regex_js_exclusions && preg_match($this->built_in_regex_js_exclusions, $_tag_frag_r['script_src'].$_tag_frag_r['script_js']))
							$_tag_frag_r['exclude'] = TRUE;
					}
				}
			}
			unset($_tag_frags, $_tag_frag, $_tag_frag_r, $_script_src, $_script_js, $_script_async);

			return (!empty($js_tag_frags)) ? $js_tag_frags : array();
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
		protected function get_tag_frags_checksum(array $tag_frags)
		{
			foreach($tag_frags as &$_frag) // Exclude exclusions.
				$_frag = ($_frag['exclude']) ? array('exclude' => TRUE) : $_frag;
			unset($_frag); // A little housekeeping.

			return md5(serialize($tag_frags));
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
		protected function strip_existing_css_charsets($css)
		{
			if(!($css = (string)$css))
				return $css; // Nothing to do.

			$css = preg_replace('/@(?:\-(?:'.$this->regex_vendor_css_prefixes.')\-)?charset(?:\s+[^;]*?)?;/i', '', $css);
			if($css) $css = trim($css);

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
		protected function strip_prepend_css_charset_utf8($css)
		{
			if(!($css = (string)$css))
				return $css; // Nothing to do.

			$css = $this->strip_existing_css_charsets($css);
			if($css) $css = '@charset "UTF-8";'."\n".$css;

			return $css;
		}

		/**
		 * Moves special CSS `@rules` to the top.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string  $css CSS code.
		 * @param integer $___recursion Internal use only.
		 *
		 * @return string CSS code after having moved special `@rules` to the top.
		 *
		 * @see <https://developer.mozilla.org/en-US/docs/Web/CSS/@charset>
		 * @see <http://stackoverflow.com/questions/11746581/nesting-media-rules-in-css>
		 */
		protected function move_special_css_at_rules_to_top($css, $___recursion = 0)
		{
			if(!($css = (string)$css))
				return $css; // Nothing to do.

			$max_recursions = 2; // `preg_match_all()` calls.
			if($___recursion >= $max_recursions)
				return $css; // All done here.

			if(stripos($css, 'charset') === FALSE && stripos($css, 'import') === FALSE)
				return $css; // Save some time. Nothing to do here.

			if(preg_match_all('/(?P<rule>@(?:\-(?:'.$this->regex_vendor_css_prefixes.')\-)?charset(?:\s+[^;]*?)?;)/i', $css, $rules, PREG_SET_ORDER)
			   || preg_match_all('/(?P<rule>@(?:\-(?:'.$this->regex_vendor_css_prefixes.')\-)?import(?:\s+[^;]*?)?;)/i', $css, $rules, PREG_SET_ORDER)
			) // Searched in a specific order. Recursion dictates a precise order based on what we find in these regex patterns.
			{
				$top_rules = array(); // Initialize.

				foreach($rules as $_rule)
					$top_rules[] = $_rule['rule'];
				unset($_rule); // Housekeeping.

				$css = $this->replace_once($top_rules, '', $css);
				$css = $this->move_special_css_at_rules_to_top($css, $___recursion + 1);
				$css = implode("\n\n", $top_rules)."\n\n".$css;
			}
			return $css;
		}

		/**
		 * Resolves `@import` rules in CSS code recursively.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string  $css CSS code.
		 * @param string  $media Current media specification.
		 * @param boolean $___recursion Internal use only.
		 *
		 * @return string CSS code after all `@import` rules have been resolved recursively.
		 */
		protected function resolve_resolved_css_imports($css, $media, $___recursion = FALSE)
		{
			if(!($css = (string)$css))
				return $css; // Nothing to do.

			$media = $this->current_css_media = (string)$media;
			if(!$media) $media = $this->current_css_media = 'all';

			$import_media_without_url_regex = '/@(?:\-(?:'.$this->regex_vendor_css_prefixes.')\-)?import\s*(["\'])(?P<url>.+?)\\1(?P<media>[^;]*?);/i';
			$import_media_with_url_regex    = '/@(?:\-(?:'.$this->regex_vendor_css_prefixes.')\-)?import\s+url\s*\(\s*(["\']?)(?P<url>.+?)\\1\s*\)(?P<media>[^;]*?);/i';

			$css = preg_replace_callback($import_media_without_url_regex, array($this, '_resolve_resolved_css_imports_cb'), $css);
			$css = preg_replace_callback($import_media_with_url_regex, array($this, '_resolve_resolved_css_imports_cb'), $css);

			if(preg_match_all($import_media_without_url_regex, $css, $_m))
				foreach($_m['media'] as $_media) if(!$_media || $_media === $this->current_css_media)
					return $this->resolve_resolved_css_imports($css, $this->current_css_media, TRUE); // Recursive.
			unset($_m, $_media); // Housekeeping.

			if(preg_match_all($import_media_with_url_regex, $css, $_m))
				foreach($_m['media'] as $_media) if(!$_media || $_media === $this->current_css_media)
					return $this->resolve_resolved_css_imports($css, $this->current_css_media, TRUE); // Recursive.
			unset($_m, $_media); // Housekeeping.

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
		protected function _resolve_resolved_css_imports_cb(array $m)
		{
			if(empty($m['url']))
				return ''; // Nothing to resolve.

			if(!empty($m['media']) && $m['media'] !== $this->current_css_media)
				return $m[0]; // Not possible; different media.

			if(($css = $this->curl($m['url'])))
				$css = $this->resolve_css_relatives($css, $m['url']);

			return $css;
		}

		/**
		 * Resolve relative URLs in CSS code.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string $css CSS code.
		 * @param string $base Optional. Base URL to calculate from.
		 *    Defaults to the current HTTP location for the browser.
		 *
		 * @return string CSS code after having all URLs resolved.
		 */
		protected function resolve_css_relatives($css, $base = '')
		{
			if(!($css = (string)$css))
				return $css; // Nothing to do.

			$this->current_base = $base; // Make this available to callback handlers (possible empty string here).

			$import_without_url_regex = '/(?P<import>@(?:\-(?:'.$this->regex_vendor_css_prefixes.')\-)?import\s*)(?P<open_encap>["\'])(?P<url>.+?)(?P<close_encap>\\2)/i';
			$any_url_regex            = '/(?P<url_>url\s*)(?P<open_bracket>\(\s*)(?P<open_encap>["\']?)(?P<url>.+?)(?P<close_encap>\\3)(?P<close_bracket>\s*\))/i';

			$css = preg_replace_callback($import_without_url_regex, array($this, '_resolve_css_relatives_import_cb'), $css);
			$css = preg_replace_callback($any_url_regex, array($this, '_resolve_css_relatives_url_cb'), $css);

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
		protected function _resolve_css_relatives_import_cb(array $m)
		{
			return $m['import'].$m['open_encap'].$this->resolve_relative_url($m['url'], $this->current_base).$m['close_encap'];
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
		protected function _resolve_css_relatives_url_cb(array $m)
		{
			if(stripos($m['url'], 'data:') === 0)
				return $m[0]; // Don't resolve `data:` URIs.

			return $m['url_'].$m['open_bracket'].$m['open_encap'].$this->resolve_relative_url($m['url'], $this->current_base).$m['close_encap'].$m['close_bracket'];
		}

		/**
		 * Get a CSS link href value from a tag fragment.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param array $tag_frag A CSS tag fragment.
		 *
		 * @return string The link href value if possible; else an empty string.
		 */
		protected function get_link_css_href(array $tag_frag)
		{
			if(!empty($tag_frag['link_self_closing_tag']) && preg_match('/type\s*\=\s*(["\'])text\/css\\1|rel\s*=\s*(["\'])stylesheet\\2/i', $tag_frag['link_self_closing_tag']))
				if(preg_match('/\s+href\s*\=\s*(["\'])(?P<value>.+?)\\1/i', $tag_frag['link_self_closing_tag'], $href) && ($link_css_href = trim($this->n_url_amps($href['value']))))
					return $link_css_href;

			return '';
		}

		/**
		 * Get a CSS link media rule from a tag fragment.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param array $tag_frag A CSS tag fragment.
		 *
		 * @return string The link media value if possible; else a default value of `all`.
		 */
		protected function get_link_css_media(array $tag_frag)
		{
			if(!empty($tag_frag['link_self_closing_tag']) && preg_match('/type\s*\=\s*(["\'])text\/css\\1|rel\s*=\s*(["\'])stylesheet\\2/i', $tag_frag['link_self_closing_tag']))
				if(preg_match('/\s+media\s*\=\s*(["\'])(?P<value>.+?)\\1/i', $tag_frag['link_self_closing_tag'], $media) && ($link_css_media = trim($media['value'])))
					return $link_css_media;

			return 'all';
		}

		/**
		 * Get a CSS style media rule from a tag fragment.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param array $tag_frag A CSS tag fragment.
		 *
		 * @return string The style media value if possible; else a default value of `all`.
		 */
		protected function get_style_css_media(array $tag_frag)
		{
			if(!empty($tag_frag['style_open_tag']) && !empty($tag_frag['style_closing_tag']) && preg_match('/\<style\s*\>|type\s*\=\s*(["\'])text\/css\\1/i', $tag_frag['style_open_tag']))
				if(preg_match('/\s+media\s*\=\s*(["\'])(?P<value>.+?)\\1/i', $tag_frag['style_open_tag'], $media) && ($style_css_media = trim($media['value'])))
					return $style_css_media;

			return 'all';
		}

		/**
		 * Get style CSS from a CSS tag fragment.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param array $tag_frag A CSS tag fragment.
		 *
		 * @return string The style CSS code (if possible); else an empty string.
		 */
		protected function get_style_css(array $tag_frag)
		{
			if(!empty($tag_frag['style_open_tag']) && !empty($tag_frag['style_closing_tag']) && preg_match('/\<style\s*\>|type\s*\=\s*(["\'])text\/css\\1/i', $tag_frag['style_open_tag']))
				if(!empty($tag_frag['style_css']) && ($style_css = trim($tag_frag['style_css'])))
					return $style_css;

			return '';
		}

		/**
		 * Get script JS src value from a JS tag fragment.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param array $tag_frag A JS tag fragment.
		 *
		 * @return string The script JS src value (if possible); else an empty string.
		 */
		protected function get_script_js_src(array $tag_frag)
		{
			if(!empty($tag_frag['script_open_tag']) && !empty($tag_frag['script_closing_tag']) && preg_match('/\<script\s*\>|type\s*\=\s*(["\'])(?:text\/javascript|application\/(?:x\-)?javascript)\\1|language\s*\=\s*(["\'])javascript\\2/i', $tag_frag['script_open_tag']))
				if(preg_match('/\s+src\s*\=\s*(["\'])(?P<value>.+?)\\1/i', $tag_frag['script_open_tag'], $src) && ($script_js_src = trim($this->n_url_amps($src['value']))))
					return $script_js_src;

			return '';
		}

		/**
		 * Get script JS async|defer value from a JS tag fragment.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param array $tag_frag A JS tag fragment.
		 *
		 * @return string The script JS async|defer value (if possible); else an empty string.
		 */
		protected function get_script_js_async(array $tag_frag)
		{
			if(!empty($tag_frag['script_open_tag']) && !empty($tag_frag['script_closing_tag']) && preg_match('/\<script\s*\>|type\s*\=\s*(["\'])(?:text\/javascript|application\/(?:x\-)?javascript)\\1|language\s*\=\s*(["\'])javascript\\2/i', $tag_frag['script_open_tag']))
				if(preg_match('/\s+(?:async|defer)(?:\>|\s*\=\s*(["\'])(?P<value>[^"\']*?)\\1|\s+)?/i', $tag_frag['script_open_tag'], $async) && (empty($async['value']) || in_array(strtolower($async), array('1', 'on', 'yes', 'true', 'defer', 'async'), TRUE)) && ($script_js_async = 'async'))
					return $script_js_async;

			return '';
		}

		/**
		 * Get script JS from a JS tag fragment.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param array $tag_frag A JS tag fragment.
		 *
		 * @return string The script JS code (if possible); else an empty string.
		 */
		protected function get_script_js(array $tag_frag)
		{
			if(!empty($tag_frag['script_open_tag']) && !empty($tag_frag['script_closing_tag']) && preg_match('/\<script\s*\>|type\s*\=\s*(["\'])(?:text\/javascript|application\/(?:x\-)?javascript)\\1|language\s*\=\s*(["\'])javascript\\2/i', $tag_frag['script_open_tag']))
				if(!empty($tag_frag['script_js']) && ($script_js = trim($tag_frag['script_js'])))
					return $script_js;

			return '';
		}

		/**
		 * Build an HTML fragment from HTML source code.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string $html Raw HTML code.
		 *
		 * @return array An HTML fragment (if possible); else an empty array.
		 */
		protected function get_html_frag($html)
		{
			if(!($html = (string)$html))
				return array(); // Nothing to do.

			if(preg_match('/(?P<all>(?P<open_tag>\<html(?:\s+[^\>]*?)?\>)(?P<contents>.*?)(?P<closing_tag>\<\/html\>))/is', $html, $html_frag))
				return $this->remove_numeric_keys_deep($html_frag);

			return array();
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
		protected function get_head_frag($html)
		{
			if(!($html = (string)$html))
				return array(); // Nothing to do.

			if(preg_match('/(?P<all>(?P<open_tag>\<head(?:\s+[^\>]*?)?\>)(?P<contents>.*?)(?P<closing_tag>\<\/head\>))/is', $html, $head_frag))
				return $this->remove_numeric_keys_deep($head_frag);

			return array();
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
		protected function get_footer_scripts_frag($html)
		{
			if(!($html = (string)$html))
				return array(); // Nothing to do.

			if(preg_match('/(?P<all>(?P<open_tag>\<\!\-\-\s*footer[\s_\-]+scripts\s*\-\-\>)(?P<contents>.*?)(?P<closing_tag>(?P=open_tag)))/is', $html, $head_frag))
				return $this->remove_numeric_keys_deep($head_frag);

			return array();
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
		protected function cleanup_self_closing_html_tag_lines($html)
		{
			if(!($html = (string)$html))
				return $html; // Nothing to do.

			return trim(preg_replace('/\>\s*?'."[\r\n]+".'\s*\</', ">\n<", $html));
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
		protected function maybe_compress_html_code($html)
		{
			if(!($html = (string)$html))
				return $html; // Nothing to do.

			if(isset($this->options['compress_html_code']))
				if(!$this->options['compress_html_code'])
					return $html; // Nothing to do here.

			if(($compressed_html = $this->compress_html($html)))
				return $compressed_html;

			return $html;
		}

		/**
		 * Compresses HTML markup (as quickly as possible).
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string $html Any HTML markup (no empty strings please).
		 *
		 * @return string Compressed HTML markup. With all comments and extra whitespace removed as quickly as possible.
		 *    This preserves portions of HTML that depend on whitespace. Like `pre/code/script/style/textarea` tags.
		 *    It also preserves conditional comments and JavaScript `on(click|blur|etc)` attributes.
		 */
		protected function compress_html($html)
		{
			if(!($html = (string)$html))
				return $html; // Nothing to do.

			$static =& static::$cache[__FUNCTION__];

			if(!isset($static['preservations'], $static['compressions'], $static['compress_with']))
			{
				$static['preservations'] = array(
					'special_tags'            => '\<(pre|code|script|style|textarea)(?:\s+[^\>]*?)?\>.*?\<\/\\2>',
					'ie_conditional_comments' => '\<\!\-\-\[if\s*[^\]]*\]\>.*?\<\!\[endif\]\-\-\>',
					'special_attributes'      => '\s(?:style|on[a-z]+)\s*\=\s*(["\']).*?\\3'
				);
				$static['preservations'] = // Implode for regex capture.
					'/(?P<preservation>'.implode('|', $static['preservations']).')/is';

				$static['compressions']['remove_html_comments']  = '/\<\!\-\-.*?\-\-\>/s';
				$static['compress_with']['remove_html_comments'] = '';

				$static['compressions']['remove_extra_whitespace']  = '/\s+/';
				$static['compress_with']['remove_extra_whitespace'] = ' ';

				$static['compressions']['remove_extra_whitespace_in_self_closing_tags']  = '/\s+\/\>/';
				$static['compress_with']['remove_extra_whitespace_in_self_closing_tags'] = '/>';
			}
			if(preg_match_all($static['preservations'], $html, $preservation_matches, PREG_SET_ORDER))
			{
				foreach($preservation_matches as $_preservation_match_key => $_preservation_match)
				{
					$preservations[]             = $_preservation_match['preservation'];
					$preservation_placeholders[] = '%%minify-html-'.$_preservation_match_key.'%%';
				}
				unset($_preservation_match_key, $_preservation_match);

				if(isset($preservations, $preservation_placeholders)) // Preservations?
					$html = $this->replace_once($preservations, $preservation_placeholders, $html);
			}
			$html = preg_replace($static['compressions'], $static['compress_with'], $html);

			if(isset($preservations, $preservation_placeholders)) // Restore?
				$html = $this->replace_once($preservation_placeholders, $preservations, $html);

			return ($html) ? trim($html) : $html;
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
		protected function maybe_compress_css_code($css)
		{
			if(!($css = (string)$css))
				return $css; // Nothing to do.

			if(isset($this->options['compress_css_code']))
				if(!$this->options['compress_css_code'])
					return $css; // Nothing to do here.

			$regex = '/(?:[a-z0-9]+\:)?\/\/'.preg_quote($this->current_url_host(), '/').'\//i';
			$css   = preg_replace($regex, '/', $css); // To absolute paths.

			if(($compressed_css = $this->compress_css($css)))
				return $compressed_css;

			return $css;
		}

		/**
		 * Compresses CSS code (as quickly as possible).
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string $css Any CSS code (excluding `<style></style>` tags please).
		 *
		 * @return string Compressed CSS code. This removes CSS comments, extra whitespace, and it compresses HEX color codes whenever possible.
		 *    In addition, this will also remove any unnecessary `;` line terminators to further optimize the overall file size.
		 */
		protected function compress_css($css)
		{
			if(!($css = (string)$css))
				return $css; // Nothing to do.

			$static =& static::$cache[__FUNCTION__];

			if(!isset($static['replace'], $static['with'], $static['colors']))
			{
				$static['replace'] = array('[', ']', '{', '}', '!=', '|=', '^=', '$=', '*=', '~=', '=', '+', '~', ':', ';', ',', '>');
				$static['replace'] = implode('|', $this->preg_quote_deep($static['replace'], '/'));

				$static['replace'] = array('comments'        => '/\/\*.*?\*\//s',
				                           'line_breaks'     => "/[\r\n\t]+/",
				                           'extra_spaces'    => '/ +/',
				                           'de_spacifiables' => '/ *('.$static['replace'].') */',
				                           'unnecessary_;s'  => '/;\}/'
				);
				$static['with']    = array('', '', ' ', '$1', '}');
				$static['colors']  = '/(?P<context>[:,\h]+#)(?P<hex>[a-z0-9]{6})/i';
			}
			$css = preg_replace($static['replace'], $static['with'], $css);
			$css = preg_replace_callback($static['colors'], array($this, '_maybe_compress_css_color'), $css);

			return trim($css);
		}

		/**
		 * Compresses HEX color codes.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param array $m Regular expression matches.
		 *
		 * @return string Full match with compressed HEX color code.
		 */
		protected function _maybe_compress_css_color(array $m)
		{
			$m['hex'] = strtoupper($m['hex']); // Convert to uppercase for easy comparison.

			if($m['hex'][0] === $m['hex'][1] && $m['hex'][2] === $m['hex'][3] && $m['hex'][4] === $m['hex'][5])
				return $m['context'].$m['hex'][0].$m['hex'][2].$m['hex'][4];

			return $m[0];
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
		protected function maybe_compress_js_code($js)
		{
			if(!($js = (string)$js))
				return $js; // Nothing to do.

			if(isset($this->options['compress_js_code']))
				if(!$this->options['compress_js_code'])
					return $js; // Nothing to do here.

			if(($compressed_js = js_minifier::compress($js)))
				return $compressed_js;

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
		protected function maybe_compress_inline_js_code($html)
		{
			if(!($html = (string)$html))
				return $html; // Nothing to do.

			if(isset($this->options['compress_js_code']))
				if(!$this->options['compress_js_code'])
					return $html; // Nothing to do here.

			if(isset($this->options['compress_inline_js_code']))
				if(!$this->options['compress_inline_js_code'])
					return $html; // Nothing to do here.

			if(($_html_frag = $this->get_html_frag($html)) && ($_js_tag_frags = $this->get_js_tag_frags($_html_frag, TRUE)))
			{
				foreach($_js_tag_frags as $_js_tag_frag_key => $_js_tag_frag) // Loop through each JS tag fragment.
					if($_js_tag_frag['script_js']) // Remove inline JS code temporarily (we'll re-insert after compression).
					{
						$js_tag_frags_script_js_parts[]                             = $_js_tag_frag['all'];
						$js_tag_frags_script_js_part_placeholders[]                 = '%%htmlc-'.$_js_tag_frag_key.'%%';
						$js_tag_frags_script_js_part_placeholder_key_replacements[] = $_js_tag_frag_key;
					}
				if(isset($js_tag_frags_script_js_parts, $js_tag_frags_script_js_part_placeholders, $js_tag_frags_script_js_part_placeholder_key_replacements))
				{
					$html = $this->replace_once($js_tag_frags_script_js_parts, $js_tag_frags_script_js_part_placeholders, $html);

					foreach($js_tag_frags_script_js_part_placeholder_key_replacements as &$_js_tag_frag_key_replacement)
					{
						$_js_tag_frag = $_js_tag_frags[$_js_tag_frag_key_replacement];

						$_js_tag_frag_key_replacement = $_js_tag_frag['if_open_tag'];
						$_js_tag_frag_key_replacement .= $_js_tag_frag['script_open_tag'];
						$_js_tag_frag_key_replacement .= $this->_compress_inline_js_code($_js_tag_frag['script_js']);
						$_js_tag_frag_key_replacement .= $_js_tag_frag['script_closing_tag'];
						$_js_tag_frag_key_replacement .= $_js_tag_frag['if_closing_tag'];
					}
					unset($_js_tag_frag_key_replacement); // Housekeeping.

					$html = $this->replace_once($js_tag_frags_script_js_part_placeholders, $js_tag_frags_script_js_part_placeholder_key_replacements, $html);
				}
			}
			unset($_html_frag, $_js_tag_frags, $_js_tag_frag_key, $_js_tag_frag); // Housekeeping.

			return ($html) ? trim($html) : $html; // After possible inline JS compression.
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
		protected function _compress_inline_js_code($js)
		{
			if(!($js = (string)$js))
				return $js; // Nothing to do.

			if(($compressed_js = js_minifier::compress($js)))
				return '/*<![CDATA[*/'.$compressed_js.'/*]]>*/';

			return $js;
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
		 * @param array                $array An input array to search in.
		 *
		 * @param string|integer|array $keys An array of `key` elements to compile.
		 *    In other words, elements with one of these array keys, are what we're looking for.
		 *    A string|integer is also accepted here (if only one key), and it's converted internally to an array.
		 *
		 * @param boolean              $preserve_keys Optional. Defaults to a FALSE value.
		 *    If this is TRUE, the return array WILL preserve numeric/associative keys, instead of forcing a numerically indexed array.
		 *    This ALSO prevents duplicates in the return array, which may NOT be desirable in certain circumstances.
		 *    Particularly when/if searching a multidimensional array (where keys could be found in multiple dimensions).
		 *    In fact, in some cases, this could return data you did NOT want/expect, so please be cautious.
		 *
		 * @param integer              $search_dimensions The number of dimensions to search. Defaults to `-1` (infinite).
		 *    If `$preserve_keys` is TRUE, consider setting this to a value of `1`.
		 *
		 * @param integer              $___current_dimension For internal use only; used in recursion.
		 *
		 * @return array The array of compiled key elements, else an empty array, if no key elements were found.
		 *    By default, the return array will be indexed numerically (e.g. keys are NOT preserved here).
		 *    If an associative array is preferred, please set `$preserve_keys` to a TRUE value,
		 *       and please consider setting `$search_dimensions` to `1`.
		 */
		protected function compile_key_elements_deep(array $array, $keys, $preserve_keys = FALSE, $search_dimensions = -1, $___current_dimension = 1)
		{
			if($___current_dimension === 1) // Force valid types.
			{
				$keys              = (array)$keys;
				$search_dimensions = (integer)$search_dimensions;
			}
			$key_elements = array(); // Initialize.

			foreach($array as $_key => $_value)
			{
				if(in_array($_key, $keys, TRUE))
					if($preserve_keys) $key_elements[$_key] = $_value;
					else $key_elements[] = $_value;

				if(($search_dimensions < 1 || $___current_dimension < $search_dimensions) && is_array($_value)
				   && ($_key_elements = $this->compile_key_elements_deep($_value, $keys, $preserve_keys, $search_dimensions, $___current_dimension + 1))
				) $key_elements = array_merge($key_elements, $_key_elements);
			}
			unset($_key, $_value, $_key_elements);

			return $key_elements;
		}

		/**
		 * Removes all numeric array keys (deeply).
		 *
		 * @since 140417 Initial release.
		 *
		 * @note This is a recursive scan running deeply into multiple dimensions of arrays.
		 *
		 * @param array   $array An input array.
		 *
		 * @param boolean $___recursion Internal use only.
		 *
		 * @return array Output array with only non-numeric keys (deeply).
		 */
		protected function remove_numeric_keys_deep(array $array, $___recursion = FALSE)
		{
			foreach($array as $_key => &$_value)
			{
				if(is_numeric($_key))
					unset($array[$_key]);

				else if(is_array($_value))
					$_value = $this->remove_numeric_keys_deep($_value, TRUE);
			}
			unset($_key, $_value);

			return $array;
		}

		/********************************************************************************************************/

		/*
		 * String Utilities
		 */

		/**
		 * Escapes regex special chars deeply (i.e. `preg_quote()` deeply).
		 *
		 * @since 140417 Initial release.
		 *
		 * @note This is a recursive scan running deeply into multiple dimensions of arrays/objects.
		 * @note This routine will usually NOT include private, protected or static properties of an object class.
		 *    However, private/protected properties *will* be included, if the current scope allows access to these private/protected properties.
		 *    Static properties are NEVER considered by this routine, because static properties are NOT iterated by `foreach()`.
		 *
		 * @param mixed   $value Any value can be converted into a quoted string.
		 *    Actually, objects can't, but this recurses into objects.
		 *
		 * @param string  $delimiter Same as PHP's `preg_quote()`.
		 *
		 * @param boolean $___recursion Internal use only.
		 *
		 * @return string|array|object Escaped string, array, object.
		 */
		protected function preg_quote_deep($value, $delimiter = '', $___recursion = FALSE)
		{
			if(is_array($value) || is_object($value))
			{
				foreach($value as &$_value)
					$_value = $this->preg_quote_deep($_value, $delimiter, TRUE);
				unset($_value); // Housekeeping.

				return $value;
			}
			return preg_quote((string)$value, (string)$delimiter);
		}

		/**
		 * String replace (ONE time), and deeply into arrays/objects.
		 *
		 * @since 140417 Initial release.
		 *
		 * @note This is a recursive scan running deeply into multiple dimensions of arrays/objects.
		 * @note This routine will usually NOT include private, protected or static properties of an object class.
		 *    However, private/protected properties *will* be included, if the current scope allows access to these private/protected properties.
		 *    Static properties are NEVER considered by this routine, because static properties are NOT iterated by `foreach()`.
		 *
		 * @param string|array $needle String, or an array of strings, to search for.
		 *
		 * @param string|array $replace String, or an array of strings, to use as replacements.
		 *
		 * @param mixed        $value Any value can be converted into a string to run replacements on.
		 *    Actually, objects can't, but this recurses into objects.
		 *
		 * @param boolean      $case_insensitive Case insensitive? Defaults to FALSE.
		 *    If TRUE, the search is NOT case sensitive.
		 *
		 * @param boolean      $___recursion Internal use only.
		 *
		 * @return mixed Values after ONE string replacement (deeply).
		 *    Any values that were NOT strings|arrays|objects, will be converted to strings by this routine.
		 *
		 * @see http://stackoverflow.com/questions/8177296/when-to-use-strtr-vs-str-replace
		 */
		protected function replace_once_deep($needle, $replace, $value, $case_insensitive = FALSE, $___recursion = FALSE)
		{
			if(is_array($value) || is_object($value))
			{
				foreach($value as &$_value) // Recursion.
					$_value = $this->replace_once_deep($needle, $replace, $_value, $case_insensitive, TRUE);
				unset($_value); // Housekeeping.

				return $value; // Array or object.
			}
			$value = (string)$value; // Force string value.

			if($case_insensitive) // Case insensitive scenario?
				$strpos = 'stripos'; // Use `stripos()`.
			else $strpos = 'strpos'; // Default.

			if(is_array($needle)) // Array of needles?
			{
				if(is_array($replace)) // Optimized for `$replace` array.
				{
					foreach($needle as $_key => $_needle)
						if(($_strpos = $strpos($value, ($_needle = (string)$_needle))) !== FALSE)
						{
							$_length  = strlen($_needle);
							$_replace = (isset($replace[$_key])) ? (string)$replace[$_key] : '';
							$value    = substr_replace($value, $_replace, $_strpos, $_length);
						}
					unset($_key, $_needle, $_strpos, $_length, $_replace);

					return $value; // String value.
				}
				else // Optimized for `$replace` string.
				{
					$replace = (string)$replace;

					foreach($needle as $_needle)
						if(($_strpos = $strpos($value, ($_needle = (string)$_needle))) !== FALSE)
						{
							$_length = strlen($_needle);
							$value   = substr_replace($value, $replace, $_strpos, $_length);
						}
					unset($_needle, $_strpos, $_length);

					return $value; // String value.
				}
			}
			else // Otherwise, just a simple case here.
			{
				$needle = (string)$needle;

				if(($_strpos = $strpos($value, $needle)) !== FALSE)
				{
					$_length = strlen($needle);

					if(is_array($replace)) // Use 1st element, else empty string.
						$_replace = (isset($replace[0])) ? (string)$replace[0] : '';
					else $_replace = (string)$replace; // Use string value.

					$value = substr_replace($value, $_replace, $_strpos, $_length);
				}
				unset($_strpos, $_length, $_replace);

				return $value; // String value.
			}
		}

		/**
		 * String replace (ONE time).
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string|array $needle String, or an array of strings, to search for.
		 *
		 * @param string|array $replace String, or an array of strings, to use as replacements.
		 *
		 * @param string       $string The subject value to search/replace; i.e. a string.
		 *
		 * @param boolean      $case_insensitive Case insensitive? Defaults to FALSE.
		 *    If TRUE, the search is NOT case sensitive.
		 *
		 * @return string String value after having been searched/replaced.
		 *
		 * @see http://stackoverflow.com/questions/8177296/when-to-use-strtr-vs-str-replace
		 */
		protected function replace_once($needle, $replace, $string, $case_insensitive = FALSE)
		{
			return $this->replace_once_deep($needle, $replace, (string)$string, $case_insensitive);
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
		 * @param mixed   $value Any value can be converted into an escaped string.
		 *    Actually, objects can't, but this recurses into objects.
		 *
		 * @param integer $times Number of escapes. Defaults to `1`.
		 *
		 * @param boolean $___recursion Internal use only.
		 *
		 * @return string|array|object Escaped string, array, object.
		 */
		protected function esc_refs_deep($value, $times = 1, $___recursion = FALSE)
		{
			if(is_array($value) || is_object($value))
			{
				foreach($value as &$_value)
					$_value = $this->esc_refs_deep($_value, $times, TRUE);
				unset($_value);

				return $value;
			}
			$value = (string)$value;
			$times = abs((integer)$times);

			return str_replace(array('\\', '$'), array(str_repeat('\\', $times).'\\', str_repeat('\\', $times).'$'), $value);
		}

		/**
		 * Escapes regex backreference chars (i.e. `\\$` and `\\\\`).
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string  $string A string value.
		 * @param integer $times Number of escapes. Defaults to `1`.
		 *
		 * @return string Escaped string.
		 */
		protected function esc_refs($string, $times = 1)
		{
			return $this->esc_refs_deep((string)$string, $times);
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
		 * @internal This is for internal use only.
		 */
		const dir_public_type = 'public';

		/**
		 * Private directory type.
		 *
		 * @since 140417 Initial release.
		 *
		 * @var string Indicates a private directory type.
		 * @internal This is for internal use only.
		 */
		const dir_private_type = 'private';

		/**
		 * Get (and possibly create) the cache dir.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string  $type One of `$this::dir_public_type` or `$this::dir_private_type`.
		 * @param string  $checksum Optional. If supplied, we'll build a nested sub-directory based on the checksum.
		 * @param boolean $base_only Defaults to a FALSE value. If TRUE, return only the base directory.
		 *    i.e. Do NOT suffix the directory in any way. No host and no checksum.
		 *
		 * @return string Server path to cache dir.
		 *
		 * @throws \exception If unable to create the cache dir.
		 * @throws \exception If cache directory is not readable/writable.
		 */
		protected function cache_dir($type, $checksum = '', $base_only = FALSE)
		{
			if($type !== $this::dir_public_type)
				if($type !== $this::dir_private_type)
					throw new \exception('Invalid type.');
			$checksum = (string)$checksum;

			if(isset($checksum[4]))
				$checksum = substr($checksum, 0, 5);
			else $checksum = ''; // Invalid or empty.

			$cache_key = $type.$checksum.(integer)$base_only;

			if(isset($this->icache[__FUNCTION__.'_'.$cache_key]))
				return $this->icache[__FUNCTION__.'_'.$cache_key];

			if(!empty($this->options[__FUNCTION__.'_'.$type]))
				$basedir = $this->n_dir_seps($this->options[__FUNCTION__.'_'.$type]);

			else if(defined('WP_CONTENT_DIR'))
				$basedir = $this->n_dir_seps(WP_CONTENT_DIR.'/htmlc/cache/'.$type);

			else if(!empty($_SERVER['DOCUMENT_ROOT']))
				$basedir = $this->n_dir_seps($_SERVER['DOCUMENT_ROOT'].'/htmlc/cache/'.$type);

			else throw new \exception(sprintf('Unable to find a good location for the cache directory. Please set option: `%1$s`.', __FUNCTION__.'_'.$type));

			if($base_only) $dir = $basedir; // Caller wants only the base directory.

			else // We add a suffix for the current host; and a possible set of sub-directories based on the checksum.
			{
				$dir = $basedir; // Start with the base directory.
				$dir .= '/'.trim(preg_replace('/[^a-z0-9\-]/i', '-', $this->current_url_host()), '-');
				$dir .= ($checksum) ? '/'.implode('/', str_split($checksum)) : '';
			}
			if(!is_dir($dir) && mkdir($dir, 0775, TRUE) && $type === $this::dir_private_type && !is_file($basedir.'/.htaccess'))
				if(!file_put_contents($basedir.'/.htaccess', $this->dir_htaccess_deny)) // Secure the private directory.
					throw new \exception(sprintf('Unable to create `.htaccess` file in private cache directory: `%1$s`.', $basedir));

			if(!is_readable($dir) || !is_writable($dir)) // Must have this directory; and it MUST be readable/writable.
				throw new \exception(sprintf('Cache directory not readable/writable: `%1$s`. Failed on `%2$s`.', $basedir, $dir));

			return ($this->icache[__FUNCTION__.'_'.$cache_key] = $dir);
		}

		/**
		 * Get (and possibly create) the cache dir URL.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string  $type One of `$this::public_type` or `$this::private_type`.
		 * @param string  $checksum Optional. If supplied, we'll build a nested sub-directory based on the checksum.
		 * @param boolean $base_only Defaults to a FALSE value. If TRUE, return only the base directory.
		 *    i.e. Do NOT suffix the directory in any way. No host and no checksum.
		 *
		 * @return string URL to server-side cache directory.
		 *
		 * @throws \exception If unable to create the cache dir.
		 * @throws \exception If cache directory is not readable/writable.
		 * @throws \exception If unable to determine the URL for any reason.
		 */
		protected function cache_dir_url($type, $checksum = '', $base_only = FALSE)
		{
			if($type !== $this::dir_public_type)
				if($type !== $this::dir_private_type)
					throw new \exception('Invalid type.');
			$checksum = (string)$checksum;

			if(isset($checksum[4]))
				$checksum = substr($checksum, 0, 5);
			else $checksum = ''; // Invalid or empty.

			$cache_key = $type.$checksum.(integer)$base_only;

			if(isset($this->icache[__FUNCTION__.'_'.$cache_key]))
				return $this->icache[__FUNCTION__.'_'.$cache_key];

			$basedir = $this->cache_dir($type, '', TRUE);

			if(!empty($this->options[__FUNCTION__.'_'.$type]))
				$baseurl = $this->set_url_scheme(rtrim($this->options[__FUNCTION__.'_'.$type], '/'));

			else if(defined('WP_CONTENT_DIR') && defined('WP_CONTENT_URL') && $basedir === $this->n_dir_seps(WP_CONTENT_DIR.'/htmlc/cache/'.$type))
				$baseurl = $this->set_url_scheme(rtrim(WP_CONTENT_URL, '/').'/htmlc/cache/'.$type);

			else if(!empty($_SERVER['DOCUMENT_ROOT']) && strpos($basedir, $_SERVER['DOCUMENT_ROOT']) === 0)
			{
				$baseurl = $this->current_url_scheme().'://'.$this->current_url_host();
				$baseurl .= str_replace(rtrim($_SERVER['DOCUMENT_ROOT'], '/'), '', $basedir);
			}
			else throw new \exception(sprintf('Unable to determine URL to cache directory. Please set option: `%1$s`.', __FUNCTION__.'_'.$type));

			if($base_only) $url = $baseurl; // Caller wants only the base directory.

			else // We add a suffix for the current host; and a possible set of sub-directories based on the checksum.
			{
				$url = $baseurl; // Start with the base URL.
				$url .= '/'.trim(preg_replace('/[^a-z0-9\-]/i', '-', $this->current_url_host()), '-');
				$url .= ($checksum) ? '/'.implode('/', str_split($checksum)) : '';
			}
			return ($this->icache[__FUNCTION__.'_'.$cache_key] = $url);
		}

		/**
		 * Cache cleanup routine.
		 *
		 * @since 140417 Initial release.
		 *
		 * @note This routine is always host-specific.
		 *    i.e. We cleanup cache files for the current host only.
		 *
		 * @return null Simply cleans up the cache.
		 */
		protected function cleanup_cache_dirs()
		{
			$public_cache_dir  = $this->cache_dir($this::dir_public_type);
			$private_cache_dir = $this->cache_dir($this::dir_private_type);
			$min_mtime         = strtotime('-'.$this->cache_expiration_time);

			/** @var $_dir_file \RecursiveDirectoryIterator For IDEs. */
			foreach($this->dir_regex_iteration($public_cache_dir, '/\/compressor\-part\..*$/') as $_dir_file)
				if(($_dir_file->isFile() || $_dir_file->isLink()) && $_dir_file->getMTime() < $min_mtime - 3600)
					if($_dir_file->isWritable()) unlink($_dir_file->getPathname());

			/** @var $_dir_file \RecursiveDirectoryIterator For IDEs. */
			foreach($this->dir_regex_iteration($private_cache_dir, '/\/compressor\-parts\..*$/') as $_dir_file)
				if(($_dir_file->isFile() || $_dir_file->isLink()) && $_dir_file->getMTime() < $min_mtime)
					if($_dir_file->isWritable()) unlink($_dir_file->getPathname());

			unset($_dir_file); // Housekeeping.
		}

		/**
		 * Regex directory iterator.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string $dir Path to a directory.
		 * @param string $regex Regular expression.
		 *
		 * @return \RegexIterator
		 */
		protected function dir_regex_iteration($dir, $regex)
		{
			$dir   = (string)$dir;
			$regex = (string)$regex;

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
		 * @param string  $dir_file Directory/file path.
		 *
		 * @param boolean $allow_trailing_slash Defaults to FALSE.
		 *    If TRUE; and `$dir_file` contains a trailing slash; we'll leave it there.
		 *
		 * @return string Normalized directory/file path.
		 */
		protected function n_dir_seps($dir_file, $allow_trailing_slash = FALSE)
		{
			if(($dir_file = (string)$dir_file) === '')
				return $dir_file; // Nothing to do.

			if(strpos($dir_file, '://' !== FALSE)) // Quick check here for optimization.
				if(preg_match('/^(?P<stream_wrapper>[a-z0-9]+)\:\/\//i', $dir_file, $stream_wrapper))
					$dir_file = preg_replace('/^(?P<stream_wrapper>[a-z0-9]+)\:\/\//i', '', $dir_file);

			if(strpos($dir_file, ':' !== FALSE)) // Quick drive letter check here for optimization.
				if(preg_match('/^(?P<drive_letter>[a-z])\:[\/\\\\]/i', $dir_file)) // It has a Windows® drive letter?
					$dir_file = preg_replace_callback('/^(?P<drive_letter>[a-z])\:[\/\\\\]/i', create_function('$m', 'return strtoupper($m[0]);'), $dir_file);

			$dir_file = preg_replace('/\/+/', '/', str_replace(array(DIRECTORY_SEPARATOR, '\\', '/'), '/', $dir_file));
			$dir_file = ($allow_trailing_slash) ? $dir_file : rtrim($dir_file, '/'); // Strip trailing slashes.

			if(!empty($stream_wrapper[0])) // Stream wrapper (force lowercase).
				$dir_file = strtolower($stream_wrapper[0]).$dir_file;

			return $dir_file; // Normalized now.
		}

		/**
		 * Apache `.htaccess` access denial snippet.
		 *
		 * @since 140417 Initial release.
		 *
		 * @var string Compatible with Apache 2.1+. Tested up to 2.4.7.
		 */
		protected $dir_htaccess_deny = "<IfModule authz_core_module>\n\tRequire all denied\n</IfModule>\n<IfModule !authz_core_module>\n\tdeny from all\n</IfModule>";

		/********************************************************************************************************/

		/*
		 * URL Utilities
		 */

		/**
		 * Indicates scheme component in a URL.
		 *
		 * @since 140417 Initial release.
		 *
		 * @var integer Part of a bitmask.
		 * @internal Internal use only.
		 */
		const url_scheme = 1;

		/**
		 * Indicates user component in a URL.
		 *
		 * @since 140417 Initial release.
		 *
		 * @var integer Part of a bitmask.
		 * @internal Internal use only.
		 */
		const url_user = 2;

		/**
		 * Indicates pass component in a URL.
		 *
		 * @since 140417 Initial release.
		 *
		 * @var integer Part of a bitmask.
		 * @internal Internal use only.
		 */
		const url_pass = 4;

		/**
		 * Indicates host component in a URL.
		 *
		 * @since 140417 Initial release.
		 *
		 * @var integer Part of a bitmask.
		 * @internal Internal use only.
		 */
		const url_host = 8;

		/**
		 * Indicates port component in a URL.
		 *
		 * @since 140417 Initial release.
		 *
		 * @var integer Part of a bitmask.
		 * @internal Internal use only.
		 */
		const url_port = 16;

		/**
		 * Indicates path component in a URL.
		 *
		 * @since 140417 Initial release.
		 *
		 * @var integer Part of a bitmask.
		 * @internal Internal use only.
		 */
		const url_path = 32;

		/**
		 * Indicates query component in a URL.
		 *
		 * @since 140417 Initial release.
		 *
		 * @var integer Part of a bitmask.
		 * @internal Internal use only.
		 */
		const url_query = 64;

		/**
		 * Indicates fragment component in a URL.
		 *
		 * @since 140417 Initial release.
		 *
		 * @var integer Part of a bitmask.
		 * @internal Internal use only.
		 */
		const url_fragment = 128;

		/**
		 * Is the current request over SSL?
		 *
		 * @since 140417 Initial release.
		 *
		 * @return boolean TRUE if over SSL; else FALSE.
		 */
		protected function current_url_ssl()
		{
			if(isset(static::$cache[__FUNCTION__]))
				return static::$cache[__FUNCTION__];

			if(!empty($_SERVER['SERVER_PORT']))
				if($_SERVER['SERVER_PORT'] === '443')
					return (static::$cache[__FUNCTION__] = TRUE);

			if(!empty($_SERVER['HTTPS']))
				if($_SERVER['HTTPS'] === '1' || strcasecmp($_SERVER['HTTPS'], 'on') === 0)
					return (static::$cache[__FUNCTION__] = TRUE);

			if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']))
				if(strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0)
					return (static::$cache[__FUNCTION__] = TRUE);

			return (static::$cache[__FUNCTION__] = FALSE);
		}

		/**
		 * Gets the current scheme (via environment variables).
		 *
		 * @since 140417 Initial release.
		 *
		 * @return string The current scheme, else an exception is thrown on failure.
		 *
		 * @throws \exception If unable to determine the current scheme.
		 */
		protected function current_url_scheme()
		{
			if(isset(static::$cache[__FUNCTION__]))
				return static::$cache[__FUNCTION__];

			if(!empty($this->options[__FUNCTION__])) // Defined explicity?
				return (static::$cache[__FUNCTION__] = $this->n_url_scheme($this->options[__FUNCTION__]));

			if(!empty($_SERVER['REQUEST_SCHEME']))
				return (static::$cache[__FUNCTION__] = $this->n_url_scheme($_SERVER['REQUEST_SCHEME']));

			return (static::$cache[__FUNCTION__] = ($this->current_url_ssl()) ? 'https' : 'http');
		}

		/**
		 * Gets the current host name (via environment variables).
		 *
		 * @since 140417 Initial release.
		 *
		 * @return string The current host name, else an exception is thrown on failure.
		 *
		 * @throws \exception If `$_SERVER['HTTP_HOST']` is empty.
		 */
		protected function current_url_host()
		{
			if(isset(static::$cache[__FUNCTION__]))
				return static::$cache[__FUNCTION__];

			if(!empty($this->options[__FUNCTION__])) // Defined explicity?
				return (static::$cache[__FUNCTION__] = $this->n_url_host($this->options[__FUNCTION__]));

			if(empty($_SERVER['HTTP_HOST']))
				throw new \exception('Missing required `$_SERVER[\'HTTP_HOST\']`.');

			return (static::$cache[__FUNCTION__] = $this->n_url_host($_SERVER['HTTP_HOST']));
		}

		/**
		 * Gets the current URI (via environment variables).
		 *
		 * @since 140417 Initial release.
		 *
		 * @return string The current URI, else an exception is thrown on failure.
		 *
		 * @throws \exception If unable to determine the current URI.
		 */
		protected function current_url_uri()
		{
			if(isset(static::$cache[__FUNCTION__]))
				return static::$cache[__FUNCTION__];

			if(!empty($this->options[__FUNCTION__])) // Defined explicity?
				return (static::$cache[__FUNCTION__] = $this->must_parse_uri($this->options[__FUNCTION__]));

			if(empty($_SERVER['REQUEST_URI']))
				throw new \exception('Missing required `$_SERVER[\'REQUEST_URI\']`.');

			return (static::$cache[__FUNCTION__] = $this->must_parse_uri($_SERVER['REQUEST_URI']));
		}

		/**
		 * URL to current request.
		 *
		 * @since 140417 Initial release.
		 *
		 * @return string The current URL.
		 */
		protected function current_url()
		{
			if(isset(static::$cache[__FUNCTION__]))
				return static::$cache[__FUNCTION__];

			$url = $this->current_url_scheme().'://';
			$url .= $this->current_url_host();
			$url .= $this->current_url_uri();

			return (static::$cache[__FUNCTION__] = $url);
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
		protected function n_url_scheme($scheme)
		{
			if(!($scheme = (string)$scheme))
				return $scheme; // Nothing to do.

			if(strpos($scheme, ':') !== FALSE)
				$scheme = strstr($scheme, ':', TRUE);

			return strtolower($scheme);
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
		protected function n_url_host($host)
		{
			if(!($host = (string)$host))
				return $host; // Nothing to do.

			return strtolower($host);
		}

		/**
		 * Converts all ampersand entities in a URL (or a URI/query/fragment only); to just `&`.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string $url_uri_query_fragment A full URL; or a partial URI;
		 *    or only a query string, or only a fragment. Any of these can be normalized here.
		 *
		 * @return string Input URL (or a URI/query/fragment only); after having been normalized by this routine.
		 */
		protected function n_url_amps($url_uri_query_fragment)
		{
			if(!($url_uri_query_fragment = (string)$url_uri_query_fragment))
				return $url_uri_query_fragment; // Nothing to do.

			if(strpos($url_uri_query_fragment, '&') === FALSE)
				return $url_uri_query_fragment; // Nothing to do.

			return preg_replace('/&amp;|&#0*38;|&#[xX]0*26;/', '&', $url_uri_query_fragment);
		}

		/**
		 * Normalizes a URL path from a URL (or a URI/query/fragment only).
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string  $url_uri_query_fragment A full URL; or a partial URI;
		 *    or only a query string, or only a fragment. Any of these can be normalized here.
		 *
		 * @param boolean $allow_trailing_slash Defaults to a FALSE value.
		 *    If TRUE, and `$url_uri_query_fragment` contains a trailing slash; we'll leave it there.
		 *
		 * @return string Normalized URL (or a URI/query/fragment only).
		 */
		protected function n_url_path_seps($url_uri_query_fragment, $allow_trailing_slash = FALSE)
		{
			if(($url_uri_query_fragment = (string)$url_uri_query_fragment) === '')
				return $url_uri_query_fragment; // Nothing to do.

			if(!($parts = $this->parse_url($url_uri_query_fragment, NULL, 0)))
				$parts['path'] = $url_uri_query_fragment;

			if($parts['path'] !== '') // Normalize directory separators.
				$parts['path'] = $this->n_dir_seps($parts['path'], $allow_trailing_slash);

			return $this->unparse_url($parts, 0); // Back together again.
		}

		/**
		 * Sets a particular scheme.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string $url A full URL.
		 *
		 * @param string $scheme Optional. The scheme to use (i.e. `//`, `https`, `http`).
		 *    Use `//` to use a cross-protocol compatible scheme.
		 *    Defaults to the current scheme.
		 *
		 * @return string The full URL w/ `$scheme`.
		 */
		protected function set_url_scheme($url, $scheme = '')
		{
			if(!($url = (string)$url))
				return $url; // Nothing to do.

			$scheme = (string)$scheme;

			if(!$scheme) // Current scheme?
				$scheme = $this->current_url_scheme();

			if($scheme !== '//') $scheme = $this->n_url_scheme($scheme).'://';

			return preg_replace('/^(?:[a-z0-9]+\:)?\/\//i', $this->esc_refs($scheme), $url);
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
		 *    or only a query string, or only a fragment. Any of these can be checked here.
		 *
		 * @return boolean TRUE if external; else FALSE.
		 */
		protected function is_url_external($url_uri_query_fragment)
		{
			if(strpos($url_uri_query_fragment, '//') === FALSE)
				return FALSE; // Relative.

			return (stripos($url_uri_query_fragment, '//'.$this->current_url_host()) === FALSE);
		}

		/**
		 * Parses a URL (or a URI/query/fragment only) into an array.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string       $url_uri_query_fragment A full URL; or a partial URI;
		 *    or only a query string, or only a fragment. Any of these can be parsed here.
		 *
		 * @note A query string or fragment MUST be prefixed with the appropriate delimiters.
		 *    This is bad `name=value` (interpreted as path). This is good `?name=value` (query string).
		 *    This is bad `anchor` (interpreted as path). This is good `#fragment` (fragment).
		 *
		 * @param null|integer $component Same as PHP's `parse_url()` component.
		 *    Defaults to NULL; which defaults to an internal value of `-1` before we pass to PHP's `parse_url()`.
		 *
		 * @param null|integer $normalize A bitmask. Defaults to NULL (indicating a default bitmask).
		 *    Defaults include: {@link self::url_scheme}, {@link self::url_host}, {@link self::url_path}.
		 *    However, we DO allow a trailing slash (even if path is being normalized by this parameter).
		 *
		 * @return array|string|integer|null If a component is requested, returns a string component (or an integer in the case of `PHP_URL_PORT`).
		 *    If a specific component is NOT requested, this returns a full array, of all component values.
		 *    Else, this returns NULL on any type of failure (even if a component was requested).
		 *
		 * @note Arrays returned by this method, will include a value for each component (a bit different from PHP's `parse_url()` function).
		 *    We start with an array of defaults (i.e. all empty strings, and `0` for the port number).
		 *    Components found in the URL are then merged into these default values.
		 *    The array is also sorted by key (e.g. alphabetized).
		 */
		protected function parse_url($url_uri_query_fragment, $component = NULL, $normalize = NULL)
		{
			$url_uri_query_fragment = (string)$url_uri_query_fragment;

			if(!isset($normalize)) // Use defaults?
				$normalize = $this::url_scheme | $this::url_host | $this::url_path;

			if(strpos($url_uri_query_fragment, '//') === 0) // Cross-protocol compatible?
			{
				$url_uri_query_fragment = $this->current_url_scheme().':'.$url_uri_query_fragment; // So URL is parsed properly.
				// Works around a bug in `parse_url()` prior to PHP v5.4.7. See: <http://php.net/manual/en/function.parse-url.php>.
				$x_protocol_scheme = TRUE; // Flag this, so we can remove scheme below.
			}
			else $x_protocol_scheme = FALSE; // No scheme; or scheme is NOT cross-protocol compatible.

			$parsed = @parse_url($url_uri_query_fragment, ((!isset($component)) ? -1 : $component));

			if($x_protocol_scheme) // Cross-protocol scheme?
			{
				if(!isset($component) && is_array($parsed))
					$parsed['scheme'] = ''; // No scheme.

				else if($component === PHP_URL_SCHEME)
					$parsed = ''; // No scheme.
			}
			if($normalize & $this::url_scheme) // Normalize scheme?
			{
				if(!isset($component) && is_array($parsed))
				{
					if(!isset($parsed['scheme']))
						$parsed['scheme'] = ''; // No scheme.
					$parsed['scheme'] = $this->n_url_scheme($parsed['scheme']);
				}
				else if($component === PHP_URL_SCHEME)
				{
					if(!is_string($parsed))
						$parsed = ''; // No scheme.
					$parsed = $this->n_url_scheme($parsed);
				}
			}
			if($normalize & $this::url_host) // Normalize host?
			{
				if(!isset($component) && is_array($parsed))
				{
					if(!isset($parsed['host']))
						$parsed['host'] = ''; // No host.
					$parsed['host'] = $this->n_url_host($parsed['host']);
				}
				else if($component === PHP_URL_HOST)
				{
					if(!is_string($parsed))
						$parsed = ''; // No scheme.
					$parsed = $this->n_url_host($parsed);
				}
			}
			if($normalize & $this::url_path) // Normalize path?
			{
				if(!isset($component) && is_array($parsed))
				{
					if(!isset($parsed['path']))
						$parsed['path'] = '/'; // Home directory.
					$parsed['path'] = $this->n_url_path_seps($parsed['path'], TRUE);
					if(strpos($parsed['path'], '/') !== 0) $parsed['path'] = '/'.$parsed['path'];
				}
				else if($component === PHP_URL_PATH)
				{
					if(!is_string($parsed))
						$parsed = '/'; // Home directory.
					$parsed = $this->n_url_path_seps($parsed, TRUE);
					if(strpos($parsed, '/') !== 0) $parsed = '/'.$parsed;
				}
			}
			if(in_array(gettype($parsed), array('array', 'string', 'integer'), TRUE))
			{
				if(is_array($parsed)) // An array?
				{
					// Standardize.
					$defaults       = array(
						'fragment' => '',
						'host'     => '',
						'pass'     => '',
						'path'     => '',
						'port'     => 0,
						'query'    => '',
						'scheme'   => '',
						'user'     => ''
					);
					$parsed         = array_merge($defaults, $parsed);
					$parsed['port'] = (integer)$parsed['port'];
					ksort($parsed); // Sort by key.
				}
				return $parsed; // A `string|integer|array`.
			}
			return NULL; // Default return value.
		}

		/**
		 * Parses a URL (or a URI/query/fragment only) into an array.
		 *
		 * @since 140417 Initial release.
		 *
		 * @return array|string|integer|null {@inheritdoc}
		 *
		 * @throws \exception If unable to parse.
		 *
		 * @see parse_url()
		 * @inheritdoc parse_url()
		 */
		protected function must_parse_url() // Arguments are NOT listed here.
		{
			if(is_null($parsed = call_user_func_array(array($this, 'parse_url'), func_get_args())))
				throw new \exception(sprintf('Unable to parse: `%1$s`.', (string)func_get_arg(0)));

			return $parsed;
		}

		/**
		 * Unparses a URL (putting it all back together again).
		 *
		 * @since 140417 Initial release.
		 *
		 * @param array        $parsed An array with at least one URL component.
		 *
		 * @param null|integer $normalize A bitmask. Defaults to NULL (indicating a default bitmask).
		 *    Defaults include: {@link self::url_scheme}, {@link self::url_host}, {@link self::url_path}.
		 *    However, we DO allow a trailing slash (even if path is being normalized by this parameter).
		 *
		 * @return string A full or partial URL, based on components provided in the `$parsed` array.
		 *    It IS possible to receive an empty string, when/if `$parsed` does NOT contain any portion of a URL.
		 */
		protected function unparse_url(array $parsed, $normalize = NULL)
		{
			$unparsed = ''; // Initialize string value.

			if(!isset($normalize)) // Use defaults?
				$normalize = $this::url_scheme | $this::url_host | $this::url_path;

			if($normalize & $this::url_scheme)
			{
				if(!isset($parsed['scheme']))
					$parsed['scheme'] = ''; // No scheme.
				$parsed['scheme'] = $this->n_url_scheme($parsed['scheme']);
			}
			if(!empty($parsed['scheme']))
				$unparsed .= $parsed['scheme'].'://';
			else if(isset($parsed['scheme']) && !empty($parsed['host']))
				$unparsed .= '//'; // Cross-protocol compatible.

			if(!empty($parsed['user']))
			{
				$unparsed .= $parsed['user'];
				if(!empty($parsed['pass']))
					$unparsed .= ':'.$parsed['pass'];
				$unparsed .= '@';
			}
			if($normalize & $this::url_host)
			{
				if(!isset($parsed['host']))
					$parsed['host'] = ''; // No host.
				$parsed['host'] = $this->n_url_host($parsed['host']);
			}
			if(!empty($parsed['host']))
				$unparsed .= $parsed['host'];

			if(!empty($parsed['port']))
				$unparsed .= ':'.$parsed['port']; // A `0` value is excluded here.

			if($normalize & $this::url_path) // Normalize path?
			{
				if(!isset($parsed['path']))
					$parsed['path'] = '/'; // Home directory.
				$parsed['path'] = $this->n_url_path_seps($parsed['path'], TRUE);
				if(strpos($parsed['path'], '/') !== 0) $parsed['path'] = '/'.$parsed['path'];
			}
			if(isset($parsed['path']))
				$unparsed .= $parsed['path'];

			if(!empty($parsed['query']))
				$unparsed .= '?'.$parsed['query'];

			if(!empty($parsed['fragment']))
				$unparsed .= '#'.$parsed['fragment'];

			return $unparsed;
		}

		/**
		 * Unparses a URL (putting it all back together again).
		 *
		 * @since 140417 Initial release.
		 *
		 * @return string {@inheritdoc}
		 *
		 * @throws \exception If unable to unparse.
		 *
		 * @see unparse_url()
		 * @inheritdoc unparse_url()
		 */
		protected function must_unparse_url() // Arguments are NOT listed here.
		{
			if(($unparsed = call_user_func_array(array($this, 'unparse_url'), func_get_args())) === '')
				throw new \exception(sprintf('Unable to unparse: `%1$s`.', print_r(func_get_arg(0), TRUE)));

			return $unparsed;
		}

		/**
		 * Parses URI parts from a URL (or a URI/query/fragment only).
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string       $url_uri_query_fragment A full URL; or a partial URI;
		 *    or only a query string, or only a fragment. Any of these can be parsed here.
		 *
		 * @param null|integer $normalize A bitmask. Defaults to NULL (indicating a default bitmask).
		 *    Defaults include: {@link self::url_scheme}, {@link self::url_host}, {@link self::url_path}.
		 *    However, we DO allow a trailing slash (even if path is being normalized by this parameter).
		 *
		 * @return array|null An array with the following components, else NULL on any type of failure.
		 *
		 *    • `path`(string) Possible URI path.
		 *    • `query`(string) A possible query string.
		 *    • `fragment`(string) A possible fragment.
		 */
		protected function parse_uri_parts($url_uri_query_fragment, $normalize = NULL)
		{
			if(($parts = $this->parse_url($url_uri_query_fragment, NULL, $normalize)))
				return array('path' => $parts['path'], 'query' => $parts['query'], 'fragment' => $parts['fragment']);

			return NULL; // Default return value.
		}

		/**
		 * Parses URI parts from a URL (or a URI/query/fragment only).
		 *
		 * @since 140417 Initial release.
		 *
		 * @return array|null {@inheritdoc}
		 *
		 * @throws \exception If unable to parse.
		 *
		 * @see parse_uri_parts()
		 * @inheritdoc parse_uri_parts()
		 */
		protected function must_parse_uri_parts() // Arguments are NOT listed here.
		{
			if(is_null($parts = call_user_func_array(array($this, 'parse_uri_parts'), func_get_args())))
				throw new \exception(sprintf('Unable to parse: `%1$s`.', (string)func_get_arg(0)));

			return $parts;
		}

		/**
		 * Parses a URI from a URL (or a URI/query/fragment only).
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string       $url_uri_query_fragment A full URL; or a partial URI;
		 *    or only a query string, or only a fragment. Any of these can be parsed here.
		 *
		 * @param null|integer $normalize A bitmask. Defaults to NULL (indicating a default bitmask).
		 *    Defaults include: {@link self::url_scheme}, {@link self::url_host}, {@link self::url_path}.
		 *    However, we DO allow a trailing slash (even if path is being normalized by this parameter).
		 *
		 * @param boolean      $include_fragment Defaults to TRUE. Include a possible fragment?
		 *
		 * @return string|null A URI (i.e. a URL path), else NULL on any type of failure.
		 */
		protected function parse_uri($url_uri_query_fragment, $normalize = NULL, $include_fragment = TRUE)
		{
			if(($parts = $this->parse_uri_parts($url_uri_query_fragment, $normalize)))
			{
				if(!$include_fragment)
					unset($parts['fragment']);
				return $this->unparse_url($parts, $normalize);
			}
			return NULL; // Default return value.
		}

		/**
		 * Parses a URI from a URL (or a URI/query/fragment only).
		 *
		 * @since 140417 Initial release.
		 *
		 * @return string|null {@inheritdoc}
		 *
		 * @throws \exception If unable to parse.
		 *
		 * @see parse_uri()
		 * @inheritdoc parse_uri()
		 */
		protected function must_parse_uri() // Arguments are NOT listed here.
		{
			if(is_null($parsed = call_user_func_array(array($this, 'parse_uri'), func_get_args())))
				throw new \exception(sprintf('Unable to parse: `%1$s`.', (string)func_get_arg(0)));

			return $parsed;
		}

		/**
		 * Resolves a relative URL into a full URL from a base.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string $relative_url_uri_query_fragment A full URL; or a partial URI;
		 *    or only a query string, or only a fragment. Any of these can be parsed here.
		 *
		 * @param string $base_url A base URL. Optional. Defaults to current location.
		 *    This defaults to the current URL. See: {@link current_url()}.
		 *
		 * @return string A full URL; else an exception will be thrown.
		 *
		 * @throws \exception If unable to parse `$relative_url_uri_query_fragment`.
		 * @throws \exception If there is no `$base`, and we're unable to detect current location.
		 * @throws \exception If unable to parse `$base` (or if `$base` has no host name).
		 */
		protected function resolve_relative_url($relative_url_uri_query_fragment, $base_url = '')
		{
			$relative_url_uri_query_fragment = (string)$relative_url_uri_query_fragment;
			$base_url                        = (string)$base_url;

			if(!$base_url) // No base URL? The `$base` is optional (defaults to current URL).
				$base_url = $this->current_url(); // Auto-detects current URL/location.

			$relative_parts         = $this->must_parse_url($relative_url_uri_query_fragment, NULL, 0);
			$relative_parts['path'] = $this->n_url_path_seps($relative_parts['path'], TRUE);
			$base_parts             = $parts = $this->must_parse_url($base_url);

			if($relative_parts['host']) // Already resolved?
			{
				if(!$relative_parts['scheme']) // If no scheme, use base scheme.
					$relative_parts['scheme'] = $base_parts['scheme'];
				return $this->must_unparse_url($relative_parts);
			}
			if(!$base_parts['host']) // We MUST have a base host name to resolve.
				throw new \exception(sprintf('Unable to parse (missing base host name): `%1$s`.', $base_url));

			if(strlen($relative_parts['path'])) // It's important that we mimic browser behavior here.
			{
				if(strpos($relative_parts['path'], '/') === 0)
					$parts['path'] = ''; // Reduce to nothing if relative is absolute.
				else $parts['path'] = preg_replace('/\/[^\/]*$/', '', $parts['path']).'/'; // Reduce to nearest `/`.

				// Replace `/./` and `/foo/../` with `/` (resolve relatives).
				for($_i = 1, $parts['path'] = $parts['path'].$relative_parts['path']; $_i > 0;)
					$parts['path'] = preg_replace(array('/\/\.\//', '/\/(?!\.\.)[^\/]+\/\.\.\//'), '/', $parts['path'], -1, $_i);
				unset($_i); // Just a little housekeeping.

				// We can ditch any unresolvable `../` patterns now.
				// For instance, if there were too many `../../../../../` back references.
				$parts['path'] = str_replace('../', '', $parts['path']);

				$parts['query'] = $relative_parts['query']; // Use relative query.
			}
			else if(strlen($relative_parts['query'])) // Only if there is a new query (or path above) in the relative.
				$parts['query'] = $relative_parts['query']; // Relative query string supersedes base.

			$parts['fragment'] = $relative_parts['fragment']; // Always changes.

			return $this->must_unparse_url($parts); // Resolved now.
		}

		/**
		 * cURL for remote HTTP communication.
		 *
		 * @since 140417 Initial release.
		 *
		 * @param string       $url A URL to connect to.
		 * @param string|array $body Optional request body.
		 * @param integer      $max_con_secs Defaults to `20` seconds.
		 * @param integer      $max_stream_secs Defaults to `20` seconds.
		 * @param array        $headers Any additional headers to send with the request.
		 * @param string       $cookie_file If cookies are to be collected, store them here.
		 * @param boolean      $fail_on_error Defaults to a value of TRUE; fail on status >= `400`.
		 * @param boolean      $return_array Defaults to a value of FALSE; response body returned only.
		 *
		 * @return string|array Output data from the HTTP response; excluding headers (e.g. body only).
		 */
		protected function curl($url, $body = '', $max_con_secs = 20, $max_stream_secs = 20, array $headers = array(), $cookie_file = '', $fail_on_error = TRUE, $return_array = FALSE)
		{
			$benchmark = !empty($this->options['benchmark'])
			             && $this->options['benchmark'] === 'details';
			if($benchmark) $time = microtime(TRUE);

			$custom_request_method = '';
			$url                   = (string)$url;
			$max_con_secs          = (integer)$max_con_secs;
			$max_stream_secs       = (integer)$max_stream_secs;
			if(!is_array($headers)) $headers = array();
			$cookie_file = (string)$cookie_file;

			$custom_request_regex = // e.g.`PUT::http://www.example.com/`
				'/^(?P<custom_request_method>(?:GET|POST|PUT|DELETE))\:{2}(?P<url>.+)/i';
			if(preg_match($custom_request_regex, $url, $_url_parts))
			{
				$url                   = $_url_parts['url']; // URL after `::`.
				$custom_request_method = strtoupper($_url_parts['custom_request_method']);
			}
			unset($_url_parts); // Housekeeping.

			if(is_array($body))
				$body = http_build_query($body, '', '&');
			else $body = (string)$body;

			if(!$url) return ''; // Nothing to do here.

			$output    = ''; // Initialize.
			$http_code = 0; // Initialize.

			/* ---------------------------------------------------------- */

			wordpress_transport: // WordPress transport layer.

			/*
			 * This is currently disabled because it runs in the shutdown phase
			 * when integrated with Quick Cache. Meaning, objects may have already been destructed by the time this runs.
			 * Until a good reliable solution is found, this will remain disabled via `0 === 0`.
			 */
			if(0 === 0 || !defined('WPINC') || !class_exists('\\WP_Http') || !did_action('init')
			   || $cookie_file || ($custom_request_method && !in_array($custom_request_method, array('GET', 'POST'), TRUE))
			) goto curl_transport; // WP_Http unavailable; or unable to handle the request method type.

			foreach($headers as $_key => $_value)
				if(!is_string($_key) && strpos($_value, ':') > 0)
				{
					list($_header, $_header_value) = explode(':', $_value, 2);
					$headers[$_header] = trim($_header_value);
					unset($headers[$_key]);
				}
			unset($_key, $_value, $_header, $_header_value);

			if($custom_request_method === 'POST' || ($body && $custom_request_method !== 'GET'))
				$wp_remote_request = wp_remote_post($url, array('headers' => $headers, 'body' => $body, 'timeout' => $max_con_secs));
			else $wp_remote_request = wp_remote_get($url, array('headers' => $headers, 'timeout' => $max_con_secs));

			if(!is_wp_error($wp_remote_request))
			{
				$output    = trim((string)wp_remote_retrieve_body($wp_remote_request));
				$http_code = (integer)wp_remote_retrieve_response_code($wp_remote_request);
				if($fail_on_error && $http_code >= 400)
					$output = ''; // Fail silently.
			}
			goto finale; // All done here, jump to finale.

			/* ---------------------------------------------------------- */

			curl_transport: // cURL transport layer (default hanlder).

			$can_follow = (!ini_get('safe_mode') && !ini_get('open_basedir'));

			$curl_opts = array(
				CURLOPT_URL            => $url,
				CURLOPT_HTTPHEADER     => $headers,
				CURLOPT_CONNECTTIMEOUT => $max_con_secs,
				CURLOPT_TIMEOUT        => $max_stream_secs,

				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_HEADER         => FALSE,

				CURLOPT_FOLLOWLOCATION => $can_follow,
				CURLOPT_MAXREDIRS      => $can_follow ? 5 : 0,

				CURLOPT_ENCODING       => '',
				CURLOPT_VERBOSE        => FALSE,
				CURLOPT_FAILONERROR    => $fail_on_error,
				CURLOPT_SSL_VERIFYPEER => FALSE
			);
			if($body) // Has a request body that we need to send?
			{
				if($custom_request_method) // A custom request method is given?
					$curl_opts += array(CURLOPT_CUSTOMREQUEST => $custom_request_method, CURLOPT_POSTFIELDS => $body);
				else $curl_opts += array(CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $body);
			}
			else if($custom_request_method) $curl_opts += array(CURLOPT_CUSTOMREQUEST => $custom_request_method);

			if($cookie_file) // Support cookies? e.g. we have a cookie jar available?
				$curl_opts += array(CURLOPT_COOKIEJAR => $cookie_file, CURLOPT_COOKIEFILE => $cookie_file);

			$curl = curl_init();
			curl_setopt_array($curl, $curl_opts);
			$output    = trim((string)curl_exec($curl));
			$http_code = (integer)curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);

			goto finale; // All done here, jump to finale.

			/* ---------------------------------------------------------- */

			finale: // Target point; finale/return value.

			if($benchmark && !empty($time))
				$this->remote_connection_times[] = // Benchmark data.
					array('time' => number_format(microtime(TRUE) - $time, 5, '.', ''), 'url' => $url);

			return ($return_array) ? array('code' => $http_code, 'body' => $output) : $output;
		}
	}
}