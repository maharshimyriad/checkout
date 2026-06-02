<?php
/**
 * ThemeHigh address book → WooCommerce checkout default values.
 *
 * @package FHS_Checkout
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'fhs_get_thwma_address_book' ) ) {
	/**
	 * @return array{
	 *     billing: array<string, array<string, string>>,
	 *     shipping: array<string, array<string, string>>,
	 *     default_billing: string,
	 *     default_shipping: string
	 * }
	 */
	function fhs_get_thwma_address_book() {
		static $book = null;

		if ( null !== $book ) {
			return $book;
		}

		$book = array(
			'billing'           => array(),
			'shipping'          => array(),
			'default_billing'   => '',
			'default_shipping'  => '',
		);

		if ( ! is_user_logged_in() ) {
			return $book;
		}

		$raw  = get_user_meta( get_current_user_id(), 'thwma_custom_address', true );
		$data = maybe_unserialize( $raw );

		if ( ! is_array( $data ) ) {
			return $book;
		}

		if ( ! empty( $data['billing'] ) && is_array( $data['billing'] ) ) {
			$book['billing'] = $data['billing'];
		}

		if ( ! empty( $data['shipping'] ) && is_array( $data['shipping'] ) ) {
			$book['shipping'] = $data['shipping'];
		}

		if ( ! empty( $data['default_billing'] ) ) {
			$book['default_billing'] = (string) $data['default_billing'];
		}

		if ( ! empty( $data['default_shipping'] ) ) {
			$book['default_shipping'] = (string) $data['default_shipping'];
		}

		return $book;
	}
}

if ( ! function_exists( 'fhs_get_default_address_entry' ) ) {
	/**
	 * @param string $type billing|shipping
	 * @return array<string, string>
	 */
	function fhs_get_default_address_entry( $type ) {
		$book = fhs_get_thwma_address_book();
		$key  = $book[ 'default_' . $type ] ?? '';

		if ( '' === $key || empty( $book[ $type ][ $key ] ) || ! is_array( $book[ $type ][ $key ] ) ) {
			return array();
		}

		return $book[ $type ][ $key ];
	}
}

if ( ! function_exists( 'fhs_get_saved_address_field' ) ) {
	/**
	 * @param array<string, string> $entry Address book row.
	 * @param string                $field_key e.g. billing_city, shipping_state.
	 */
	function fhs_get_saved_address_field( array $entry, $field_key ) {
		if ( isset( $entry[ $field_key ] ) && '' !== (string) $entry[ $field_key ] ) {
			return (string) $entry[ $field_key ];
		}

		$short = preg_replace( '/^(billing|shipping)_/', '', $field_key );

		if ( is_string( $short ) && isset( $entry[ $short ] ) && '' !== (string) $entry[ $short ] ) {
			return (string) $entry[ $short ];
		}

		return '';
	}
}

if ( ! function_exists( 'fhs_checkout_field_has_posted_value' ) ) {
	function fhs_checkout_field_has_posted_value( $field_key ) {
		if ( isset( $_POST[ $field_key ] ) && '' !== wc_clean( wp_unslash( $_POST[ $field_key ] ) ) ) {
			return true;
		}

		if ( empty( $_POST['post_data'] ) ) {
			return false;
		}

		parse_str( wp_unslash( $_POST['post_data'] ), $posted );

		return isset( $posted[ $field_key ] ) && '' !== wc_clean( $posted[ $field_key ] );
	}
}

if ( ! function_exists( 'fhs_apply_default_addresses_to_customer' ) ) {
	function fhs_apply_default_addresses_to_customer() {
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! is_user_logged_in() || ! function_exists( 'WC' ) || ! WC()->customer ) {
			return;
		}

		foreach ( array( 'billing', 'shipping' ) as $type ) {
			$entry = fhs_get_default_address_entry( $type );

			if ( ! $entry ) {
				continue;
			}

			foreach ( $entry as $field_key => $field_value ) {
				if ( ! is_string( $field_key ) || 0 !== strpos( $field_key, $type . '_' ) ) {
					continue;
				}

				$setter = 'set_' . $field_key;

				if ( is_callable( array( WC()->customer, $setter ) ) ) {
					WC()->customer->{$setter}( wc_clean( $field_value ) );
				}
			}
		}

		WC()->customer->save();
	}
}

if ( ! function_exists( 'fhs_checkout_get_default_address_value' ) ) {
	function fhs_checkout_get_default_address_value( $value, $input ) {
		if ( ! is_user_logged_in() ) {
			return $value;
		}

		if ( fhs_checkout_field_has_posted_value( $input ) ) {
			return $value;
		}

		$type = null;

		if ( 0 === strpos( $input, 'billing_' ) ) {
			$type = 'billing';
		} elseif ( 0 === strpos( $input, 'shipping_' ) ) {
			$type = 'shipping';
		}

		if ( ! $type ) {
			return $value;
		}

		$entry = fhs_get_default_address_entry( $type );

		if ( ! $entry ) {
			return $value;
		}

		$saved = fhs_get_saved_address_field( $entry, $input );

		return '' !== $saved ? $saved : $value;
	}
}

if ( ! function_exists( 'fhs_register_checkout_address_defaults' ) ) {
	function fhs_register_checkout_address_defaults() {
		if ( ! has_filter( 'woocommerce_checkout_get_value', 'fhs_checkout_get_default_address_value' ) ) {
			add_filter( 'woocommerce_checkout_get_value', 'fhs_checkout_get_default_address_value', 20, 2 );
		}

		if ( ! has_action( 'woocommerce_before_checkout_form', 'fhs_apply_default_addresses_to_customer' ) ) {
			add_action( 'woocommerce_before_checkout_form', 'fhs_apply_default_addresses_to_customer', 5 );
		}
	}
}

fhs_register_checkout_address_defaults();

if ( file_exists( __DIR__ . '/fhs-address-debug.php' ) ) {
	require_once __DIR__ . '/fhs-address-debug.php';
}
