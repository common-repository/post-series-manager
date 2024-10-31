<?php
/**
 *
 * The main plugin file, this is where all the magic happens.
 *
 * @link              http://cheffism.com
 * @since             1.0.0
 * @package           Post_Series_Manager
 *
 * @wordpress-plugin
 * Plugin Name:       Post Series Manager
 * Plugin URI:        http://cheffism.com/post-series-manager/
 * Description:       This plugin will help you manage and display post series more easily. You'll be able to create/assign series and display other posts in the series.
 * Version:           1.2.1
 * Author:            Jeffrey de Wit, Adam Soucie
 * Author URI:        http://cheffism.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       post-series-manager
 * Domain Path:       /languages
 *
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Main plugin class.
 *
 * This class defines all code for running the plugin.
 *
 * @since      1.0.0
 * @package    Post_Series_Manager
 * @author     Jeffrey de Wit <jeffrey.dewit@gmail.com>
 */
class Post_Series_Manager {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_version    The current version of the plugin.
	 */
	protected $plugin_version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	function __construct() {
		$this->plugin_name = 'post-series-manager';
		$this->plugin_version = '1.2.1';

		register_activation_hook( __FILE__, array( $this, 'post_series_manager_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'post_series_manager_deactivate' ) );

		add_action( 'init', array( $this, 'post_series_taxonomy' ) );
		add_action( 'plugins_loaded', array( $this, 'post_series_i18n' ) );
		add_action( 'init', array( $this, 'post_series_shortcodes' ) );
		add_filter( 'the_content', array( $this, 'post_series_before' ) );
		add_filter( 'the_content', array( $this, 'post_series_after' ) );
		add_action( 'pre_get_posts', array( $this, 'post_series_sort_order' ) );
	}

	/**
	 * Register taxonomy and force rewrite flush when plugin is activated.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function post_series_manager_activate() {
		$this->post_series_taxonomy();
		flush_rewrite_rules();
	}

	/**
	 * Force rewrite flush when plugin is deactivated.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function post_series_manager_deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Registers the post series taxonomy.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function post_series_taxonomy() {
		register_taxonomy(
			'post-series',
			'post',
			array(
				'label' => __( 'Post Series', 'post-series-manager' ),
				'rewrite' => array( 'slug' => 'post-series' ),
				'labels' => array(
					'name' => __( 'Post Series', 'post-series-manager' ),
					'singular_name' => __( 'Post Series', 'post-series-manager' ),
					'all_items' => __( 'All Post Series', 'post-series-manager' ),
					'edit_item' => __( 'Edit Post Series', 'post-series-manager' ),
					'view_item' => __( 'View Post Series', 'post-series-manager' ),
					'update_item' => __( 'Update Post Series', 'post-series-manager' ),
					'add_new_item' => __( 'Add New Post Series', 'post-series-manager' ),
					'new_item_name' => __( 'New Post Series Name', 'post-series-manager' ),
					'search_items' => __( 'Search Post Series', 'post-series-manager' ),
					'popular_items' => __( 'Popular Post Series', 'post-series-manager' ),
					'separate_items_with_commas' => __( 'Separate post series with commas', 'post-series-manager' ),
					'add_or_remove_items' => __( 'Add or remove post series', 'post-series-manager' ),
					'choose_from_most_used' => __( 'Choose from most used post series', 'post-series-manager' ),
					'not_found' => __( 'No post series found', 'post-series-manager' ),
				),
				'show_in_rest' => true,
			)
		);
	}

	/**
	 * Registers the post series i18n.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function post_series_i18n() {
		load_plugin_textdomain(
			$this->plugin_name,
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);
	}

	/**
	 * Registers the post series shortcodes.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function post_series_shortcodes() {
		add_shortcode( 'post_series_block', array( &$this, 'post_series_block_function' ) );
		add_shortcode( 'post_series_nav', array( &$this, 'post_series_nav_function' ) );
	}

	/**
	 * The post_series_block shortcode output.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function post_series_block_function() {
		global $post;

		$shortcode_html = '';
		$all_series = get_the_terms( $post->ID, 'post-series' );

		if ( $all_series ) {
			foreach ( $all_series as $series ) {
				$series_block = '<div class="post-series-manager-block"><p>%s %s</p>%s</div>';
				$series_link = sprintf( '<a href="%s">%s</a>', get_term_link( $series ), $series->name );

				$series_text = apply_filters( 'post-series-manager-series-text', __( 'This post is part of the series', 'post-series-manager' ) );

				if ( is_single() ) {
					$series_list_html = $this->get_series_list_html( $series );
					$shortcode_html .= sprintf( $series_block, $series_text, $series_link, $series_list_html );
				} else {
					$shortcode_html .= sprintf( $series_block, $series_text, $series_link );
				}
			}
		}
		return $shortcode_html;
	}

	/**
	 * Generates the markup for the Post Series list.
	 *
	 * @since  1.0.0
	 *
	 * @param  object $series The post series to work through.
	 * @return string $series_list_HTML Completed HTML string of all the series lists
	 */
	public function get_series_list_html( $series ) {
		$current_post_id = get_the_ID();

		$current_indicator = apply_filters( 'post-series-manager-current-text', __( '(Current)', 'post-series-manager' ) );

		$args = array(
			'tax_query' => array(
				array(
					'taxonomy' => 'post-series',
					'field' => 'slug',
					'terms' => $series->name,
				),
			),
			'order' => 'ASC',
			'posts_per_page' => -1,
			);

		$series_posts = new WP_Query( $args );

		if ( $series_posts->post_count > 1 ) {
			$current_post = get_post( $current_post_id );
			$current_index = array_search( $current_post, $series_posts->posts, true );

			$start_index = $current_index - 2;
			$end_index = $current_index + 2;

			if ( $start_index < 0 ) {
				$start_index = 0;
			}

			if ( $end_index > ( $series_posts->post_count - 1) ) {
				$end_index = $series_posts->post_count - 1;
			}

			$list_introduction = apply_filters( 'post-series-list-intro-text', sprintf( '<p>%s</p>', __( 'Other posts in this series:', 'post-series-manager' ) ) );

			$list_opening = apply_filters( 'post-series-list-opening-tags', sprintf( '<ol class="post-series-manager-post-list" start="%s">', $start_index + 1 ) );

			$series_list_html = $list_introduction . $list_opening;

			for ( $i = $start_index; $i <= $end_index; $i++ ) {
				$post_title   = get_the_title( $series_posts->posts[ $i ]->ID );
				$post_permalink = get_permalink( $series_posts->posts[ $i ]->ID );

				$list_item = "<li class='post-series-manager-post'>%s</li>";

				if ( $series_posts->posts[ $i ]->ID === $current_post_id ) {
					$title_markup = $post_title . ' ' . $current_indicator;
				} else {
					$title_markup = "<a href='$post_permalink'>" . $post_title . '</a>';
				}

				$series_list_html .= sprintf( $list_item, $title_markup );
			}

			$list_ending = apply_filters( 'post-series-list-ending-tags', '</ol>' );

			$series_list_html .= $list_ending;

			return $series_list_html;
		}
	}

	/**
	 * Post series navigation function. Generates "Continue reading" link.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function post_series_nav_function() {
		global $post;

		$shortcode_html = '';
		$all_series = get_the_terms( $post->ID, 'post-series' );

		if ( $all_series ) {
			$series_text = apply_filters( 'post-series-manager-next-text', __( 'Continue reading this series:', 'post-series-manager' ) );

			$series_nav = '<div class="post-series-nav"><p>%s<br /> %s</p></div>';
			$next = get_next_post_link( '%link', '%title', true, null, 'post-series' );

			if ( $next && is_single() ) {
				$shortcode_html = sprintf( $series_nav, $series_text, $next );
			}
		}

		return $shortcode_html;

	}

	/**
	 * Automatically add shortcodes to post content, before the post content.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @param  string $content The post content.
	 */
	public function post_series_before( $content ) {
		if ( is_single() ) {
			$series_box = do_shortcode( '[post_series_block]' );
			$content = $series_box . $content;
		}

		return $content;
	}

	/**
	 * Automatically add shortcodes to post content, after the post content.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @param  string $content The post content.
	 */
	public function post_series_after( $content ) {
		if ( is_single() ) {
			$series_nav = do_shortcode( '[post_series_nav]' );
			$content = $content . $series_nav;
		}

		return $content;
	}

	/**
	 * Reverse sort order, since part 1 is generally older than part X.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @param  object $query WP_Query object.
	 */
	public function post_series_sort_order( $query ) {
		if ( ( $query->is_main_query() ) && ( is_tax( 'post-series' ) ) ) {
			$query->set( 'order', 'ASC' );
		}
	}
}

$post_series_manager = new Post_Series_Manager();
