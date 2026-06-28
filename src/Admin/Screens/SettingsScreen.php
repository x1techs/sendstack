<?php
/**
 * Settings admin screen.
 *
 * @package SendStack\Admin\Screens
 * @since   1.0.0
 */

namespace SendStack\Admin\Screens;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the SMTP provider settings form and processes saves.
 *
 * This screen owns the primary connection config for v1.0. Additional provider
 * tabs (Gmail, SendGrid, Mailgun) are wired in Feature 8.
 *
 * @since 1.0.0
 */
class SettingsScreen extends AbstractScreen {

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string {
		return 'sendstack-settings';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function title(): string {
		return __( 'Settings', 'sendstack' );
	}

	/**
	 * Handle POST, then render the settings form.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function content(): void {
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && $this->verify_nonce( 'sendstack_save_settings' ) ) {
			$this->handle_save();
		}

		$settings    = (array) get_option( 'sendstack_settings', array() );
		$connections = isset( $settings['connections']['smtp'] ) ? (array) $settings['connections']['smtp'] : array();

		settings_errors( 'sendstack' );
		?>

		<div class="sstk-provider-tabs">
			<a href="#" class="sstk-provider-tab sstk-provider-tab--active"><?php esc_html_e( 'SMTP', 'sendstack' ); ?></a>
			<?php /* Additional provider tabs added in Feature 8. */ ?>
		</div>

		<div class="sstk-settings-form">
			<form method="post" action="">
				<?php $this->nonce_field( 'sendstack_save_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="sendstack-smtp-host"><?php esc_html_e( 'SMTP Host', 'sendstack' ); ?></label>
						</th>
						<td>
							<input type="text" id="sendstack-smtp-host"
								name="sendstack_smtp[host]"
								value="<?php echo esc_attr( $connections['host'] ?? '' ); ?>"
								class="regular-text"
								placeholder="smtp.example.com">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sendstack-smtp-port"><?php esc_html_e( 'Port', 'sendstack' ); ?></label>
						</th>
						<td>
							<input type="number" id="sendstack-smtp-port"
								name="sendstack_smtp[port]"
								value="<?php echo esc_attr( (string) ( $connections['port'] ?? 587 ) ); ?>"
								class="small-text"
								min="1" max="65535">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sendstack-smtp-encryption"><?php esc_html_e( 'Encryption', 'sendstack' ); ?></label>
						</th>
						<td>
							<select id="sendstack-smtp-encryption" name="sendstack_smtp[encryption]">
								<option value="tls" <?php selected( $connections['encryption'] ?? 'tls', 'tls' ); ?>><?php esc_html_e( 'TLS (port 587)', 'sendstack' ); ?></option>
								<option value="ssl" <?php selected( $connections['encryption'] ?? 'tls', 'ssl' ); ?>><?php esc_html_e( 'SSL (port 465)', 'sendstack' ); ?></option>
								<option value="none" <?php selected( $connections['encryption'] ?? 'tls', 'none' ); ?>><?php esc_html_e( 'None', 'sendstack' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sendstack-smtp-username"><?php esc_html_e( 'Username', 'sendstack' ); ?></label>
						</th>
						<td>
							<input type="text" id="sendstack-smtp-username"
								name="sendstack_smtp[username]"
								value="<?php echo esc_attr( $connections['username'] ?? '' ); ?>"
								class="regular-text"
								autocomplete="username">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sendstack-smtp-password"><?php esc_html_e( 'Password', 'sendstack' ); ?></label>
						</th>
						<td>
							<input type="password" id="sendstack-smtp-password"
								name="sendstack_smtp[password]"
								value=""
								class="regular-text"
								autocomplete="new-password"
								placeholder="<?php esc_attr_e( 'Leave blank to keep current password', 'sendstack' ); ?>">
							<button type="button" class="button sstk-toggle-password"><?php esc_html_e( 'Show', 'sendstack' ); ?></button>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sendstack-smtp-from-email"><?php esc_html_e( 'From Email', 'sendstack' ); ?></label>
						</th>
						<td>
							<input type="email" id="sendstack-smtp-from-email"
								name="sendstack_smtp[from_email]"
								value="<?php echo esc_attr( $connections['from_email'] ?? '' ); ?>"
								class="regular-text">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sendstack-smtp-from-name"><?php esc_html_e( 'From Name', 'sendstack' ); ?></label>
						</th>
						<td>
							<input type="text" id="sendstack-smtp-from-name"
								name="sendstack_smtp[from_name]"
								value="<?php echo esc_attr( $connections['from_name'] ?? '' ); ?>"
								class="regular-text">
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Settings', 'sendstack' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize and persist the submitted SMTP settings.
	 *
	 * Merges into the existing sendstack_settings option so other top-level keys
	 * (provider, logs_config, etc.) are preserved across saves.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$raw = isset( $_POST['sendstack_smtp'] ) ? (array) $_POST['sendstack_smtp'] : array();
		// phpcs:enable

		$allowed_encryptions = array( 'tls', 'ssl', 'none' );
		$encryption = sanitize_text_field( wp_unslash( $raw['encryption'] ?? 'tls' ) );
		if ( ! in_array( $encryption, $allowed_encryptions, true ) ) {
			$encryption = 'tls';
		}

		$port = absint( $raw['port'] ?? 587 );
		if ( 0 === $port ) {
			$port = 587;
		}

		$sanitized = array(
			'host'       => sanitize_text_field( wp_unslash( $raw['host'] ?? '' ) ),
			'port'       => $port,
			'encryption' => $encryption,
			'username'   => sanitize_text_field( wp_unslash( $raw['username'] ?? '' ) ),
			'from_email' => sanitize_email( wp_unslash( $raw['from_email'] ?? '' ) ),
			'from_name'  => sanitize_text_field( wp_unslash( $raw['from_name'] ?? '' ) ),
		);

		// Preserve the stored password when the field is submitted blank.
		$settings = (array) get_option( 'sendstack_settings', array() );
		$new_password = sanitize_text_field( wp_unslash( $raw['password'] ?? '' ) );
		if ( '' !== $new_password ) {
			$sanitized['password'] = $new_password;
		} else {
			$sanitized['password'] = isset( $settings['connections']['smtp']['password'] )
				? (string) $settings['connections']['smtp']['password']
				: '';
		}

		$settings['provider']              = 'smtp';
		$settings['connections']['smtp']   = $sanitized;
		update_option( 'sendstack_settings', $settings );

		add_settings_error(
			'sendstack',
			'settings-saved',
			__( 'Settings saved.', 'sendstack' ),
			'success'
		);

		do_action( 'sendstack_settings_saved', $settings );
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * NO CONSTRUCTOR DEPENDENCIES
 * SettingsScreen reads and writes a single option (sendstack_settings). It has
 * no need for injected services — get_option / update_option are the interface.
 * Keeping dependencies out of the constructor makes this class cheap to test
 * (no mocking required) and easy to wire up in the service provider.
 *
 * settings_errors('sendstack') INSTEAD OF REDIRECT
 * WordPress's Settings API pattern is: save → add_settings_error → redirect →
 * display. We skip the redirect here because the form re-renders immediately
 * after handle_save() returns. Calling settings_errors('sendstack') at the top
 * of the form output picks up any errors added during handle_save() in the same
 * request cycle. This avoids a round-trip and keeps the nonce still valid.
 *
 * PASSWORD PRESERVATION
 * The password field always renders empty (value=""). Submitting the form blank
 * means "keep the current password". Only a non-empty submission overwrites the
 * stored value. This prevents the password from being silently cleared when the
 * admin edits other fields.
 *
 * ENCRYPTION WHITELIST
 * The encryption select only has three legal values. Filtering through an
 * in_array check (with strict comparison) before saving prevents an attacker
 * with manage_options access from storing arbitrary strings in the option.
 *
 * MERGING INTO THE EXISTING OPTION
 * update_option(sendstack_settings, $settings) after array-merging preserves
 * keys from other features (log retention, alert configs). A full replacement
 * would wipe those values each time the SMTP form is saved.
 */
