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

if ( ! function_exists( 'fhs_get_profile_address_entry' ) ) {
	/**
	 * WooCommerce user-meta address when ThemeHigh has no saved billing rows.
	 *
	 * @param string $type billing|shipping
	 * @return array<string, string>
	 */
	function fhs_get_profile_address_entry( $type ) {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$user_id = get_current_user_id();
		$parts   = array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' );
		$entry   = array();

		foreach ( $parts as $part ) {
			$key           = $type . '_' . $part;
			$entry[ $key ] = (string) get_user_meta( $user_id, $key, true );
		}

		return array_filter( $entry, static function ( $value ) {
			return '' !== (string) $value;
		} );
	}
}

if ( ! function_exists( 'fhs_get_resolved_default_entry' ) ) {
	/**
	 * Address book default, or WooCommerce profile meta for billing.
	 *
	 * @param string $type billing|shipping
	 * @return array<string, string>
	 */
	function fhs_get_resolved_default_entry( $type ) {
		$entry = fhs_get_default_address_entry( $type );

		if ( $entry ) {
			return $entry;
		}

		if ( 'billing' === $type ) {
			return fhs_get_profile_address_entry( 'billing' );
		}

		return array();
	}
}

if ( ! function_exists( 'fhs_normalize_checkout_country' ) ) {
	function fhs_normalize_checkout_country( $country ) {
		$country = trim( (string) $country );

		if ( '' === $country ) {
			return '';
		}

		if ( 2 === strlen( $country ) ) {
			return strtoupper( $country );
		}

		if ( in_array( strtolower( $country ), array( 'australia', 'aus' ), true ) ) {
			return 'AU';
		}

		if ( function_exists( 'WC' ) && WC()->countries ) {
			foreach ( WC()->countries->get_countries() as $code => $name ) {
				if ( strcasecmp( (string) $name, $country ) === 0 ) {
					return (string) $code;
				}
			}
		}

		return $country;
	}
}

if ( ! function_exists( 'fhs_normalize_checkout_state' ) ) {
	function fhs_normalize_checkout_state( $state, $country_code ) {
		$state        = trim( (string) $state );
		$country_code = fhs_normalize_checkout_country( $country_code );

		if ( '' === $state || '' === $country_code ) {
			return $state;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->countries ) {
			return $state;
		}

		$states = WC()->countries->get_states( $country_code );

		if ( ! is_array( $states ) || ! $states ) {
			return $state;
		}

		if ( isset( $states[ $state ] ) ) {
			return $state;
		}

		foreach ( $states as $code => $name ) {
			if ( strcasecmp( (string) $name, $state ) === 0 ) {
				return (string) $code;
			}
		}

		return $state;
	}
}

if ( ! function_exists( 'fhs_normalize_checkout_field' ) ) {
	/**
	 * @param array<string, string> $entry Source address row.
	 */
	function fhs_normalize_checkout_field( $field_key, $value, array $entry ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '/_(country)$/', $field_key ) ) {
			return fhs_normalize_checkout_country( $value );
		}

		if ( preg_match( '/_(state)$/', $field_key ) ) {
			$type        = 0 === strpos( $field_key, 'billing_' ) ? 'billing' : 'shipping';
			$country_key = $type . '_country';
			$country     = fhs_get_saved_address_field( $entry, $country_key );

			return fhs_normalize_checkout_state( $value, $country );
		}

		return $value;
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

if ( ! function_exists( 'fhs_apply_resolved_entry_to_customer' ) ) {
	/**
	 * Write one address type to WC()->customer (billing and shipping stay separate).
	 *
	 * @param string $type billing|shipping
	 */
	function fhs_apply_resolved_entry_to_customer( $type ) {
		if ( ! is_user_logged_in() || ! function_exists( 'WC' ) || ! WC()->customer ) {
			return;
		}

		$entry = fhs_get_resolved_default_entry( $type );

		if ( ! $entry ) {
			return;
		}

		foreach ( $entry as $field_key => $field_value ) {
			if ( ! is_string( $field_key ) || 0 !== strpos( $field_key, $type . '_' ) ) {
				continue;
			}

			$setter = 'set_' . $field_key;
			$value  = fhs_normalize_checkout_field( $field_key, (string) $field_value, $entry );

			if ( is_callable( array( WC()->customer, $setter ) ) ) {
				WC()->customer->{$setter}( wc_clean( $value ) );
			}
		}

		WC()->customer->save();
	}
}

if ( ! function_exists( 'fhs_apply_billing_address_to_customer' ) ) {
	function fhs_apply_billing_address_to_customer() {
		fhs_apply_resolved_entry_to_customer( 'billing' );
	}
}

if ( ! function_exists( 'fhs_apply_shipping_address_to_customer' ) ) {
	function fhs_apply_shipping_address_to_customer() {
		fhs_apply_resolved_entry_to_customer( 'shipping' );
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

		$entry = fhs_get_resolved_default_entry( $type );

		if ( ! $entry ) {
			return $value;
		}

		$saved = fhs_get_saved_address_field( $entry, $input );

		if ( '' === $saved ) {
			return $value;
		}

		return fhs_normalize_checkout_field( $input, $saved, $entry );
	}
}

if ( ! function_exists( 'fhs_get_billing_defaults_for_js' ) ) {
	/**
	 * Normalized billing defaults for client-side restore (after AJAX / country_to_state).
	 *
	 * @return array<string, string>
	 */
	function fhs_get_billing_defaults_for_js() {
		$entry = fhs_get_resolved_default_entry( 'billing' );

		if ( ! $entry ) {
			return array();
		}

		$defaults = array();

		foreach ( $entry as $field_key => $field_value ) {
			if ( ! is_string( $field_key ) || 0 !== strpos( $field_key, 'billing_' ) ) {
				continue;
			}

			$defaults[ $field_key ] = fhs_normalize_checkout_field(
				$field_key,
				(string) $field_value,
				$entry
			);
		}

		return $defaults;
	}
}

if ( ! function_exists( 'fhs_guard_billing_from_shipping_value' ) ) {
	/**
	 * Last line of defence: billing fields must not display shipping session values.
	 */
	function fhs_guard_billing_from_shipping_value( $value, $input ) {
		if ( 0 !== strpos( $input, 'billing_' ) || ! is_user_logged_in() ) {
			return $value;
		}

		if ( fhs_checkout_field_has_posted_value( $input ) ) {
			return $value;
		}

		$billing_entry = fhs_get_resolved_default_entry( 'billing' );

		if ( ! $billing_entry ) {
			return $value;
		}

		$saved = fhs_get_saved_address_field( $billing_entry, $input );

		if ( '' === $saved ) {
			return $value;
		}

		$correct = fhs_normalize_checkout_field( $input, $saved, $billing_entry );

		if ( '' === (string) $correct ) {
			return $value;
		}

		$shipping_key    = str_replace( 'billing_', 'shipping_', $input );
		$shipping_getter = 'get_' . $shipping_key;

		if ( function_exists( 'WC' ) && WC()->customer && is_callable( array( WC()->customer, $shipping_getter ) ) ) {
			$shipping_value = (string) WC()->customer->{$shipping_getter}();

			if ( (string) $value === $shipping_value && (string) $correct !== $shipping_value ) {
				return $correct;
			}
		}

		return $correct;
	}
}

if ( ! function_exists( 'fhs_register_checkout_address_defaults' ) ) {
	function fhs_register_checkout_address_defaults() {
		if ( ! has_filter( 'woocommerce_checkout_get_value', 'fhs_checkout_get_default_address_value' ) ) {
			add_filter( 'woocommerce_checkout_get_value', 'fhs_checkout_get_default_address_value', 20, 2 );
		}

		if ( ! has_filter( 'woocommerce_checkout_get_value', 'fhs_guard_billing_from_shipping_value' ) ) {
			add_filter( 'woocommerce_checkout_get_value', 'fhs_guard_billing_from_shipping_value', 999, 2 );
		}

		if ( ! has_action( 'woocommerce_before_checkout_billing_form', 'fhs_apply_billing_address_to_customer' ) ) {
			// After ThemeHigh / other plugins on this hook (default 10).
			add_action( 'woocommerce_before_checkout_billing_form', 'fhs_apply_billing_address_to_customer', 99 );
		}

		if ( ! has_action( 'woocommerce_before_checkout_shipping_form', 'fhs_apply_shipping_address_to_customer' ) ) {
			add_action( 'woocommerce_before_checkout_shipping_form', 'fhs_apply_shipping_address_to_customer', 99 );
		}
	}
}

fhs_register_checkout_address_defaults();
