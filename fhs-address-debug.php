<?php
/**
 * On-page address / state debug for checkout.
 *
 * Usage (any logged-in user — for test customers):
 *   /checkout/?fhs_address_debug=1
 *   Turn off: /checkout/?fhs_address_debug=0
 *
 * Writes snapshot (if writable): fhs-address-debug-snapshot.json in this folder.
 *
 * @package FHS_Checkout
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'fhs_address_debug_is_enabled' ) ) {
	function fhs_address_debug_is_enabled() {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return false;
		}

		if ( isset( $_GET['fhs_address_debug'] ) ) {
			$flag = sanitize_text_field( wp_unslash( $_GET['fhs_address_debug'] ) );
			if ( '0' === $flag || 'off' === $flag ) {
				WC()->session->set( 'fhs_address_debug', 0 );
				return false;
			}
			if ( '1' === $flag || 'on' === $flag ) {
				WC()->session->set( 'fhs_address_debug', 1 );
				return true;
			}
		}

		return (bool) WC()->session->get( 'fhs_address_debug' );
	}
}

if ( ! function_exists( 'fhs_address_debug_collect_snapshot' ) ) {
	/**
	 * @return array<string, mixed>
	 */
	function fhs_address_debug_collect_snapshot() {
		$user_id = get_current_user_id();
		$book    = fhs_get_thwma_address_book();
		$raw     = get_user_meta( $user_id, 'thwma_custom_address', true );

		$state_fields = array( 'billing_state', 'shipping_state', 'billing_country', 'shipping_country', 'billing_city', 'shipping_city', 'billing_postcode', 'shipping_postcode' );

		$snapshot = array(
			'generated_at'   => gmdate( 'c' ),
			'user_id'          => $user_id,
			'is_ajax'          => wp_doing_ajax(),
			'request_uri'      => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
			'address_book'     => array(
				'default_billing'  => $book['default_billing'],
				'default_shipping' => $book['default_shipping'],
				'billing_keys'     => array_keys( $book['billing'] ),
				'shipping_keys'    => array_keys( $book['shipping'] ),
			),
			'default_entries' => array(),
			'state_resolution' => array(),
			'user_meta_direct' => array(),
			'wc_customer'      => array(),
			'checkout_get_value' => array(),
			'posted'           => array(),
			'valid_wc_states'  => array(),
		);

		foreach ( array( 'billing', 'shipping' ) as $type ) {
			$book_entry    = fhs_get_default_address_entry( $type );
			$resolved      = fhs_get_resolved_default_entry( $type );
			$key           = $book[ 'default_' . $type ];

			$snapshot['default_entries'][ $type ] = array(
				'address_key'      => $key,
				'source'           => $book_entry ? 'address_book' : ( $resolved ? 'wc_user_meta' : 'none' ),
				'entry_keys'       => array_keys( $resolved ),
				'entry'            => $resolved,
			);
		}

		foreach ( $state_fields as $field_key ) {
			$type  = 0 === strpos( $field_key, 'billing_' ) ? 'billing' : 'shipping';
			$entry = fhs_get_resolved_default_entry( $type );
			$raw   = fhs_get_saved_address_field( $entry, $field_key );

			$snapshot['state_resolution'][ $field_key ] = array(
				'entry_prefixed'  => isset( $entry[ $field_key ] ) ? (string) $entry[ $field_key ] : null,
				'entry_short'     => isset( $entry[ preg_replace( '/^(billing|shipping)_/', '', $field_key ) ] )
					? (string) $entry[ preg_replace( '/^(billing|shipping)_/', '', $field_key ) ]
					: null,
				'fhs_get_saved'   => $raw,
				'normalized'      => $entry ? fhs_normalize_checkout_field( $field_key, $raw, $entry ) : $raw,
				'has_posted'      => fhs_checkout_field_has_posted_value( $field_key ),
			);
		}

		foreach ( $state_fields as $field_key ) {
			$snapshot['user_meta_direct'][ $field_key ] = (string) get_user_meta( $user_id, $field_key, true );
		}

		if ( WC()->customer ) {
			foreach ( $state_fields as $field_key ) {
				$getter = 'get_' . $field_key;
				if ( is_callable( array( WC()->customer, $getter ) ) ) {
					$snapshot['wc_customer'][ $field_key ] = (string) WC()->customer->{$getter}();
				}
			}
		}

		if ( function_exists( 'WC' ) && WC()->checkout() ) {
			foreach ( $state_fields as $field_key ) {
				$snapshot['checkout_get_value'][ $field_key ] = (string) WC()->checkout()->get_value( $field_key );
			}
		}

		if ( ! empty( $_POST['post_data'] ) ) {
			parse_str( wp_unslash( $_POST['post_data'] ), $parsed );
			foreach ( $state_fields as $field_key ) {
				if ( isset( $parsed[ $field_key ] ) ) {
					$snapshot['posted']['post_data'][ $field_key ] = (string) $parsed[ $field_key ];
				}
			}
		}

		foreach ( $state_fields as $field_key ) {
			if ( isset( $_POST[ $field_key ] ) ) {
				$snapshot['posted']['direct'][ $field_key ] = (string) wc_clean( wp_unslash( $_POST[ $field_key ] ) );
			}
		}

		$countries = array(
			$snapshot['wc_customer']['billing_country'] ?? '',
			$snapshot['wc_customer']['shipping_country'] ?? '',
			$snapshot['checkout_get_value']['billing_country'] ?? '',
			$snapshot['checkout_get_value']['shipping_country'] ?? '',
		);

		if ( function_exists( 'WC' ) && WC()->countries ) {
			foreach ( array_unique( array_filter( $countries ) ) as $country ) {
				$states = WC()->countries->get_states( $country );
				$snapshot['valid_wc_states'][ $country ] = is_array( $states ) ? $states : array();
			}
		}

		$snapshot['raw_meta_type'] = gettype( $raw );
		if ( is_array( $raw ) ) {
			$snapshot['raw_meta_top_keys'] = array_keys( $raw );
		}

		return $snapshot;
	}
}

if ( ! function_exists( 'fhs_address_debug_write_snapshot_file' ) ) {
	function fhs_address_debug_write_snapshot_file( array $snapshot ) {
		$path = __DIR__ . '/fhs-address-debug-snapshot.json';

		if ( ! is_writable( __DIR__ ) ) {
			return array(
				'ok'    => false,
				'path'  => $path,
				'error' => 'Directory is not writable by PHP.',
			);
		}

		$written = file_put_contents(
			$path,
			wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_put_contents_file_put_contents
		);

		return array(
			'ok'   => false !== $written,
			'path' => $path,
			'bytes' => false !== $written ? $written : 0,
		);
	}
}

if ( ! function_exists( 'fhs_address_debug_render_panel' ) ) {
	function fhs_address_debug_render_panel() {
		if ( ! fhs_address_debug_is_enabled() || ! is_checkout() ) {
			return;
		}

		$snapshot = fhs_address_debug_collect_snapshot();
		$file     = fhs_address_debug_write_snapshot_file( $snapshot );
		$json     = wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

		$file_note = $file['ok']
			? sprintf( 'Snapshot written: %s (%d bytes)', $file['path'], (int) $file['bytes'] )
			: sprintf( 'Could not write file: %s — %s', $file['path'], $file['error'] ?? 'unknown error' );
		?>
		<div id="fhs-address-debug-panel" style="margin:1em 0;padding:1em;background:#1e1e1e;color:#e0e0e0;font:13px/1.5 monospace;border:3px solid #f0ad4e;border-radius:6px;max-height:70vh;overflow:auto;">
			<p style="margin:0 0 .75em;color:#f0ad4e;font:bold 14px sans-serif;">
				FHS address debug (user #<?php echo (int) get_current_user_id(); ?>) — use <code>?fhs_address_debug=0</code> to turn off.
			</p>
			<p style="margin:0 0 .75em;color:#9cdcfe;font:12px sans-serif;"><?php echo esc_html( $file_note ); ?></p>
			<pre style="margin:0;white-space:pre-wrap;word-break:break-word;"><?php echo esc_html( $json ); ?></pre>
		</div>
		<?php
	}
}

if ( fhs_address_debug_is_enabled() ) {
	add_action( 'woocommerce_before_checkout_form', 'fhs_address_debug_render_panel', 1 );
}
