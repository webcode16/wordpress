// Function to create the custom post type
<?php
function create_posttype() {
    register_post_type('product-slider',
        array(
            'labels' => array(
                'name' => __('Product Slider'),
                'singular_name' => __('Product Slider')
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'product-slider'),
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail'), // Add 'thumbnail' to support featured images
        )
    );
	    register_taxonomy('product-category', 'product-slider', array(
        'labels' => array(
            'name' => __('Product Categories'),
            'singular_name' => __('Product Category'),
            'search_items' => __('Search Product Categories'),
            'all_items' => __('All Product Categories'),
            'parent_item' => __('Parent Product Category'),
            'parent_item_colon' => __('Parent Product Category:'),
            'edit_item' => __('Edit Product Category'),
            'update_item' => __('Update Product Category'),
            'add_new_item' => __('Add New Product Category'),
            'new_item_name' => __('New Product Category Name'),
            'menu_name' => __('Product Categories'),
        ),
        'hierarchical' => true, // Set to true for hierarchical taxonomy (like categories), false for non-hierarchical (like tags)
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'product-category'),
        'show_in_rest' => true, // Make it available in the REST API
    ));
}
// Hooking up our function to theme setup
add_action('init', 'create_posttype');

// Add custom meta box for URL
function add_custom_meta_box() {
    add_meta_box(
        'custom_url_meta_box', 
        'Custom URL', 
        'custom_url_meta_box_html', 
        'product-slider'
    );
}
add_action('add_meta_boxes', 'add_custom_meta_box');

// Display the custom meta box
function custom_url_meta_box_html($post) {
    $value = get_post_meta($post->ID, '_custom_url', true);
    ?>
    <label for="custom_url">URL:</label>
    <input type="url" id="custom_url" name="custom_url" value="<?php echo esc_attr($value); ?>" style="width: 100%;">
    <?php
}

// Save the custom meta box data
function save_custom_url_meta_box_data($post_id) {
    if (array_key_exists('custom_url', $_POST)) {
        update_post_meta(
            $post_id,
            '_custom_url',
            sanitize_text_field($_POST['custom_url'])
        );
    }
}
add_action('save_post', 'save_custom_url_meta_box_data');



// Function to handle the shortcode
function product_slider_shortcode($atts) {
    // Define default attributes
    $atts = shortcode_atts(
        array(
            'posts' => 5,
			'category' => 'windows',
        ),
        $atts,
        'windows'
    );
    $query = new WP_Query(array(
        'post_type' => 'product-slider',
        'posts_per_page' => $atts['posts'],
		'tax_query' => array(
            array(
                'taxonomy' => 'product-category',
                'field'    => 'slug',
                'terms'    => $atts['category'],
           ),
		 ),			
    ));
	 $category = get_term_by('slug', $atts['category'], 'product-category');
     $category_name = $category ? $category->name : '';	
	 $sanitized_category_name = sanitize_title($category_name);
    ob_start(); ?>
<link href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css" rel="stylesheet">
<style>
.main-product-slider-s .element.element-1.slick-slide{
	margin: 0 !important;
	width: 100% ;
	height: 100%;
}
</style>
<?php
    if ($query->have_posts()) {
        echo '<div class="category-'. esc_attr($sanitized_category_name) .' product-slider-list"> <div class="slick-slider">';
        while ($query->have_posts()) {
            $query->the_post();
			$custom_url = get_post_meta(get_the_ID(), '_custom_url', true);
            ?>
		
	<div class="element element-1">
          <div class="product-slider-item">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="product-slider-thumbnail">
                        <?php the_post_thumbnail(' '); // You can specify different sizes here ?>
                    </div>
                <?php endif; ?>
				<a href="<?php echo esc_url($custom_url); ?>">
					<div class="under-product-slider">
						<div class="product-slider-content">
							<h2><?php the_title(); ?></h2>
							<div class="product-slider-content"><?php the_excerpt(); ?></div>
						</div>
					</div>
				</a>
          </div>
	</div>
<?php
        }
        echo '</div> </div>';
        wp_reset_postdata();
    } else {
        echo '<p>No product slider items found</p>';
    }
	?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/jquery.slick/1.4.1/slick.min.js"></script>
<script>
$(".category-<?php echo esc_js($sanitized_category_name); ?> .slick-slider").slick({
  slidesToShow: 1,
  infinite: true,
  slidesToScroll: 1,
  autoplay: true,
  autoplaySpeed: 1500,
  fade: true, // Enable fade transition
  speed: 1000, // Duration of the transition (in milliseconds)	
  arrows: false,
  dots: false 
});
</script>

<?php
	
    return ob_get_clean();
}
function register_product_slider_shortcodes() {
    $categories = array('windows', 'doors', 'sliding-systems', ''); // List your categories here
    foreach ($categories as $category) {
        add_shortcode($category, function($atts) use ($category) {
            $atts['category'] = $category;
            return product_slider_shortcode($atts);
        });
    }
}
add_action('init', 'register_product_slider_shortcodes');

// add_shortcode('windows', 'product_slider_shortcode');



