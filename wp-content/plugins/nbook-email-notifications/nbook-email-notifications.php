<?php
/**
 * Plugin Name: Biblio SMTP & Email Notifications
 * Description: Sends transactional and marketing notifications through a configurable SMTP server.
 * Version: 1.0.0
 * Author: Biblio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'phpmailer_init', 'nbook_email_configure_smtp' );
function nbook_email_configure_smtp( $mailer ) {
	$settings = nbook_email_get_settings();

	if ( empty( $settings['host'] ) ) {
		return;
	}

	$mailer->isSMTP();
	$mailer->Host       = $settings['host'];
	$mailer->Port       = (int) $settings['port'];
	$mailer->SMTPAuth   = ! empty( $settings['username'] );
	$mailer->Username   = $settings['username'];
	$mailer->Password   = $settings['password'];
	$mailer->SMTPSecure  = $settings['encryption'];
	$mailer->From       = $settings['from_email'];
	$mailer->FromName   = $settings['from_name'];
	$mailer->CharSet    = 'UTF-8';
}

function nbook_email_get_settings() {
	return array(
		'host'       => defined( 'NBOOK_SMTP_HOST' ) ? NBOOK_SMTP_HOST : '',
		'port'       => defined( 'NBOOK_SMTP_PORT' ) ? (int) NBOOK_SMTP_PORT : 587,
		'encryption' => defined( 'NBOOK_SMTP_ENCRYPTION' ) ? NBOOK_SMTP_ENCRYPTION : 'tls',
		'username'   => defined( 'NBOOK_SMTP_USERNAME' ) ? NBOOK_SMTP_USERNAME : '',
		'password'   => defined( 'NBOOK_SMTP_PASSWORD' ) ? NBOOK_SMTP_PASSWORD : '',
		'from_email' => defined( 'NBOOK_MAIL_FROM' ) ? NBOOK_MAIL_FROM : get_option( 'admin_email' ),
		'from_name'  => defined( 'NBOOK_MAIL_FROM_NAME' ) ? NBOOK_MAIL_FROM_NAME : get_bloginfo( 'name' ),
	);
}

function nbook_email_send( $to, $subject, $body ) {
	$to = sanitize_email( $to );
	if ( ! is_email( $to ) ) {
		return false;
	}

	return wp_mail(
		$to,
		$subject,
		$body,
		array( 'Content-Type: text/plain; charset=UTF-8' )
	);
}

add_action( 'user_register', 'nbook_email_notify_new_user', 20, 1 );
function nbook_email_notify_new_user( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}

	nbook_email_send(
		$user->user_email,
		'Chào mừng bạn đến với Biblio',
		sprintf( "Xin chào %s,\n\nTài khoản Biblio của bạn đã được đăng ký thành công. Chúc bạn có trải nghiệm mua sách vui vẻ!\n\n%s", $user->display_name ?: $user->user_login, home_url( '/' ) )
	);
}

add_action( 'woocommerce_order_status_changed', 'nbook_email_notify_order_status', 20, 4 );
function nbook_email_notify_order_status( $order_id, $old_status, $new_status, $order ) {
	if ( ! $order instanceof WC_Order || $old_status === $new_status ) {
		return;
	}

	$email = $order->get_billing_email();
	if ( ! is_email( $email ) || ! $order->get_user_id() ) {
		return;
	}

	$status = wc_get_order_status_name( $new_status );
	nbook_email_send(
		$email,
		sprintf( 'Biblio cập nhật đơn hàng #%s: %s', $order->get_order_number(), $status ),
		sprintf( "Xin chào,\n\nĐơn hàng #%s của bạn đã chuyển từ “%s” sang “%s”.\n\nXem chi tiết đơn hàng: %s", $order->get_order_number(), wc_get_order_status_name( $old_status ), $status, $order->get_view_order_url() )
	);
}

add_action( 'transition_post_status', 'nbook_email_notify_new_content', 20, 3 );
function nbook_email_notify_new_content( $new_status, $old_status, $post ) {
	if ( 'publish' !== $new_status || 'publish' === $old_status || ! in_array( $post->post_type, array( 'product', 'shop_coupon' ), true ) ) {
		return;
	}

	$subject_prefix = 'product' === $post->post_type ? 'Sách mới về Biblio' : 'Khuyến mãi mới từ Biblio';
	$subscribers    = get_users(
		array(
			'fields'     => array( 'user_email' ),
			'role__in'   => array( 'customer', 'subscriber' ),
		)
	);

	foreach ( $subscribers as $subscriber ) {
		nbook_email_send(
			$subscriber->user_email,
			$subject_prefix . ': ' . $post->post_title,
			sprintf( "Xin chào,\n\n%s vừa được cập nhật: %s\n\nXem ngay: %s", $subject_prefix, $post->post_title, 'product' === $post->post_type ? get_permalink( $post ) : home_url( '/khuyen-mai/' ) )
		);
	}
}
