<?php
/**
 * WPInputObjectType - CartItemQuantityInput
 *
 * @package \WPGraphQL\Extensions\WooCommerce\Type\WPInputObject
 * @since   0.2.0
 */

namespace WPGraphQL\Extensions\WooCommerce\Type\WPInputObject;

/**
 * Class Cart_Item_Quantity_Input
 */
class Cart_Item_Quantity_Input {
	/**
	 * Registers type
	 */
	public static function register() {
		register_graphql_input_type(
			'CartItemQuantityInput',
			array(
				'description' => __( 'Cart item quantity', 'wp-graphql-woocommerce' ),
				'fields'      => array(
					'key'      => array(
						'type'        => array( 'non_null' => 'ID' ),
						'description' => __( 'Cart item being updated', 'wp-graphql-woocommerce' ),
					),
					'quantity' => array(
						'type'        => array( 'non_null' => 'Int' ),
						'description' => __( 'Cart item\'s new quantity', 'wp-graphql-woocommerce' ),
					),
				),
			)
		);
	}
}
