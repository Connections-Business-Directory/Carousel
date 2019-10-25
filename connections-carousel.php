<?php
/**
 * An extension for Connections Business Directory which adds a shortcode to display entries in a carousel.
 *
 * @package   Connections Business Directory Extension - Carousel
 * @category  Extension
 * @author    Steven A. Zahm
 * @license   GPL-2.0+
 * @link      https://connections-pro.com
 * @copyright 2019 Steven A. Zahm
 *
 * @wordpress-plugin
 * Plugin Name:       Connections Business Directory Extension - Carousel
 * Plugin URI:        http://connections-pro.com
 * Description:       An extension for Connections Business Directory which adds a shortcode to display entries in a carousel.
 * Version:           1.0
 * Author:            Steven A. Zahm
 * Author URI:        https://connections-pro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       connections-carousel
 * Domain Path:       /languages
 */

if ( ! class_exists( 'Connections_Directory_Carousel' ) ) :

	final class Connections_Directory_Carousel {

		/**
		 * @since 1.0
		 */
		const VERSION = '1.0';

		/**
		 * Stores the instance of this class.
		 *
		 * @var $instance Connections_Directory_Carousel
		 *
		 * @access private
		 * @since  1.0
		 */
		private static $instance;

		/**
		 * @var string The absolute path this this file.
		 *
		 * @access private
		 * @since  1.0
		 */
		private $file = '';

		/**
		 * @var string The URL to the plugin's folder.
		 *
		 * @access private
		 * @since  1.0
		 */
		private $url = '';

		/**
		 * @var string The absolute path to this plugin's folder.
		 *
		 * @access private
		 * @since  1.0
		 */
		private $path = '';

		/**
		 * @var string The basename of the plugin.
		 *
		 * @access private
		 * @since 2.7
		 */
		private $basename = '';

		/**
		 * A dummy constructor to prevent the class from being loaded more than once.
		 *
		 * @access public
		 * @since  1.0
		 */
		public function __construct() { /* Do nothing here */ }

		/**
		 * The main plugin instance.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 *
		 * @return Connections_Directory_Carousel
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Connections_Directory_Carousel ) ) {

				self::$instance = $self = new self;

				$self->file     = __FILE__;
				$self->url      = plugin_dir_url( $self->file );
				$self->path     = plugin_dir_path( $self->file );
				$self->basename = plugin_basename( $self->file );

				$self->hooks();
			}

			return self::$instance;
		}

		private function hooks() {

			//Register the shortcode.
			add_shortcode( 'cn_carousel', array( __CLASS__, 'shortcode' ) );

			// Register and enqueue the styles.
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueueStyles' ), 999 );
		}

		/**
		 * @since 1.0
		 *
		 * @return string
		 */
		public function pluginPath() {

			return $this->path;
		}

		/**
		 * @since 1.0
		 *
		 * @return string
		 */
		public function pluginURL() {

			return $this->url;
		}

		/**
		 * @since 1.0
		 *
		 * @param string $version
		 * @param string $path
		 *
		 * @return string
		 */
		public function getAssetVersion( $version, $path ) {

			$time = filemtime( $path );

			if ( FALSE !== $time ) {

				$version = "{$version}-{$time}";
			}

			return $version;
		}

		/**
		 * Callback for the `wp_enqueue_scripts` action.
		 *
		 * @since 1.0
		 */
		public static function enqueueStyles() {

			$url  = cnURL::makeProtocolRelative( Connections_Directory_Carousel()->pluginURL() );
			$path = Connections_Directory_Carousel()->pluginPath();

			wp_register_script(
				'cn-carousel-slick',
				"{$url}includes/vendor/slick/slick.js",
				array( 'jquery' ),
				Connections_Directory_Carousel()->getAssetVersion( '1.8.1', "{$path}includes/vendor/slick/slick.js" )
			);

			wp_register_script(
				'cn-carousel',
				"{$url}assets/js/public.js",
				array( 'cn-carousel-slick' ),
				Connections_Directory_Carousel()->getAssetVersion( Connections_Directory_Carousel::VERSION, "{$path}assets/js/public.js" )
			);

			wp_register_style(
				'cn-carousel',
				"{$url}assets/css/public.css",
				array( 'cn-public' ),
				Connections_Directory_Carousel()->getAssetVersion( Connections_Directory_Carousel::VERSION, "{$path}assets/css/public.css" )
			);

			wp_register_style(
				'cn-carousel-slick',
				"{$url}includes/vendor/slick/slick.css",
				array( 'cn-public' ),
				Connections_Directory_Carousel()->getAssetVersion( '1.8.1', "{$path}includes/vendor/slick/slick.css" )
			);

			wp_register_style(
				'cn-carousel-slick-theme',
				"{$url}includes/vendor/slick/slick-theme.css",
				array( 'cn-carousel-slick' ),
				Connections_Directory_Carousel()->getAssetVersion( '1.8.1', "{$path}includes/vendor/slick/slick-theme.css" )
			);
		}

		/**
		 * @since 1.0
		 *
		 * @param array  $atts    Shortcode attributes array,
		 * @param null   $content Content between shortcode open/close tags.
		 * @param string $tag     Shortcode name.
		 *
		 * @return string
		 */
		public static function shortcode( $atts, $content, $tag ) {

			$defaults = array(
				'category' => NULL,
				'limit'    => 100,
			);

			$atts = shortcode_atts( $defaults, $atts );
			$html = '';

			$results = Connections_Directory()->retrieve->entries( $atts );

			if ( 0 === count( $results ) ) {

				return $html;
			}

			wp_enqueue_script( 'cn-carousel' );
			wp_enqueue_style( 'cn-carousel' );
			wp_enqueue_style( 'cn-carousel-slick-theme' );

			foreach ( $results as $data ) {

				$entry = new cnOutput( $data );

				$entry->directoryHome();

				$name    = $entry->getNameBlock(
					array(
						'link'   => FALSE,
						'return' => TRUE,
					)
				);

				$image   = $entry->getImage(
					array(
						'fallback' => array(
							'type'   => 'block',
							'string' => '',
						),
						'return' => TRUE
					)
				);

				$excerpt = $entry->excerpt( array( 'return' => TRUE ) );

				$row   = '<div style="padding: 20px">';

				$row .= "<h3>{$name}</h3>";
				$row .= "<div class='cn-slick-carousel-section-column cn-slick-carousel-section-column'>{$image}</div>";
				$row .= "<div class='cn-slick-carousel-section-column cn-slick-carousel-section-column'>{$excerpt}</div>";

				$row .= '</div>';

				$html .= $row;
			}

			$containerOpen  = '<div class="cn-slick-carousel-section cn-slick-carousel-section-group">';
			$containerClose = '</div>';

			$sliderOpen  = '<div class="cn-slick-carousel" data-slick=\'{"autoplay": true, "autoplaySpeed": 5000, "infinite": true}\'>';
			$SliderClose = '</div>';

			return $containerOpen . $sliderOpen . $html . $SliderClose . $containerClose;
		}
	}

	/**
	 * Start up the extension.
	 *
	 * @return Connections_Directory_Carousel|false
	 * @since 1.0
	 *
	 */
	function Connections_Directory_Carousel() {

		if ( class_exists('connectionsLoad') ) {

			return Connections_Directory_Carousel::instance();

		} else {

			add_action(
				'admin_notices',
				function() {
					echo '<div id="message" class="error"><p><strong>ERROR:</strong> Connections must be installed and active in order use the Connections Carousel addon.</p></div>';
				}
			);

			return FALSE;
		}
	}

	/**
	 * Since Connections loads at default priority 10, and this extension is dependent on Connections,
	 * we'll load with priority 11 so we know Connections will be loaded and ready first.
	 */
	add_action( 'plugins_loaded', 'Connections_Directory_Carousel', 11 );

endif;
