<?php
/**
 * Simple product add to cart - Shopee Action Bar Style
 *
 * @package Biblio
 * @version 10.2.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

// Lấy thông tin tồn kho cho hàng "Số lượng"
$stock_qty = $product->get_stock_quantity();
if ( $product->managing_stock() && ! is_null( $stock_qty ) ) {
	$stock_text = $stock_qty . ' sản phẩm có sẵn';
} elseif ( $product->is_in_stock() ) {
	$stock_text = 'Sản phẩm có sẵn';
} else {
	$stock_text = 'Hết hàng';
}

echo wc_get_stock_html( $product );

if ( $product->is_in_stock() ) : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<div class="nb-single-action-box">
			<!-- Hàng 1: Số lượng & Sản phẩm có sẵn -->
			<div class="nb-single-qty-row">
				<label class="nb-single-qty-label" for="quantity_<?php echo esc_attr( $product->get_id() ); ?>">Số Lượng</label>
				<div class="nb-single-qty-control">
					<?php
					do_action( 'woocommerce_before_add_to_cart_quantity' );

					woocommerce_quantity_input(
						array(
							'min_value'   => $product->get_min_purchase_quantity(),
							'max_value'   => $product->get_max_purchase_quantity(),
							'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(),
						)
					);

					do_action( 'woocommerce_after_add_to_cart_quantity' );
					?>
				</div>
				<span class="nb-single-stock-avail"><?php echo esc_html( $stock_text ); ?></span>
			</div>

			<!-- Hidden ID sản phẩm đảm bảo gửi đúng ID cho cả 2 nút -->
			<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" />

			<!-- Hàng 2: Hai nút Thêm Vào Giỏ Hàng & Mua Ngay chuẩn Shopee -->
			<div class="nb-single-btn-row">
				<button type="submit" name="submit_action" value="add_to_cart" class="single_add_to_cart_button nb-btn-shopee-cart button alt" aria-label="Thêm Vào Giỏ Hàng">
					<svg class="nb-cart-plus-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="9" cy="21" r="1"/>
						<circle cx="20" cy="21" r="1"/>
						<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
						<line x1="12" y1="9" x2="16" y2="9"/>
						<line x1="14" y1="7" x2="14" y2="11"/>
					</svg>
					<span>Thêm Vào Giỏ Hàng</span>
				</button>

				<button type="submit" name="nb_buy_now" value="1" class="nb-btn-shopee-buy-now button" aria-label="Mua Ngay">
					<span>Mua Ngay</span>
				</button>
			</div>
		</div>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	</form>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php endif; ?>
