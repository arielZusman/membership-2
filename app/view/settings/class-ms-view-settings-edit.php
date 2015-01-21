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
 * Renders Membership Plugin Settings.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @uses MS_Helper_Html Helper used to create form elements and vertical navigation.
 *
 * @since 1.0
 *
 * @return object
 */
class MS_View_Settings_Edit extends MS_View {

	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * Creates a wrapper 'ms-wrap' HTML element to contain content and navigation. The content inside
	 * the navigation gets loaded with dynamic method calls.
	 * e.g. if key is 'settings' then render_settings() gets called, if 'bob' then render_bob().
	 *
	 * @todo Could use callback functions to call dynamic methods from within the helper, thus
	 * creating the navigation with a single method call and passing method pointers in the $tabs array.
	 *
	 * @since 4.0.0
	 *
	 * @return object
	 */
	public function to_html() {
		// Setup navigation tabs.
		$tabs = $this->data['tabs'];
		$desc = array();

		// A "Reset" button that can be added via URL param
		// Intentionally not translated (purpose is dev/testing)
		if ( isset( $_GET['reset'] ) ) {
			$reset_url = admin_url( 'admin.php?page=protected-content-settings&reset=1' );
			$reset_url = add_query_arg(
				MS_Model_Upgrade::get_reset_token(),
				$reset_url
			);
			$cancel_url = remove_query_arg( 'reset' );
			$desc[] = sprintf(
				'<div class="error" style="width:600px;margin:20px auto;text-align:center"><p><b>%1$s</b></p><hr />%2$s</div>',
				'Careful: This will completely erase all your Protected Content settings and details!',
				sprintf(
					'<form method="POST" action="%s" style="padding:20px 0">' .
					'<label style="line-height:28px">' .
					'<input type="checkbox" name="confirm" value="reset" /> Yes, reset everything!' .
					'</label><p>' .
					'<button class="button-primary">Do it!</button> ' .
					'<a href="%s" class="button">Cancel</a>' .
					'</p></form>',
					$reset_url,
					$cancel_url
				)
			);
		}

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Protect Content Settings', MS_TEXT_DOMAIN ),
					'title_icon_class' => 'wpmui-fa wpmui-fa-cog',
					'desc' => $desc,
				)
			);
			$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );

			// Call the appropriate form to render.
			$tab_name = str_replace( '-', '_', $active_tab );
			$callback_name = 'render_tab_' . $tab_name;
			$render_callback = apply_filters(
				'ms_view_settings_edit_render_callback',
				array( $this, $callback_name ),
				$active_tab,
				$this->data
			);
			?>
			<div class="ms-settings ms-settings-<?php echo esc_attr( $tab_name ); ?>">
				<?php
				$html = call_user_func( $render_callback );
				$html = apply_filters( 'ms_view_settings_' . $callback_name, $html );
				echo '' . $html;
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               GENERAL
	 * ====================================================================== */

	public function render_tab_general() {
		$settings = $this->data['settings'];

		$fields = array(
			'plugin_enabled' => array(
				'id' => 'plugin_enabled',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title' => __( 'Content Protection', MS_TEXT_DOMAIN ),
				'desc' => __( 'This setting toggles the content protection on this site.', MS_TEXT_DOMAIN ),
				'value' => MS_Plugin::is_enabled(),
				'class' => 'ms-half',
				'data_ms' => array(
					'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
					'setting' => 'plugin_enabled',
				),
			),

			'hide_admin_bar' => array(
				'id' => 'hide_admin_bar',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title' => __( 'Hide admin toolbar', MS_TEXT_DOMAIN ),
				'desc' => __( 'Hide the admin toolbar for non administrator users.', MS_TEXT_DOMAIN ),
				'value' => $settings->hide_admin_bar,
				'class' => 'ms-half',
				'data_ms' => array(
					'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
					'setting' => 'hide_admin_bar',
				),
			),

		);

		$fields = apply_filters( 'ms_view_settings_prepare_general_fields', $fields );
		$setup = MS_Factory::create( 'MS_View_Settings_Setup' );

		ob_start();

		MS_Helper_Html::settings_tab_header();
		?>

		<form action="" method="post">
			<div class="cf">
				<?php
				MS_Helper_Html::html_element( $fields['plugin_enabled'] );
				MS_Helper_Html::html_element( $fields['hide_admin_bar'] );
				?>
			</div>
			<?php
			MS_Helper_Html::html_separator();
			MS_Helper_Html::html_element( $setup->to_html() );
			?>
		</form>
		<?php
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               PAYMENT
	 * ====================================================================== */

	public function render_tab_payment() {
		$view = MS_Factory::create( 'MS_View_Settings_Payment' );

		ob_start();
		?>
		<div id="ms-payment-settings-wrapper">
			<?php $view->render(); ?>
		</div>
		<?php
		echo '' . ob_get_clean();
	}

	/* ====================================================================== *
	 *                               PROTECTION MESSAGE
	 * ====================================================================== */

	public function render_tab_messages_protection() {
		$settings = $this->data['settings'];
		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_PROTECTION_MSG;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'content' => array(
				'editor' => array(
					'id' => 'content',
					'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
					'title' => __( 'Message displayed when not having access to a protected content.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_CONTENT ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
				),
				'save' => array(
					'id' => 'save_content',
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Save', MS_TEXT_DOMAIN ),
					'class' => 'button-primary',
					'data_ms' => array(
						'type' => 'content',
						'action' => $action,
						'_wpnonce' => $nonce,
					),
				),
			),

			'shortcode' => array(
				'editor' => array(
					'id' => 'shortcode',
					'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
					'title' => __( 'Message displayed when not having access to a protected shortcode content.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_SHORTCODE ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
				),
				'save' => array(
					'id' => 'save_content',
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Save', MS_TEXT_DOMAIN ),
					'class' => 'button-primary',
					'data_ms' => array(
						'type' => 'shortcode',
						'action' => $action,
						'_wpnonce' => $nonce,
					),
				),
			),

			'more_tag' => array(
				'editor' => array(
					'id' => 'more_tag',
					'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
					'title' => __( 'Message displayed when not having access to a protected content under more tag.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_MORE_TAG ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
				),
				'save' => array(
					'id' => 'save_content',
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Save', MS_TEXT_DOMAIN ),
					'class' => 'button-primary',
					'data_ms' => array(
						'type' => 'more_tag',
						'action' => $action,
						'_wpnonce' => $nonce,
					),
				),
			),
		);

		$fields = apply_filters( 'ms_view_settings_prepare_pages_fields', $fields );

		$membership = $this->data['membership'];
		$rule_more_tag = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG );
		$has_more = $rule_more_tag->get_rule_value( MS_Model_Rule_More::CONTENT_ID );

		ob_start();

		MS_Helper_Html::settings_tab_header(
			array( 'title' => __( 'Protection Messages', MS_TEXT_DOMAIN ) )
		);
		?>

		<form class="ms-form" action="" method="post">
			<?php
			MS_Helper_Html::settings_box(
				$fields['content'],
				__( 'Content protection message', MS_TEXT_DOMAIN ),
				'',
				'open'
			);

			MS_Helper_Html::settings_box(
				$fields['shortcode'],
				__( 'Shortcode protection message', MS_TEXT_DOMAIN ),
				'',
				'open'
			);

			if ( $has_more ) {
				MS_Helper_Html::settings_box(
					$fields['more_tag'],
					__( 'More tag protection message', MS_TEXT_DOMAIN ),
					'',
					'open'
				);
			}
			?>
		</form>
		<?php
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               AUTOMATED MESSAGES
	 * ====================================================================== */

	public function render_tab_messages_automated() {
		$comm = $this->data['comm'];

		$action = MS_Controller_Communication::AJAX_ACTION_UPDATE_COMM;
		$nonce = wp_create_nonce( $action );
		$comm_titles = MS_Model_Communication::get_communication_type_titles();

		$fields = array(
			'comm_type' => array(
				'id' => 'comm_type',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => @$comm->type,
				'field_options' => $comm_titles,
			),

			'switch_comm_type' => array(
				'id' => 'switch_comm_type',
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Load Email', MS_TEXT_DOMAIN ),
			),

			'type' => array(
				'id' => 'type',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => @$comm->type,
			),

			'enabled' => array(
				'id' => 'enabled',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => @$comm->enabled,
				'data_ms' => array(
					'type' => @$comm->type,
					'field' => 'enabled',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'period_unit' => array(
				'id' => 'period_unit',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Period after/before', MS_TEXT_DOMAIN ),
				'value' => @$comm->period['period_unit'],
			),

			'period_type' => array(
				'id' => 'period_type',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => @$comm->period['period_type'],
				'field_options' => MS_Helper_Period::get_periods(),
			),

			'subject' => array(
				'id' => 'subject',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Message Subject', MS_TEXT_DOMAIN ),
				'value' => @$comm->subject,
				'class' => 'ms-comm-subject widefat',
			),

			'email_body' => array(
				'id' => 'email_body',
				'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
				'value' => @$comm->description,
				'field_options' => array(
					'media_buttons' => false,
					'editor_class' => 'wpmui-ajax-update',
				),
			),

			'cc_enabled' => array(
				'id' => 'cc_enabled',
				'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'title' => __( 'Send copy to Administrator', MS_TEXT_DOMAIN ),
				'value' => @$comm->cc_enabled,
			),

			'cc_email' => array(
				'id' => 'cc_email',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => @$comm->cc_email,
				'field_options' => MS_Model_Member::get_admin_user_emails(),
			),

			'save_email' => array(
				'id' => 'save_email',
				'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			),

			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'save_comm',
			),

			'nonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( 'save_comm' ),
			),

			'load_action' => array(
				'id' => 'load_action',
				'name' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'load_action',
			),

			'load_nonce' => array(
				'id' => '_wpnonce1',
				'name' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( 'load_action' ),
			),
		);

		$fields = apply_filters( 'ms_view_settings_prepare_messages_automated_fields', $fields );

		ob_start();

		MS_Helper_Html::settings_tab_header(
			array( 'title' => __( 'Automated Messages', MS_TEXT_DOMAIN ) )
		);
		?>

		<form id="ms-comm-type-form" action="" method="post">
			<?php MS_Helper_Html::html_element( $fields['load_action'] ); ?>
			<?php MS_Helper_Html::html_element( $fields['load_nonce'] ); ?>
			<?php MS_Helper_Html::html_element( $fields['comm_type'] ); ?>
			<?php MS_Helper_Html::html_element( $fields['switch_comm_type'] ); ?>
		</form>

		<?php MS_Helper_Html::html_separator(); ?>

		<form action="" method="post" class="ms-editor-form">
			<?php
			MS_Helper_Html::html_element( $fields['action'] );
			MS_Helper_Html::html_element( $fields['nonce'] );
			MS_Helper_Html::html_element( $fields['type'] );

			if ( is_a( $comm, 'MS_Model_Communication' ) ) {
				printf(
					'<h3>%1$s %2$s: %3$s</h3><div class="ms-description" style="margin-bottom:20px;">%4$s</div>',
					esc_html( $comm_titles[ $comm->type ] ),
					__( 'Message', MS_TEXT_DOMAIN ),
					MS_Helper_Html::html_element( $fields['enabled'], true ),
					$comm->get_description()
				);

				if ( $comm->period_enabled ) {
					echo '<div class="ms-period-wrapper">';
					MS_Helper_Html::html_element( $fields['period_unit'] );
					MS_Helper_Html::html_element( $fields['period_type'] );
					echo '</div>';
				}
			}

			MS_Helper_Html::html_element( $fields['subject'] );
			MS_Helper_Html::html_element( $fields['email_body'] );

			MS_Helper_Html::html_element( $fields['cc_enabled'] );
			echo ' &nbsp; ';
			MS_Helper_Html::html_element( $fields['cc_email'] );
			MS_Helper_Html::html_separator();
			MS_Helper_Html::html_element( $fields['save_email'] );
			?>
		</form>
		<?php
		/**
		 * Print JS details for the custom TinyMCE "Insert Variable" button
		 *
		 * @see class-ms-controller-settings.php (function add_mce_buttons)
		 * @see ms-view-settings-automated-msg.js
		 */
		$var_button = array(
			'title' => __( 'Insert Membership Variables', MS_TEXT_DOMAIN ),
			'items' => @$comm->comm_vars,
		);
		printf(
			'<script>window.ms_data.var_button = %1$s;window.ms_data.lang_confirm = %2$s</script>',
			json_encode( $var_button ),
			json_encode(
				__( 'You have made changes that are not saved yet. Do you want to discard those changes?', MS_TEXT_DOMAIN )
			)
		);

		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               IMPORT
	 * ====================================================================== */

	public function render_tab_import() {
		$export_action = MS_Controller_Import::ACTION_EXPORT;
		$import_action = MS_Controller_Import::ACTION_PREVIEW;
		$messages = $this->data['message'];

		$preview = false;
		if ( isset( $messages['preview'] ) ) {
			$preview = $messages['preview'];
		}

		$export_fields = array(
			'export' => array(
				'id' => 'btn_export',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Generate Export', MS_TEXT_DOMAIN ),
				'desc' => __(
					'Generate an export file with the current membership settings. ' .
					'<em>Note that this is not a full backup of the plugin settings.</em>',
					MS_TEXT_DOMAIN
				),
			),
			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $export_action,
			),
			'nonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $export_action ),
			),
		);

		$file_field = array(
			'id' => 'upload',
			'type' => MS_Helper_Html::INPUT_TYPE_FILE,
			'title' => __( 'From export file', MS_TEXT_DOMAIN ),
		);
		$import_options = array(
			'file' => array(
				'text' => MS_Helper_Html::html_element( $file_field, true ),
				'disabled' => ! MS_Model_Import_File::present(),
			),
			'membership' => array(
				'text' => __( 'Membership (WPMU Dev)', MS_TEXT_DOMAIN ),
				'disabled' => ! MS_Model_Import_Membership::present(),
			),
		);

		$sel_source = 'file';
		if ( isset( $_POST['import_source'] )
			&& isset( $import_options[ $_POST['import_source'] ] )
		) {
			$sel_source = $_POST['import_source'];
		}

		$import_fields = array(
			'source' => array(
				'id' => 'import_source',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
				'title' => __( 'Choose an import source', MS_TEXT_DOMAIN ),
				'field_options' => $import_options,
				'value' => $sel_source,
			),
			'import' => array(
				'id' => 'btn_import',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Preview Import', MS_TEXT_DOMAIN ),
				'desc' => __(
					'Import data into this installation.',
					MS_TEXT_DOMAIN
				),
			),
			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $import_action,
			),
			'nonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $import_action ),
			),
		);

		ob_start();

		MS_Helper_Html::settings_tab_header(
			array( 'title' => __( 'Import Tool', MS_TEXT_DOMAIN ) )
		);
		?>

		<div>
			<?php if ( $preview ) : ?>
				<form action="" method="post">
					<?php echo '' . $preview; ?>
				</form>
			<?php else : ?>
				<form action="" method="post" enctype="multipart/form-data">
					<?php MS_Helper_Html::settings_box(
						$import_fields,
						__( 'Import data', MS_TEXT_DOMAIN )
					); ?>
				</form>
				<form action="" method="post">
					<?php MS_Helper_Html::settings_box(
						$export_fields,
						__( 'Export data', MS_TEXT_DOMAIN )
					); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

}