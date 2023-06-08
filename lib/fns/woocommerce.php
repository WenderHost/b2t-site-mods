<?php

namespace b2tmods\woocommere;

/**
 * Remove breadcrumbs
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );

/**
 * Removes `order-date` column from My Account > Orders table
 *
 * @param      array  $columns  The columns
 *
 * @return     array  Filtered columns.
 */
function filter_account_orders_columns( $columns ){
    $columns = array_slice( $columns, 0, 1 ) + ['order-items' => __( 'Order/Items', 'woocommerce')] + array_slice( $columns, 1, count( $columns ) - 1, true );
    unset( $columns['order-number'], $columns['order-date'] );
    return $columns;
}
add_filter( 'woocommerce_account_orders_columns', __NAMESPACE__ . '\\filter_account_orders_columns' );

/**
 * Filters My Account tabs
 *
 * @param      array  $items  Array of tabs for My Account
 *
 * @return     array  The filtered array
 */
function filter_my_account_tabs($items) {
    //unset($items['dashboard']);
    //unset($items['orders']);
    unset($items['downloads']);
    //unset($items['edit-address']);
    //unset($items['payment-methods']);
    //unset($items['edit-account']);
    //unset($items['customer-logout']);

    return $items;
}
add_filter( 'woocommerce_account_menu_items', __NAMESPACE__ . '\\filter_my_account_tabs', 999 );

/**
 * Rebuilds the WooCommerce Product Category display on the
 * main shop page. It works by discarding the original
 * content and rebuilding it from scratch
 *
 * @param      <string>  $content  The content
 *
 * @return     string  Rebuilt WooCommerce Product Category list
 */
function filter_product_loop_start( $content ){
  if( ! is_shop() )
    return $content;

  $term = get_term_by( 'slug', 'uncategorized', 'product_cat' );

  $terms = get_terms([
    'taxonomy'=>'product_cat',
    'parent' => 0,
    'exclude' => $term->term_id,
  ]);
    $col = 1;
    foreach ($terms as $term) {
        $classes = [];
        if( 1 === $col )
          $classes[] = 'first';
        if( 4 === $col )
          $classes[] = 'last';


        $thumbnail_id = get_woocommerce_term_meta( $term->term_id, 'thumbnail_id', true );
        $image = wp_get_attachment_url( $thumbnail_id );
        $html.= '<li class="product-category product ' . implode( ' ', $classes ) . '"><a href="' . get_term_link( $term ) . '"><img src="' . $image . '" alt="' . esc_attr( $term->name ) . '" /><h2 class="woocommerce-loop-category__title">'.$term->name.'</h2></a></li>';

        if( 4 === $col ){
          $col = 1;
        } else {
          $col++;
        }
    }
    return '<ul class="products columns-4">'.$html.'</ul>';
}
add_filter( 'woocommerce_product_loop_start', __NAMESPACE__ . '\\filter_product_loop_start' );

/**
 * Removes `uncateogrized` category from WooCommerce Shop page
 *
 * For some reason, this isn't working
 *
 * @param      <object>  $q      The WP_Query
 */
function woocommerce_product_query( $q ){

    if( ! $q->is_main_query() || ! $q->is_post_type_archive() || ! is_shop() )
        return;

    $tax_query[] = [
        'taxonomy' => 'product_cat',
        'field' => 'slug',
        'terms' => ['uncategorized','business-analysis-playbook'],
        'operator' => 'NOT IN'
    ];

    if ( ! is_admin() ) {
        $q->set( 'tax_query', [[
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => ['uncategorized'],
            'operator' => 'NOT IN'
        ]]);
    }

    remove_action( 'pre_get_posts', __NAMESPACE__ . '\\woocommerce_product_query' );
}
//add_action( 'pre_get_posts', __NAMESPACE__ . '\\woocommerce_product_query' );

/**
 * Content for the order-items column.
 *
 * @param      <type>  $order  The order
 */
function order_items_column( $order ){
?>
    <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
        <?php echo _x( '#', 'hash before order number', 'woocommerce' ) . $order->get_order_number(); ?>
    </a> &ndash;
    <time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>"><?php echo esc_html( date( 'M j, Y', strtotime( $order->get_date_created() ) ) ); ?></time>
<?php
    $items = $order->get_items();
    foreach( $items as $item ){
        $items_array[] = $item['name'] . ' x ' . $item['qty'];
    }
    echo '<ul><li>' . implode( '</li><li>', $items_array ) . '</li></ul>';
}
add_action( 'woocommerce_my_account_my_orders_column_order-items', __NAMESPACE__ . '\\order_items_column' );

/**
 * Filters titles for product tabs
 *
 * @param      string  $title  The title
 * @param      string  $key    The key
 *
 * @return     string  Filtered title
 */
function filter_tab_titles( $title, $key ){
    switch ( $key ) {
        case 'reviews':
            $title = 'Reviews';
            break;
    }

    return $title;
}
add_filter( 'woocommerce_product_reviews_tab_title', __NAMESPACE__ . '\\filter_tab_titles', 15, 2 );

/**
 * Customize product view for courses
 *
 * @return void
 */
function course_product_view(){
    global $product;
    if( 'course' != $product->product_type )
        return;

    wp_enqueue_script( 'b2t-scripts' );

    remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
    remove_action( 'woocommerce_after_single_product_summary', 'Andalu_Woo_Courses_Single::class_table', 7 );
    remove_action( 'woocommerce_after_single_product_summary', 'Andalu_Woo_Courses_Single::sub_class_table', 7 );
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs' );

    add_action( 'woocommerce_before_single_product_summary', 'b2t_divi_wc_course_title_and_tabs', 20 );
    add_action( 'woocommerce_single_product_summary', function(){
        if( has_post_thumbnail() ){
            the_post_thumbnail( 'fullsize', ['style'=>'width: 100%; border: 1px solid #d9d9d9;'] );
        }
    }, 5 );
    add_filter( 'woocommerce_product_tabs', 'b2t_divi_public_classes_product_tab', 10, 1 );
    add_action( 'woocommerce_after_single_product_summary', __NAMESPACE__ . '\\course_product_related_posts', 10, 1 );
}
add_action( 'woocommerce_before_single_product', __NAMESPACE__ . '\\course_product_view' );

/**
 * Displays related posts below WooCommerce products.
 */
function course_product_related_posts(){
    global $post;

    $posts = [];
    $related_posts_count = get_post_meta( $post->ID, 'related_posts', true );
    error_log( '$post->ID = ' . $post->ID . '; $related_posts_count = ' . $related_posts_count );
    if( 0 < $related_posts_count ){
        for( $x = 0; $x < $related_posts_count; $x++ ){
            $posts[] = get_post_meta( $post->ID, 'related_posts_' . $x . '_related_post', true );
        }
    }

    if( 0 == count( $posts ) )
        return;
?>
<div class="et_pb_row" id="course-related-posts" style="width: 100%; clear: both;">
    <div class="et_pb_column et_pb_column_4_4  et_pb_column_23">
        <div class="et_pb_blog_grid_wrapper">
            <div class="et_pb_blog_grid clearfix et_pb_module et_pb_bg_layout_light  et_pb_blog_0" data-columns="3">
            <?php
            $posts = get_posts( ['post__in' => $posts, 'numberposts' => 3, 'orderby' => 'post__in'] );
            foreach( $posts as $post ){
                ?>
                <div class="column size-1of3">
                    <article id="post-21349" class="et_pb_post clearfix">
                        <?php
                        if( has_post_thumbnail( $post->ID ) ){
                        ?>
                        <div class="et_pb_image_container">
                            <a href="<?php echo get_the_permalink( $post->ID ); ?>" class="entry-featured-image-url">
                                <?php echo get_the_post_thumbnail( $post->ID, 'et-pb-post-main-image', ['altt' => get_the_title( $post->ID )] ); ?>
                            </a>
                        </div> <!-- .et_pb_image_container -->
                        <?php
                        }
                        $display_name = get_the_author_meta( 'display_name', $post->post_author );
                        ?>
                        <h2 class="entry-title"><a href="<?php echo get_the_permalink( $post->ID ); ?>"><?php echo get_the_title( $post->ID ) ?></a></h2>
                        <p class="post-meta">by <span class="author vcard"><a href="<?php echo get_author_posts_url( $post->post_author ); ?>" title="Posts by <?= esc_attr( $display_name); ?>" rel="author"><?= $display_name; ?></a></span>  |  <span class="published"><?php echo get_the_date( 'M j, Y', $post->ID ) ?></span></p>
                        <div class="post-content">
                            <p><?php truncate_post( 260, true, $post, true ) ?></p>
                            <a href="<?php echo get_the_permalink( $post->ID ); ?>" class="more-link">read more</a>
                        </div><!-- .post_content -->
                    </article>
                </div><!-- .column.size-1of3 -->
                <?php
            }
            ?>
            </div><!-- .et_pb_posts -->
        </div><!-- .et_pb_blog_grid_wrapper -->
    </div> <!-- .et_pb_column -->
</div><!-- .et_pb_row -->
<?php
}


function hide_category_count() {
    // No count
    // Ref: https://docs.woocommerce.com/document/hide-sub-category-product-count-in-product-archives/
}
add_filter( 'woocommerce_subcategory_count_html', __NAMESPACE__ . '\\hide_category_count' );

/**
 * Removes a product image lightbox.
 */
function remove_product_image_lightbox() {
    remove_theme_support( 'wc-product-gallery-lightbox' );
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\\remove_product_image_lightbox', 100 );

/**
 * Adds a Register form note.
 */
add_action( 'woocommerce_register_form', function(){
  echo '<style>form.woocommerce-form.woocommerce-form-register.register p:nth-child(2){display: none;}</style>';
  echo '<p>A link to set a new password will be sent to your email address. If you don\'t see an email with the password reset link, please check your spam folder.</p><p><strong>Important:</strong> If you are creating an account, please note:</p><ul style="margin-bottom: 1.1em; padding-left: 30px;"><li style="margin-bottom: 1.1em;">The email address you enter above must match the email address we have on file with your certification records.</li><li>If you create your account and find that your certification records are not published (classes/exams, badge and certification status), please <a href="mailto:certification@b2ttraining.com">email us</a> to update your primary email address to match your certification record associated address.</li></ul>';
});

/**
 * Add a Login form note.
 */
add_action( 'woocommerce_login_form_start', function(){
  echo '<p>The method used to access our certification records was updated as of March 1, 2023. If your B2T Account was created prior to this date, please use the <strong>"Register"</strong> section of this page to establish a new account to access your records. We apologize for the inconvenience.</p>';
});

/**
 * Reverses the order of reviews on WC Product pages.
 *
 * @param      array  $args{
 *      @type   bool    $reverse_top_level  Set to TRUE to show reviews in DESC chronological order.
 * }
 *
 * @return     array  Arguments for the product review list.
 */
function reverse_review_order( $args ){
    $args['reverse_top_level'] = true;
    return $args;
}
add_filter( 'woocommerce_product_review_list_args', __NAMESPACE__ . '\\reverse_review_order', 999 );
