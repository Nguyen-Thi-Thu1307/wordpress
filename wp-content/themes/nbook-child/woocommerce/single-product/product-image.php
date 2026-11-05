<?php
/**
 * Single Product Image
 *
 * This template overrides default WooCommerce single product image gallery
 * to provide a decorative luxury showcase frame and guarantee trust badges.
 *
 * @package Biblio
 * @version 11.1.0
 */

use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\ProductGallery\ProductMediaGallery;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
	return;
}

global $product;

$columns           = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
$post_thumbnail_id = $product->get_image_id();
$media_items       = ProductMediaGallery::get_product_media_gallery_items_for_display( $product );
$first_media_item  = $media_items[0] ?? array();
$first_media_id    = isset( $first_media_item['id'] ) ? absint( $first_media_item['id'] ) : $post_thumbnail_id;
$has_media         = ! empty( $first_media_item ) && 'placeholder' !== ( $first_media_item['source_type'] ?? '' );
$is_video          = $has_media && 'video' === ( $first_media_item['media_type'] ?? '' );
$wrapper_classes   = apply_filters(
	'woocommerce_single_product_image_gallery_classes',
	array(
		'woocommerce-product-gallery',
		'woocommerce-product-gallery--' . ( $has_media ? 'with-images' : 'without-images' ),
		'woocommerce-product-gallery--columns-' . absint( $columns ),
		'images',
	)
);
?>
<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?> nb-decorative-book-stage" data-columns="<?php echo esc_attr( $columns ); ?>" style="opacity: 0; transition: opacity .25s ease-in-out;">
	<div class="nb-gallery-top-badge">
		<i class="bi bi-patch-check-fill"></i>
		<span>SÁCH BẢN QUYỀN CHÍNH HÃNG</span>
	</div>

	<div class="woocommerce-product-gallery__wrapper">
		<?php
		if ( $is_video ) {
			$html = ProductMediaGallery::get_gallery_video_html( $first_media_item, true );
		} elseif ( $has_media && $first_media_id ) {
			$html = wc_get_gallery_image_html( $first_media_id, true );
		} else {
			$wrapper_classname = $product->is_type( ProductType::VARIABLE ) && ! empty( $product->get_visible_children() ) && '' !== $product->get_price() ?
				'woocommerce-product-gallery__image woocommerce-product-gallery__image--placeholder' :
				'woocommerce-product-gallery__image--placeholder';
			$html              = sprintf( '<div class="%s">', esc_attr( $wrapper_classname ) );
			$html             .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
			$html             .= '</div>';
		}

		if ( $is_video ) {
			echo apply_filters( 'woocommerce_single_product_video_thumbnail_html', $html, $first_media_id, $first_media_item );
		} else {
			echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $first_media_id );
		}

		do_action( 'woocommerce_product_thumbnails' );
		?>
	</div>

	<div class="nb-single-gallery-footer">
		<div class="nb-gf-item">
			<i class="bi bi-shield-fill-check text-success"></i>
			<span>100% Sách Thật</span>
		</div>
		<div class="nb-gf-item">
			<i class="bi bi-box-seam-fill text-primary"></i>
			<span>Đóng gói 3 lớp</span>
		</div>
		<div class="nb-gf-item">
			<i class="bi bi-lightning-charge-fill text-danger"></i>
			<span>Giao nhanh 2H</span>
		</div>
	</div>
</div>
