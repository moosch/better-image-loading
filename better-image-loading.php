<?php
/**
 * BetterImageLoading plugin file
 * @link              http://wp.mooschmedia.com/plugins/better-image-loading/
 * @since             0.0.1
 * @package           BetterImageLoading
 *
 * @wordpress-plugin
 * Plugin Name:       Better Image Loading
 * Plugin URI:        http://wp.mooschmedia.com/plugins/better-image-loading/
 * Description:       Load images better on page paint. No more jank!
 * Version:           0.3.7
 * Author:            Moosch Media
 * Author URI:        http://wp.mooschmedia.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       better-image-loading
 */
/**
 * Acknowledgements:
 *
 * Thanks to Jake Archibald (@jaffathecake) for his excellent article on responsive
 * images (https://jakearchibald.com/2015/anatomy-of-responsive-images/)
 *
 * Thanks to Micah Wood (https://wpscholar.com/blog/get-attachment-id-from-wp-image-url/)
 * for the solution of getting an attachment_id from an image url
 *
 */

/**
 * Notes:
 *
 * Creating a blurred image for each registered image size may seem heavy handed and a waste of space,
 * but it removes the possibility of any 'jank' you may get with veried image sizes before JavaScript loads
 * to fix it.
 */

define( 'BIL_VERSION', '0.3.7' );
define( 'BIL_URL', plugins_url( '', __FILE__ ) );
define( 'BIL_LANG', '__moosch__' );

// Debugging
ini_set( 'display_errors', 'on' );

// Set globals
global $bilOptions, $better_image_loading;

/**
 * Load BIL options class
 *
 * @since 0.3.4
 */
// if( file_exists( BIL_URL.'/src/options.php' ) )
// require_once( BIL_URL.'/src/options.php' );
// require( dirname( __FILE__.'/src/options.php' ) )

/**
 * The core plugin class.
 *
 * This is used to define image sizes and make additions to standart featured image html
 *
 * @since      0.1.0
 * @package    BetterImageLoading
 * @author     Moosch Media <hello@mooschmedia.com>
 */
if( !class_exists('BetterImageLoading') )
{
	class BetterImageLoading
	{

		private $options;
    private $blursize = 15;

		/**
		 * Class construction
		 *
		 * Initialises plugin
		 *
		 * @since 0.1.0
		 * @access private
		 */
		function __construct($_options)
		{
			$this->init_plugin();
      $this->options = $_options;
		}

		/**
		 * Initialise plugin
		 *
		 * @since 0.1.0
		 * @access private
		 */
		function init_plugin()
		{
			// Set up registered image sizes
			$this->set_bil_sizes();

			// Filter post content to apply BIL to any local images
			add_filter( 'the_content', array( $this, 'content_filter' ) );

			// Use various Wordpress image building hooks
			add_filter( 'wp_get_attachment_image_attributes', array( &$this, 'set_new_image_attributes' ), 10, 3 );

			// Enqueue scripts and styles
			add_action( 'wp_enqueue_scripts',  array( &$this, 'enqueue_bil_styles' ) );
			add_action( 'wp_enqueue_scripts',  array( &$this, 'enqueue_bil_scripts' ) );
		}

		/**
		 * Set up all the required blurred image sizes we may need
		 *
		 * @since 0.3.0
		 * @access private
		 */
		function set_bil_sizes()
		{
			// Add theme support
			add_theme_support( 'post-thumbnails' );
			// Legacy support
			add_image_size( 'blurred-thumb', $this->blursize, 9999, false ); // Set blurred image size

			$sizes = get_intermediate_image_sizes();
			foreach( $sizes as $size ){

				// Try not to duplicate
				if( substr($size, -4) != '_bil'
					&& !in_array( "{$size}_bil", $sizes )
					&& $size != 'blurred-thumb' ){

					$w = get_option( "{$size}_size_w" );
					$h = get_option( "{$size}_size_h" );
					$crop = (bool) get_option( "{$size}_crop" );

					if( $w && $w != 0 ){
						$ratio = $h / $w;
						$height = $this->blursize * $ratio;
					} else {
						$height = 99999;
					}

					add_image_size( "{$size}_bil", $this->blursize, ( $crop ? $height : 99999 ), false );

				}

			}

			// WooCommerce support
			if( class_exists('WooCommerce') ){
				$wooSizes = array( 'shop_single', 'shop_catalog', 'shop_thumbnail' );
				foreach( $wooSizes as $_size ){
					$size = wc_get_image_size( $_size );
					if( isset($size['height']) && isset($size['width']) && $size['width'] != 0 && $size['height'] != '' && $size['width'] != '' ){
						$ratio = $size['height'] / $size['width'];
						$height = $this->blursize * $ratio;
					} else {
						$height = 99999;
					}
					add_image_size( "{$_size}_bil", $this->blursize, $height, $size['crop'] );
				}
			}
		}

		/**
		 * Extract an attribute from markup
		 *
		 * For example src from <img src="..." />
		 *
		 * @since 0.3.0
		 * @param string $html 	- the markup used to extract attributes
		 * @param string $att 	- the attribute we are looking to extract from $html
		 * @return string 		- the attribute value if found. Empty string otherwise
		 * @access private
		 */
		function extract_attribute( $html = '', $att = '' )
		{
			switch( $att ){
				case 'class':
					preg_match( '/class="([^"]*)"/i', $html, $match );
					return ( isset($match[1]) ? $match[1] : '');
				break;

				case 'src':
					preg_match( '@src="([^"]+)"@' , $html, $match );
					return ( isset($match[1]) ? $match[1] : '');
				break;

				case 'width':
					preg_match( '/width="([^"]*)"/i', $html, $match ) ;
					return ( isset($match[1]) ? $match[1] : false);
				break;

				case 'height':
					preg_match( '/height="([^"]*)"/i', $html, $match ) ;
					return ( isset($match[1]) ? $match[1] : false);
				break;

				case 'srcset':
					preg_match( '/srcset="([^"]*)"/i', $html, $match );
					return ( isset($match[1]) ? $match[1] : '');
				break;

				case 'sizes':
					preg_match( '/sizes="([^"]*)"/i', $html, $match );
					return ( isset($match[1]) ? $match[1] : false);
				break;

				case 'alt':
					preg_match( '/alt="([^"]*)"/i', $html, $match );
					return ( isset($match[1]) ? $match[1] : false);
				break;

				case 'title':
					preg_match( '/title="([^"]*)"/i', $html, $match );
					return ( isset($match[1]) ? $match[1] : false);
				break;

				default:
					return '';
				break;
			}
		}

		/**
		 * Set new image attributes to initialise JavaScript
		 *
		 * Uses Wordpress wp_get_attachment_image_attributes filter
		 *
		 * @since 0.3.0
		 * @param array $atts 			- an array of attributes set as key => value
		 * @param object $attachment 	- the Wordpress image attachment object
		 * @return array 				- the array of attributes
		 * @access private
		 */
		function set_new_image_attributes( $atts, $attachment, $size )
		{
			if( is_admin() || !isset( $attachment->ID ) )
				return $atts;

			$info = pathinfo($atts['src']);
			$data = wp_get_attachment_metadata( $attachment->ID );

			// Blurred image
			$blur = false;

			if( isset($data['sizes']["{$size}_bil"]) )
				$blur = "{$size}_bil";

			// Fallback
			if( isset($data['sizes']['blurred-thumb']) )
				$blur = 'blurred-thumb';

			// If no blurred image, return atts
			if( !$blur )
				return $atts;

			/*
			$shownsize is used for WooCommerce where product thumbnails are scaled to shop_thumbnail size
			Typical could be $size = 'shop_thumbnail' and $shownsize = 'full'
			*/
			$shownsize = false;

			// Check which size is being called
			foreach( $data['sizes'] as $key => $value ){
				if( $data['sizes'][$key]['file'] === $info['basename'] ){
					$shownsize = $key;
					break;
				}
			}

			// If no $shownsize then use full
			if( !$shownsize ){
				$shownsize = 'full';
				$data['sizes']['full'] = array(
					'file' => $info['basename'],
					'width' => $data['width'],
					'height' => $data['height']
				);
			}

			// Fallback to blur if no blurred setsize matches
			if( !isset($data['sizes']["{$shownsize}_bil"]) )
				$data['sizes']["{$shownsize}_bil"] = $data['sizes'][$blur];

			$atts['data-width'] = $data['sizes'][$shownsize]['width'];
			$atts['data-height'] = $data['sizes'][$shownsize]['height'];

			// If srcset is available
			if( isset($atts['srcset']) ){
				// Temp store srcset
				$_srcset = $atts['srcset'];

				// Remove all blurred images from srcset
				$_srcset = explode(', ', $_srcset);
				$new_srcset = array();
				foreach( $_srcset as $src ){
					// If is not a blurred image, add to new srcset
					if( substr($src, -4) != '_bil'
						&& strpos($src, $data['sizes']['blurred-thumb']['width'].'w') === false )
						$new_srcset[] = $src;
				}

				// Make string from array
				$srcset = implode(', ', $new_srcset);

				// Set new srcset value and remove srcset from atts
				$atts['data-srcset'] = $srcset;
				unset($atts['srcset']);

			}

			$atts['size'] = $size;
			// $atts['shownsize'] = $shownsize;

			// Set data-full to original and src to blurred image
			$atts['data-full'] = $atts['src'];
			$atts['src'] = $info['dirname'].'/'.$data['sizes']["{$shownsize}_bil"]['file'];

			// If everything checks out, add the class to initiate the JavaScript
			if( !isset($atts['class']) )
				$atts['class'] = '';

			$atts['class'] = $atts['class'].' bil-init';

			return $atts;
		}

		/**
		 * Rebuild image to BIL format and attributes from html image match
		 *
		 * @since 0.3.0
		 * @param string $html 					- the markup used to extract attributes
		 * @param int (optional) $attachment 	- Wordpress image attachment ID
		 * @return string 						- the mutated $html to match BIL markup
		 * @access private
		 */
		function rebuild_image( $html = '', $attachment_id = false )
		{
			if( empty($html) )
				return '';

			// Get classes
			$classes = $this->extract_attribute( $html, 'class' );
			$classes = explode(' ', $classes);

			// Check if bil is already in place
			if( in_array('bil-init', $classes) )
				return '';

			// Get selected image size
			$size = 'full'; // Default
			foreach( $classes as $class )
				if( strpos($class, 'size-') !== false )
					$size = str_replace('size-', '', $class);

			// Get the image source
			$src = $this->extract_attribute( $html, 'src' );

			// Attempt to get the image attachment_id
			// if ( !preg_match( '/wp-image-([0-9]+)/i', $html, $class_id ) || !( $attachment_id = absint( $class_id[1] ) ) )
			// 	return '';
			if( !$attachment_id )
				$attachment_id = $this->get_attachment_id( $src );

			// If no attachment ID can be found, we assume there are no cropped sizes so bail
			if( !$attachment_id )
				return '';

			// If image src is not local return the markup
			if( strpos($src, get_site_url()) === false )
				return '';

			// Get set dimensions
			$width = $this->extract_attribute( $html, 'width' );
			$height = $this->extract_attribute( $html, 'height' );
			$srcset = $this->extract_attribute( $html, 'srcset' );
			$sizes = $this->extract_attribute( $html, 'sizes' );
			$alt = $this->extract_attribute( $html, 'alt' );
			$title = $this->extract_attribute( $html, 'title' );

			/*
			If no sizes are in place, just return the image
			TODO: Look into extracting the size from the html or get directly from file
			*/
			if( empty($width) || empty($height) )
				return '';

			$data = wp_get_attachment_metadata($attachment_id);

			if( $srcset ){
				// Remove all blurred images from srcset
				$srcset = explode(', ', $srcset);
				$new_srcset = array();
				foreach( $srcset as $_src ){
					// If is not a blurred image, add to new srcset
					if( substr($_src, -4) != '_bil'
						&& strpos($_src, $data['sizes']['blurred-thumb']['width'].'w') === false )
						$new_srcset[] = $_src;
				}

				// Make string from array
				$srcset = implode(', ', $new_srcset).'"';
			}

			// Add width fallback to sizes (Wordpress doesn't add this...yet)
			if( !empty($sizes) )
				$sizes = substr($sizes, 0, -1) . ', 100vw"';

			$src = wp_get_attachment_image_src($attachment_id, $size);
			// Get url path info to find dir url
			$info = pathinfo($src[0]);

			// Default
			$blurred = $src[0];
			if( isset($data['sizes']['blurred-thumb']) )
				$blurred = $info['dirname'].'/'.$data['sizes']['blurred-thumb']['file'];

			/* Check if a caption is required */
			$caption_id = false;
			if( strpos($html, 'class="has-wp-caption captionid-') !== false ){
				foreach( $classes as $class )
					if( strpos($class, 'captionid-') !== false )
						$caption_id = (int) str_replace('captionid-', '', $class);
			}

			$markup = $html;

			// Add bil-init class
			$classes[] = 'bil-init bil-blurred';
			$classes = implode(' ', $classes);

			// Set data-width
			$markup = str_replace(' width="', ' data-width="'.$src[1].'" width="', $markup);
			// Set data-height
			$markup = str_replace(' height="', ' data-height="'.$src[2].'" height="', $markup);
			// Set srcset
			$markup = preg_replace('@srcset="([^"]+)"@', 'data-srcset="'.$srcset.'"', $markup);
			// Set sizes
			if( !empty($sizes) )
				$markup = preg_replace('@sizes="([^"]+)"@', 'data-sizes="'.$sizes.'"', $markup);
			// Add classes
			$markup = preg_replace('@class="([^"]+)"@', 'class="'.$classes.'"', $markup);
			// Replace src with blurred and add data-full
			$markup = str_replace('src="', 'src="'.$blurred.'" data-full="'.$src[0].'"', $markup);

			return $markup;
		}

		/**
		 * Searched through post content for images that are local and changes the markup to load better
		 *
		 * @since 0.1.0
		 * @param string $content 	- the markup input from the_content Wordpress filter hook
		 * @return string 			- the mutated $html to match BIL markup if required
		 * @access private
		 */
		function content_filter( $content )
		{
			// Get all images within markup ( <img...> )
			preg_match_all('/(<img[^>]*src=".*?"[^>]*>)/i', $content, $matches);

			if( !isset($matches[0]) || count($matches[0]) < 1 )
				return do_shortcode( $content );

			foreach( $matches[0] as $match ){

				$markup = $this->rebuild_image( $match, false );

				if( !empty($markup) ){
					// Do the replacement
					$content = str_replace($match, $markup, $content);
				}

			}
			return $content;
		}

		/**
		 * Get an attachment ID from URL
		 *
		 * Credit to Micah Wood (https://wpscholar.com/blog/get-attachment-id-from-wp-image-url/) for the solution
		 *
		 * @since 0.3.2
		 * @param string $url 	- the image url
		 * @return int 			- Attachment ID on success, 0 on failure
		 * @access private
		 */
		function get_attachment_id( $url )
		{
			$attachment_id = 0;
			$dir = wp_upload_dir();
			if ( false !== strpos( $url, $dir['baseurl'] . '/' ) ) { // Is URL in uploads directory?
				$file = basename( $url );
				$query_args = array(
					'post_type'   => 'attachment',
					'post_status' => 'inherit',
					'fields'      => 'ids',
					'meta_query'  => array(
						array(
							'value'   => $file,
							'compare' => 'LIKE',
							'key'     => '_wp_attachment_metadata',
						),
					)
				);
				$query = new WP_Query( $query_args );
				if ( $query->have_posts() ) {
					foreach ( $query->posts as $post_id ) {
						$meta = wp_get_attachment_metadata( $post_id );
						$original_file       = basename( $meta['file'] );
						$cropped_image_files = wp_list_pluck( $meta['sizes'], 'file' );
						if ( $original_file === $file || in_array( $file, $cropped_image_files ) ) {
							$attachment_id = $post_id;
							break;
						}
					}
				}
			}
			return $attachment_id;
		}

		/**
		 * Enqueue CSS
		 *
		 * @since 0.1.0
		 * @access private
		 */
		function enqueue_bil_styles()
		{
			wp_enqueue_style( 'bil-styles', BIL_URL.'/assets/dist/css/bil-styles.css', array(), '1.0' );
		}

		/**
		 * Enqueue JavaScript
		 *
		 * @since 0.1.0
		 * @access private
		 */
		function enqueue_bil_scripts()
		{
			wp_register_script( 'bil-scripts', BIL_URL.'/assets/dist/js/bil-scripts.js', array( 'jquery' ), '', true );
			wp_enqueue_script( 'bil-scripts' );
		}
	}
}

// Start up plugin
function BetterImageLoading()
{
	global $bilOptions, $better_image_loading;
  // $bilOptions = new BIL_Options();
	$better_image_loading = new BetterImageLoading($bilOptions);
}
add_action( 'after_setup_theme', 'BetterImageLoading' );
