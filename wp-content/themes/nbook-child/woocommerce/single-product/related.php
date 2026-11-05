<?php
/**
 * Related Products
 *
 * This template overrides default WooCommerce related products to match the homepage grid.
 *
 * @package Biblio
 * @version 10.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $related_products ) :
	if ( function_exists( 'wp_increase_content_media_count' ) ) {
		$content_media_count = wp_increase_content_media_count( 0 );
		if ( $content_media_count < wp_omit_loading_attr_threshold() ) {
			wp_increase_content_media_count( wp_omit_loading_attr_threshold() - $content_media_count );
		}
	}
	?>

	<section class="related products nb-prods-section nb-related-section" aria-label="Sản phẩm gợi ý">

		<div class="nb-sec-header-row">
			<h2 class="nb-sec-header-title">
				<span>✨ <?php echo esc_html( apply_filters( 'woocommerce_product_related_products_heading', 'SẢN PHẨM GỢI Ý DÀNH CHO BẠN' ) ); ?></span>
			</h2>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="nb-view-more-link">Xem tất cả ›</a>
		</div>

		<?php woocommerce_product_loop_start(); ?>

			<?php foreach ( $related_products as $related_product ) : ?>

				<?php
				$post_object = get_post( $related_product->get_id() );

				setup_postdata( $GLOBALS['post'] =& $post_object );

				wc_get_template_part( 'content', 'product' );
				?>

			<?php endforeach; ?>

		<?php woocommerce_product_loop_end(); ?>

	</section>
	<?php
endif;

wp_reset_postdata();
