<?php

global $M_options;

$factory = Membership_Plugin::factory();
if ( isset( $_REQUEST['gateway'] ) && isset( $_REQUEST['extra_form'] ) ) {
	$gateway = Membership_Gateway::get_gateway( $_REQUEST['gateway'] );
	if ( $gateway && is_object( $gateway ) && $gateway->haspaymentform == true ) {
		$sub = $factory->get_subscription( $subscription );
		$pricing = $sub->get_pricingarray();
		do_action( 'membership_payment_form_' . $_REQUEST['gateway'], $sub, $pricing, $member->ID );
	}
} else if ( $member->on_sub( $subscription ) ) {


	$sub = $factory->get_subscription( $subscription );
	// Get the coupon
	$coupon = membership_get_current_coupon();

	?><div id='membership-wrapper'>
		<legend><?php echo __( 'Sign up for', 'membership' ), " ", $sub->sub_name() ?></legend>

		<div class="alert">
			<?php printf( __( 'You currently have a subscription for the %s subscription. If you wish to sign up a different subscription then you can do below.', 'membership' ), '<strong>' . $sub->sub_name() . '</strong>' ) ?>
		</div>

		<table class='purchasetable'><?php

			$subs = $this->get_subscriptions();

			foreach ( $subs as $s ) {
				if ( $s->id == $subscription ) {
					continue;
				}
				$sub = $factory->get_subscription( $s->id );
				// Build the pricing array
				$pricing = $sub->get_pricingarray();

				if ( !empty( $pricing ) && !empty( $coupon ) && method_exists( $coupon, 'valid_for_subscription' ) && $coupon->valid_for_subscription( $s->id ) ) {
					$pricing = $coupon->apply_coupon_pricing( $pricing );
				}

				?><tr>
					<td class='detailscolumn'>
						<?php echo $sub->sub_name(); ?>
					</td>
					<td class='pricecolumn'>
						<?php
						$amount = $sub->sub_pricetext();

						if ( !empty( $amount ) ) {
							echo $amount;
						} else {

							$first = $pricing[0];

							if ( !empty( $first ) ) {
								$price = $first['amount'];
								if ( $price == 0 ) {
									$price = "Free";
								} else {

									$M_options = get_option( 'membership_options', array() );

									switch ( $M_options['paymentcurrency'] ) {
										case "USD": $price = "$" . $price;
											break;
										case "GBP": $price = "&pound;" . $price;
											break;
										case "EUR": $price = "&euro;" . $price;
											break;
									}
								}
							}
							echo $price;
						}
						?>
					</td>
					<td class='buynowcolumn'>
						<?php
						if ( !empty( $pricing ) ) {
							do_action( 'membership_purchase_button', $sub, $pricing, $member->ID );
						}
						?>
					</td>
				</tr>
				<?php
				if ( !defined( 'MEMBERSHIP_HIDE_PAYTEXT' ) ) {
					$pricetext = membership_price_in_text( $pricing );
					if ( $pricetext !== false ) {
						?>
						<tr class='pricescolumn'>
							<td colspan='3'>
								<?php
								// Decipher the pricing array and display it
								echo '<strong>' . __( 'You will pay : ', 'membership' ) . '</strong> ' . $pricetext;
								?>
							</td>
						</tr>
						<?php
					}
				}
				?>
				<?php
			}
			?>
		</table>

		<?php
		if ( !defined( 'MEMBERSHIP_HIDE_COUPON_FORM' ) ) {
			if ( !isset( $M_options['show_coupons_form'] ) || $M_options['show_coupons_form'] == 'yes' ) {
				include( membership_dir( 'membershipincludes/includes/coupon.form.php' ) );
			}
		}
		?>

	</div>

	<?php
} else {

	$sub = $factory->get_subscription( $subscription );
	// Get the coupon
	$coupon = membership_get_current_coupon();
	// Build the pricing array
	$pricing = $sub->get_pricingarray();

	if ( !empty( $pricing ) && !empty( $coupon ) && method_exists( $coupon, 'valid_for_subscription' ) && $coupon->valid_for_subscription( $sub->id ) ) {
		$pricing = $coupon->apply_coupon_pricing( $pricing );
	}
	?>
	<div id='membership-wrapper'>
		<legend><?php echo __( 'Sign up for', 'membership' ) . " " . $sub->sub_name(); ?></legend>

		<div class="alert alert-success">
			<?php echo __( 'Please check the details of your subscription below and click on the relevant button to complete the subscription.', 'membership' ); ?>
		</div>

		<table class='purchasetable'>
			<tr>
				<td class='detailscolumn'>
					<?php echo $sub->sub_name(); ?>
				</td>
				<td class='pricecolumn'><?php
					$amount = $sub->sub_pricetext();
					if ( !empty( $amount ) ) {
						echo $amount;
					} else {
						$first = $pricing[0];

						if ( !empty( $first ) ) {
							$price = $first['amount'];
							if ( $price == 0 ) {
								$price = "Free";
							} else {

								$M_options = get_option( 'membership_options', array() );

								switch ( $M_options['paymentcurrency'] ) {
									case "USD": $price = "$" . $price;
										break;
									case "GBP": $price = "&pound;" . $price;
										break;
									case "EUR": $price = "&euro;" . $price;
										break;
								}
							}
						}
						echo $price;
					}
					?>
				</td>
				<td class='buynowcolumn'>
					<?php
					if ( !empty( $pricing ) ) {
						do_action( 'membership_purchase_button', $sub, $pricing, $member->ID );
					}
					?>
				</td>
			</tr>
			<?php
			if ( !defined( 'MEMBERSHIP_HIDE_PAYTEXT' ) ) {
				$pricetext = membership_price_in_text( $pricing );
				if ( $pricetext !== false ) {
					?>
					<tr class='pricescolumn'>
						<td colspan='3'>
							<?php
							// Decipher the pricing array and display it
							echo '<strong>' . __( 'You will pay : ', 'membership' ) . '</strong> ' . $pricetext;
							?>
						</td>
					</tr>
					<?php
				}
			}
			?>
		</table>

		<?php
		if ( !defined( 'MEMBERSHIP_HIDE_COUPON_FORM' ) ) {
			if ( !isset( $M_options['show_coupons_form'] ) || $M_options['show_coupons_form'] == 'yes' ) {
				include( membership_dir( 'membershipincludes/includes/coupon.form.php' ) );
			}
		}
		?>
	</div>
	<?php
}