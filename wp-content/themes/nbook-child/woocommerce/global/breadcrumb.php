<?php
/**
 * Shop / Product Breadcrumb
 *
 * Overridden in nbook-child theme for unified, consistent breadcrumb navigation
 * across all product and catalog pages (Trang chủ > Sản phẩm > ...).
 *
 * @package Biblio
 * @version 10.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $breadcrumb ) ) {

	// Unify breadcrumb trail for single product pages: Trang chủ > Sản phẩm > [Danh mục] > [Tên sách]
	if ( is_product() ) {
		$shop_url   = wc_get_page_permalink( 'shop' );
		$shop_title = 'Sản phẩm';
		$has_shop   = false;

		foreach ( $breadcrumb as $c ) {
			if ( $c[0] === 'Sản phẩm' || $c[0] === 'Cửa hàng' || $c[0] === 'Danh mục sách' || ( ! empty( $c[1] ) && untrailingslashit( $c[1] ) === untrailingslashit( $shop_url ) ) ) {
				$has_shop = true;
				break;
			}
		}

		if ( ! $has_shop && count( $breadcrumb ) >= 2 ) {
			$first = array_shift( $breadcrumb );
			$first[0] = 'Trang chủ';
			array_unshift( $breadcrumb, [ $shop_title, $shop_url ] );
			array_unshift( $breadcrumb, $first );
		}
	}

	echo '<nav class="woocommerce-breadcrumb nb-custom-product-breadcrumb" aria-label="Breadcrumb">';

	$total = count( $breadcrumb );
	foreach ( $breadcrumb as $key => $crumb ) {
		$is_last = ( $total === $key + 1 );

		if ( ! empty( $crumb[1] ) && ! $is_last ) {
			$title_html = esc_html( $crumb[0] );
			if ( $key === 0 ) {
				$title_html = '<i class="bi bi-house-door" style="margin-right: 5px;"></i>' . $title_html;
			}
			echo '<a href="' . esc_url( $crumb[1] ) . '" class="nb-bc-link">' . $title_html . '</a>';
		} else {
			// Đang xem ở đâu thì in đậm đổi màu đỏ
			echo '<span class="nb-bc-current">' . esc_html( $crumb[0] ) . '</span>';
		}

		if ( ! $is_last ) {
			echo '<span class="nb-bc-sep">&gt;</span>';
		}
	}

	echo '</nav>';

}
