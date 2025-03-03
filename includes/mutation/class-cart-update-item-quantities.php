<?php
/**
 * Mutation - updateItemQuantities
 *
 * Registers mutation for updating cart item quantities.
 *
 * @package WPGraphQL\Extensions\WooCommerce\Mutation
 * @since 0.1.0
 */

namespace WPGraphQL\Extensions\WooCommerce\Mutation;

use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Extensions\WooCommerce\Data\Mutation\Cart_Mutation;

/**
 * Class - Cart_Update_Item_Quantities
 */
class Cart_Update_Item_Quantities {
	/**
	 * Registers mutation
	 */
	public static function register_mutation() {
		register_graphql_mutation(
			'updateItemQuantities',
			array(
				'inputFields'         => self::get_input_fields(),
				'outputFields'        => self::get_output_fields(),
				'mutateAndGetPayload' => self::mutate_and_get_payload(),
			)
		);
	}

	/**
	 * Defines the mutation input field configuration
	 *
	 * @return array
	 */
	public static function get_input_fields() {
		return array(
			'items' => array(
				'type'        => array( 'list_of' => 'CartItemQuantityInput' ),
				'description' => __( 'Cart item being updated', 'wp-graphql-woocommerce' ),
			),
		);
	}

	/**
	 * Defines the mutation output field configuration
	 *
	 * @return array
	 */
	public static function get_output_fields() {
		return array(
			'updated' => array(
				'type'    => array( 'list_of' => 'CartItem' ),
				'resolve' => function ( $payload ) {
					$items = array();
					foreach ( $payload['updated'] as $key ) {
						$items[] = WC()->cart->get_cart_item( $key );
					}

					return $items;
				},
			),
			'removed' => array(
				'type'    => array( 'list_of' => 'CartItem' ),
				'resolve' => function ( $payload ) {
					return $payload['removed'];
				},
			),
			'items'   => array(
				'type'    => array( 'list_of' => 'CartItem' ),
				'resolve' => function ( $payload ) {
					$updated = array();
					foreach ( $payload['updated'] as $key ) {
						$updated[] = \WC()->cart->get_cart_item( $key );
					}

					return array_merge( $updated, $payload['removed'] );
				},
			),
		);
	}

	/**
	 * Defines the mutation data modification closure.
	 *
	 * @return callable
	 */
	public static function mutate_and_get_payload() {
		return function( $input, AppContext $context, ResolveInfo $info ) {
			// Confirm "items" exists.
			if ( empty( $input['items'] ) ) {
				throw new UserError( __( 'No item data provided', 'wp-graphql-woocommerce' ) );
			}

			// Confirm "items" is value.
			if ( ! is_array( $input['items'] ) ) {
				throw new UserError( __( 'Provided "items" invalid', 'wp-graphql-woocommerce' ) );
			}

			do_action( 'woocommerce_graphql_before_set_item_quantities', $input['items'], $input, $context, $info );

			// Update quantities. If quantity set to 0, the items in removed.
			$removed       = array();
			$updated       = array();
			$removed_items = array();
			foreach ( $input['items'] as $item ) {
				if ( Cart_Mutation::item_is_valid( $item ) ) {
					$key      = $item['key'];
					$quantity = $item['quantity'];
					if ( 0 === $quantity ) {
						$removed_item    = \WC()->cart->get_cart_item( $key );
						$removed_items[] = $removed_item;
						do_action( 'woocommerce_graphql_before_remove_item', $removed_item, 'update_quantity', $input, $context, $info );
						$removed[ $key ] = \WC()->cart->remove_cart_item( $key );
						do_action( 'woocommerce_graphql_after_remove_item', $removed_item, 'update_quantity', $input, $context, $info );
						continue;
					}
					do_action( 'woocommerce_graphql_before_set_item_quantity', \WC()->cart->get_cart_item( $key ), $input, $context, $info );
					$updated[ $key ] = \WC()->cart->set_quantity( $key, $quantity, true );
					do_action( 'woocommerce_graphql_after_set_item_quantity', \WC()->cart->get_cart_item( $key ), $input, $context, $info );
				}
			}

			// Throw failed.
			try {
				$errors = array_keys(
					array_filter(
						array_merge( $removed, $updated ),
						function( $value ) {
							return ! $value;
						}
					)
				);
				if ( 0 < count( $errors ) ) {
					throw new \Exception(
						sprintf(
							/* translators: %s: Cart item keys */
							__( 'Cart items identified with keys %s failed to update', 'wp-graphql-woocommerce' ),
							implode( ', ', $errors )
						)
					);
				}
			} catch ( \Exception $e ) {
				throw new UserError( $e->getMessage() );
			}

			do_action(
				'woocommerce_graphql_before_set_item_quantities',
				array_keys( $updated ),
				array_keys( $removed ),
				$input,
				$context,
				$info
			);

			return array(
				'removed' => $removed_items,
				'updated' => array_keys( $updated ),
			);
		};
	}
}
