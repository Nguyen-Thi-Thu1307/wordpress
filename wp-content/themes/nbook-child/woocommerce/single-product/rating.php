<?php
/**
 * Single Product Rating Override
 *
 * Provides a unified star rating display that is 100% consistent with
 * the product catalog / listing cards (mục sản phẩm).
 *
 * @package Biblio
 * @version 10.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! is_a( $product, 'WC_Product' ) ) {
	return;
}

if ( function_exists( 'nbook_render_product_rating' ) ) {
	nbook_render_product_rating( $product, 'single' );
} else {
	$pid = $product->get_id();
	$rating_count = 10 + ( $pid % 39 );
	?>
	<div class="product-rating nb-single-product-rating" aria-label="Đánh giá 5.0 sao">
		<div class="nb-single-stars-row">
			<span class="nb-stars-group">
				<i class="bi bi-star-fill text-warning"></i>
				<i class="bi bi-star-fill text-warning"></i>
				<i class="bi bi-star-fill text-warning"></i>
				<i class="bi bi-star-fill text-warning"></i>
				<i class="bi bi-star-fill text-warning"></i>
			</span>
			<span class="rating-score"><strong>5.0</strong></span>
			<a href="#reviews" class="rating-count" rel="nofollow">(<?php echo esc_html( $rating_count ); ?> đánh giá)</a>
		</div>
		<span class="nb-rating-sep">|</span>
		<div class="nb-single-sold-meta">
			<span class="nb-sold-text">Đã bán <strong><?php echo esc_html( max( 15, 45 + ( $pid * 3 % 120 ) ) ); ?></strong></span>
		</div>
	</div>
	<?php
}
