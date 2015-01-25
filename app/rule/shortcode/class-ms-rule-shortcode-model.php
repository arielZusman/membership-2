<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
*/

/**
 * Membership Shortcode Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Rule_Shortcode_Model extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_SHORTCODE;

	/**
	 * Protect content shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const PROTECT_CONTENT_SHORTCODE = 'ms-protect-content';

	/**
	 * Set-up the Rule
	 *
	 * @since  1.1.0
	 */
	static public function prepare_class() {
		// Register the tab-output handler for the admin side
		MS_Factory::load( 'MS_Rule_Shortcode_View' )->register();
	}

	/**
	 * Verify access to the current content.
	 *
	 * This rule will return NULL (not relevant), because shortcodes are
	 * replaced inside the page content instead of protecting the whole page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The content id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id = null ) {
		return apply_filters(
			'ms_rule_shortcode_model_has_access',
			null,
			$id,
			$this
		);
	}

	/**
	 * Set initial protection.
	 *
	 * Add [ms-protect-content] shortcode to protect membership content inside post.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $ms_relationship The user membership details.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );

		$this->membership_id = $ms_relationship->membership_id;

		add_shortcode(
			self::PROTECT_CONTENT_SHORTCODE,
			array( $this, 'protect_content_shortcode')
		);

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_SHORTCODE ) ) {
			global $shortcode_tags;
			$exclude = MS_Helper_Shortcode::get_membership_shortcodes();

			foreach ( $shortcode_tags as $shortcode => $callback_funciton ) {
				if ( in_array( $shortcode, $exclude ) ) {
					continue;
				}
				if ( ! parent::has_access( $shortcode ) ) {
					$shortcode_tags[ $shortcode ] = array(
						&$this,
						'do_protected_shortcode',
					);
				}
			}
		}
	}

	/**
	 * Do protected shortcode [do_protected_shortcode].
	 *
	 * This shortcode is executed to replace a protected shortcode.
	 *
	 *  @since 1.0.0
	 */
	public function do_protected_shortcode() {
		$content = null;
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$msg = $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_SHORTCODE );

		if ( $msg ) {
			$content = $msg;
		} else {
			$content = __( 'Shortcode content protected.', MS_TEXT_DOMAIN );
		}

		return apply_filters(
			'ms_model_shortcode_do_protected_shortcode_content',
			$content,
			$this
		);
	}

	/**
	 * Do membership content protection shortcode.
	 *
	 * self::PROTECT_CONTENT_SHORTCODE
	 *
	 * Verify if content is protected comparing to membership_id.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts The shortcode attributes.
	 * @param string $content The content inside the shortcode.
	 * @param string $code The shortcode code.
	 * @return string The shortcode output
	 */
	public function protect_content_shortcode( $atts, $content = null, $code = '' ) {
		$atts = apply_filters(
			'ms_model_shortcode_protect_content_shortcode_atts',
			shortcode_atts(
				array(
					'id' => '',
					'access' => true,
					'silent' => false,
					'msg' => false,
				),
				$atts
			)
		);
		extract( $atts );

		$membership_ids = explode( ',', $id );

		if ( $silent ) {
			$msg = '';
		} else {
			if ( ! is_string( $msg ) || ! strlen( $msg ) ) {
				$settings = MS_Factory::load( 'MS_Model_Settings' );
				$msg = $settings->get_protection_message(
					MS_Model_Settings::PROTECTION_MSG_SHORTCODE
				);
			}
		}

		$access = WDev()->is_true( $access );

		if ( ! $access ) {
			// No access to member of membership_ids

			if ( $this->is_member_of( $membership_ids ) ) {
				// User belongs to these memberships and therefore cannot see
				// this content...

				if ( $silent ) {
					// Silent protection: Do not show a message, simply hide it
					$content = '';
				} else {
					$content = '<div class="ms-protection-msg">';
					if ( ! empty( $msg ) ) {
						$content .= $msg;
					} else {
						$membership_names = MS_Model_Membership::get_membership_names(
							array( 'post__in' => $membership_ids )
						);
						$content .= __( 'No access to members of: ', MS_TEXT_DOMAIN );
						$content .= implode( ', ', $membership_names );
					}
					$content .= '</div>';
				}
			}
		} else {
			// Give access to member of membership_ids

			if ( ! $this->is_member_of( $membership_ids ) ) {
				// User does not belong to these memberships and therefore
				// cannot see this content...

				if ( $silent ) {
					// Silent protection: Do not show a message, simply hide it
					$content = '';
				} else {
					$content = '<div class="ms-protection-msg">';
					if ( ! empty( $msg ) ) {
						$content .= $msg;
					} else {
						$membership_names = MS_Model_Membership::get_membership_names(
							array( 'post__in' => $membership_ids )
						);
						$content .= __( 'Content protected to members of: ', MS_TEXT_DOMAIN );
						$content .= implode( ', ', $membership_names );
					}
					$content .= '</div>';
				}
			}
		}

		return apply_filters(
			'ms_rule_shortcode_model_protect_content_shortcode_content',
			do_shortcode( $content ),
			$atts,
			$content,
			$code,
			$this
		);
	}

	/**
	 * Returns true when the current user is a member of one of the specified
	 * memberships.
	 *
	 * @since  1.0.4.2
	 *
	 * @return bool
	 */
	protected function is_member_of( $ids ) {
		$result = false;

		if ( empty( $ids ) ) {
			$result = true;
		} else {
			if ( ! is_array( $ids ) ) {
				$ids = array( $ids );
			}

			if ( in_array( $this->membership_id, $ids ) ) {
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * Get the total content count.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args. Not used.
	 * @return int The total content count.
	 */
	public function get_content_count( $args = null ) {
		$args['posts_per_page'] = 0;
		$args['offset'] = false;
		$items = $this->get_contents( $args );
		$count = count( $items );

		return apply_filters(
			'ms_rule_shortcode_model_get_content_count',
			$count,
			$this
		);
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 * @param $args The filter args
	 *
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		global $shortcode_tags;

		$exclude = MS_Helper_Shortcode::get_membership_shortcodes();
		$contents = array();

		foreach ( $shortcode_tags as $key => $function ) {
			if ( in_array( $key, $exclude ) ) {
				continue;
			}

			// Search the shortcode-tag...
			if ( ! empty( $args['s'] ) ) {
				if ( stripos( $key, $args['s'] ) === false ) {
					continue;
				}
			}

			$contents[ $key ] = new StdClass();
			$contents[ $key ]->id = $key;
			$contents[ $key ]->name = "[$key]";
			$contents[ $key ]->type = $this->rule_type;
			$contents[ $key ]->access = $this->get_rule_value( $key );
		}

		$filter = $this->get_exclude_include( $args );
		if ( is_array( $filter->include ) ) {
			$contents = array_intersect_key( $contents, array_flip( $filter->include ) );
		} elseif ( is_array( $filter->exclude ) ) {
			$contents = array_diff_key( $contents, array_flip( $filter->exclude ) );
		}

		if ( ! empty( $args['posts_per_page'] ) ) {
			$total = $args['posts_per_page'];
			$offset = ! empty( $args['offset'] ) ? $args['offset'] : 0;
			$contents = array_slice( $contents, $offset, $total );
		}

		return apply_filters(
			'ms_rule_shortcode_model_get_contents',
			$contents
		);
	}

}