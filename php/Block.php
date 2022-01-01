<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
		add_action( 'save_post', [ $this, 'xwp_set_custom_post_cache' ], 10, 3 );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Set object cache for custom post loop
	 *
	 * @param int     $post_id post id.
	 * @param object  $post post object.
	 * @param boolean $update post update flag.
	 * @return void
	 */
	function xwp_set_custom_post_cache( $post_id, $post, $update ) {
		$this->xwp_get_top_posts_cat_foo_baz( true );
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$post_types = get_post_types( [ 'public' => true ] );
		$class_name = $attributes['className'];
		ob_start();
		?>
		<div class="<?php echo $class_name; ?>">
			<h2>Post Counts</h2>
			<ul>
				<?php get_posts(); ?>
				<?php
				foreach ( $post_types as $post_type_slug ) :
					$post_type_object = get_post_type_object( $post_type_slug );
					$post_count       = ( 'attachment' !== $post_type_slug ) ? wp_count_posts( $post_type_slug )->publish : array_sum( (array) wp_count_attachments() );
					?>
					<li>
						<?php echo 'There are ' . $post_count . ' ' . $post_type_object->labels->name; ?> 
					</li> 
				<?php endforeach; ?>
			</ul>
			<p><?php echo 'The current post ID is ' . get_the_ID(); // or can be used with sanitize_text_field(GET['post_id']). ?></p>
			<?php
			$xwp_top_posts = $this->xwp_get_top_posts_cat_foo_baz();
			if ( $xwp_top_posts->found_posts ) :
				?>
				<h2><?php _e( '5 posts with the tag of foo and the category of baz', 'site-counts' ); ?></h2>
				<ul>
					<?php
					foreach ( array_slice( $xwp_top_posts->posts, 0, 5 ) as $post ) :
						if ( get_the_ID() === $post->ID ) {
							continue;
						}
						?>
					<li><?php echo $post->post_title; ?></li>
						<?php
					endforeach;
			endif;
			?>
				</ul>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Retrieve top 5 posts with tag of foo.
	 *
	 * @param bool $force_refresh Optional. Whether to force the cache to be refreshed. Default false.
	 * @return array|WP_Error Array of WP_Post objects with the highest comment counts, WP_Error object otherwise.
	 */
	public function xwp_get_top_posts_cat_foo_baz( $force_refresh = false ) {
		// Check for the top post from key in the 'top_posts' group.
		$cat_foo_baz_posts = wp_cache_get( 'xwp_top_commented_posts', 'top_posts' );
		// If nothing is found, build the object.
		if ( true === $force_refresh || false === $cat_foo_baz_posts ) {
			// Grab the top 10 most commented posts.

			$cat_foo_baz_posts_query = new WP_Query(
				[
					'post_type'      => 'post',
					'posts_per_page' => 10,
					'no_found_rows'  => true,
					'date_query'     => [
						[
							'hour'    => 9,
							'compare' => '>=',
						],
						[
							'hour'    => 17,
							'compare' => '<=',
						],
					],
					'tag'            => 'foo',
					'category_name'  => 'baz',
				]
			);

			$cat_foo_baz_posts = new WP_Query( $cat_foo_baz_posts_query );

			if ( ! is_wp_error( $cat_foo_baz_posts ) && $cat_foo_baz_posts->have_posts() ) {
				// In this case we don't need a timed cache expiration.
				wp_cache_set( 'xwp_top_posts_cat_foo_baz', $cat_foo_baz_posts->posts, 'top_posts' );
			}
		}
		return $cat_foo_baz_posts;
	}
}
?>