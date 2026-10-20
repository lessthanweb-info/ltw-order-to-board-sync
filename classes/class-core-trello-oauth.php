<?php
namespace LTW\Order_To_Board_Sync\Core;

/**
 * Handles Trello OAuth 2.0 authorization and access-token lifecycle.
 *
 * @author		lessthanweb
 * @package 	LTW_Order_To_Board_Sync
 * @since		1.0.0
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trello OAuth 2.0 client.
 */
class Trello_OAuth {
	/**
	 * Option used to store the Trello OAuth tokens.
	 *
	 * @var	string
	 */
	private const TOKEN_OPTION = 'ltw_order_to_board_sync_trello_oauth_tokens';

	/**
	 * Prefix for transients used to verify the OAuth state.
	 *
	 * @var	string
	 */
	private const STATE_TRANSIENT_PREFIX = 'ltw_order_to_board_sync_oauth_state_';

	/**
	 * Atlassian OAuth authorization endpoint.
	 *
	 * @var	string
	 */
	private const AUTHORIZE_URL = 'https://auth.atlassian.com/authorize';

	/**
	 * Atlassian OAuth token endpoint.
	 *
	 * @var	string
	 */
	private const TOKEN_URL = 'https://auth.atlassian.com/oauth/token';

	/**
	 * Trello REST API base URL.
	 *
	 * @var	string
	 */
	private const API_BASE_URL = 'https://api.trello.com/1/';

	/**
	 * OAuth scopes requested for Trello access.
	 *
	 * @var	string
	 */
	private const SCOPES = 'read:member:trello read:board:trello write:board:trello offline_access';

	/**
	 * WordPress admin-post action used for the OAuth callback.
	 *
	 * @var	string
	 */
	private const CALLBACK_ACTION = 'ltw_trello_oauth_callback';

	/**
	 * Register the authenticated OAuth actions.
	 *
	 * @param	Settings	$settings	Plugin settings.
	 * @param	Logger		$logger		Plugin logger.
	 * @return	void
	 * @since	1.0.0
	 */
	public function __construct( private Settings $settings, private Logger $logger ) {
		add_action( 'admin_post_ltw_trello_oauth_connect', [ $this, 'start_authorization' ] );
		add_action( 'admin_post_' . self::CALLBACK_ACTION, [ $this, 'handle_callback' ] );
		add_action( 'admin_post_ltw_trello_oauth_disconnect', [ $this, 'disconnect' ] );
		add_filter( Hooks::FILTER_TRELLO_OAUTH_ACCESS_TOKEN, [ $this, 'provide_access_token' ] );
	}

	/**
	 * Provide the current OAuth access token through the Free extension API.
	 *
	 * @param	mixed	$filtered_value	Previous value passed to the filter.
	 * @return	string|\WP_Error
	 * @since	1.0.0
	 */
	public function provide_access_token( mixed $filtered_value = '' ): string|\WP_Error {
		return $this->get_access_token();
	}

	/**
	 * Return the exact callback URL to register in the Trello app.
	 *
	 * @return	string
	 * @since	1.0.0
	 */
	public function get_callback_url(): string {
		return add_query_arg(
			'action',
			self::CALLBACK_ACTION,
			admin_url( 'admin-post.php' )
		);
	}

	/**
	 * Return the current access token, refreshing it before expiry when needed.
	 *
	 * @return	string|\WP_Error
	 * @since	1.0.0
	 */
	public function get_access_token( bool $force_refresh = false ): string|\WP_Error {
		$tokens = get_option( self::TOKEN_OPTION, [] );

		if ( ! is_array( $tokens ) || empty( $tokens['access_token'] ) ) {
			return new \WP_Error( 'trello_oauth_not_connected', __( 'Connect a Trello account in the plugin settings.', 'ltw-order-to-board-sync' ) );
		}

		if ( ! $force_refresh && isset( $tokens['expires_at'] ) && (int) $tokens['expires_at'] > time() + 60 ) {
			return (string) $tokens['access_token'];
		}

		if ( empty( $tokens['refresh_token'] ) ) {
			return new \WP_Error( 'trello_oauth_refresh_missing', __( 'The Trello authorization has expired. Reconnect your Trello account in the plugin settings.', 'ltw-order-to-board-sync' ) );
		}

		return $this->refresh_access_token( $tokens );
	}

	/**
	 * Check whether OAuth tokens are stored.
	 *
	 * @return	bool
	 * @since	1.0.0
	 */
	public function is_connected(): bool {
		$tokens = get_option( self::TOKEN_OPTION, [] );

		return is_array( $tokens ) && ! empty( $tokens['access_token'] ) && ! empty( $tokens['refresh_token'] );
	}

	/**
	 * Render OAuth connection status and protected connect/disconnect links.
	 *
	 * @return	string
	 * @since	1.0.0
	 */
	public function render_admin_controls(): string {
		$callback_url = $this->get_callback_url();

		$connect_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=ltw_trello_oauth_connect' ),
			'ltw_trello_oauth_connect'
		);

		$client_is_configured = $this->settings->get( 'settings', 'trello_api', 'oauth_client_id' ) !== '' && $this->settings->get_oauth_client_secret() !== '';
		
		$connect_label = $this->is_connected() ? __( 'Reconnect Trello account', 'ltw-order-to-board-sync' ) : __( 'Connect Trello account', 'ltw-order-to-board-sync' );

		$html = '<div class="ltw-order-to-board-sync-oauth">';
		$html .= '<p><strong>' . esc_html__( 'OAuth callback URL', 'ltw-order-to-board-sync' ) . ':</strong> <code>' . esc_html( $callback_url ) . '</code></p>';
		$html .= '<p>' . esc_html__( 'Register this exact URL in the callback URLs for your Trello OAuth 2.0 client.', 'ltw-order-to-board-sync' ) . '</p>';

		if ( $this->is_connected() ) {
			$html .= '<p><strong>' . esc_html__( 'Trello account connected.', 'ltw-order-to-board-sync' ) . '</strong></p>';

			$disconnect_url = wp_nonce_url(
				admin_url( 'admin-post.php?action=ltw_trello_oauth_disconnect' ),
				'ltw_trello_oauth_disconnect'
			);

			$html .= '<p><a class="button" href="' . esc_url( $disconnect_url ) . '">' . esc_html__( 'Disconnect Trello account', 'ltw-order-to-board-sync' ) . '</a></p>';
		} else {
			$html .= '<p><strong>' . esc_html__( 'Trello account not connected.', 'ltw-order-to-board-sync' ) . '</strong></p>';
		}

		if ( $client_is_configured ) {
			$html .= '<p><a class="button button-primary" href="' . esc_url( $connect_url ) . '">' . esc_html( $connect_label ) . '</a></p>';
		} else {
			$html .= '<p>' . esc_html__( 'Enter and save the OAuth client ID and secret above before connecting.', 'ltw-order-to-board-sync' ) . '</p>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Start Trello's authorization-code flow.
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	public function start_authorization(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to connect a Trello account.', 'ltw-order-to-board-sync' ) );
		}

		check_admin_referer( 'ltw_trello_oauth_connect' );

		$client_id = $this->settings->get( 'settings', 'trello_api', 'oauth_client_id' );
		$client_secret = $this->settings->get_oauth_client_secret();

		if ( $client_id === '' || $client_secret === '' ) {
			wp_die( esc_html__( 'Save your Trello OAuth client ID and client secret before connecting.', 'ltw-order-to-board-sync' ) );
		}

		try {
			$state = $this->base64url_encode( random_bytes( 32 ) );
			
			$verifier = $this->base64url_encode( random_bytes( 32 ) );
		} catch ( \Throwable $exception ) {
			$this->logger->error( __( 'Unable to generate secure Trello OAuth state.', 'ltw-order-to-board-sync' ), [ 'error' => $exception->getMessage() ] );
			
			wp_die( esc_html__( 'Unable to start secure Trello authorization. Please try again.', 'ltw-order-to-board-sync' ) );
			
			return;
		}

		$transient_key = self::STATE_TRANSIENT_PREFIX . hash( 'sha256', $state );

		$state_saved = set_transient(
			$transient_key,
			[
				'state' => $state,
				'verifier' => $verifier,
				'user_id' => get_current_user_id(),
			],
			10 * MINUTE_IN_SECONDS
		);

		if ( $state_saved === false ) {
			wp_die( esc_html__( 'Unable to securely start Trello authorization. Please try again.', 'ltw-order-to-board-sync' ) );
		}

		$challenge = $this->base64url_encode( hash( 'sha256', $verifier, true ) );

		$authorize_url = add_query_arg(
			[
				'client_id' => $client_id,
				'scope' => self::SCOPES,
				'redirect_uri' => $this->get_callback_url(),
				'state' => $state,
				'response_type' => 'code',
				'prompt' => 'consent',
				'code_challenge' => $challenge,
				'code_challenge_method' => 'S256',
			],
			self::AUTHORIZE_URL
		);

		wp_redirect( esc_url_raw( $authorize_url ) );
		die();
	}

	/**
	 * Validate the OAuth callback and exchange its authorization code.
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	public function handle_callback(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to complete Trello authorization.', 'ltw-order-to-board-sync' ) );
		}

		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$transient_key = self::STATE_TRANSIENT_PREFIX . hash( 'sha256', $state );
		$authorization = get_transient( $transient_key );
		delete_transient( $transient_key );

		if ( $state === ''
			|| ! is_array( $authorization )
			|| empty( $authorization['state'] )
			|| ! hash_equals( (string) $authorization['state'], $state )
			|| (int) ( $authorization['user_id'] ?? 0 ) !== get_current_user_id()
		) {
			wp_die( esc_html__( 'Trello authorization could not be verified. Start the connection process again.', 'ltw-order-to-board-sync' ) );
		}

		if ( isset( $_GET['error'] ) ) {
			$error = sanitize_text_field( wp_unslash( $_GET['error'] ) );
			
			$this->logger->error( __( 'Trello OAuth callback returned an authorization error.', 'ltw-order-to-board-sync' ), [ 'error' => $error ] );
			
			wp_die( esc_html( sprintf( __( 'Trello authorization was not completed: %s', 'ltw-order-to-board-sync' ), $error ) ) );
		}

		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';

		if ( $code === '' || empty( $authorization['verifier'] ) ) {
			wp_die( esc_html__( 'Trello did not return a valid authorization code. Start the connection process again.', 'ltw-order-to-board-sync' ) );
		}

		$token_result = $this->request_tokens( [
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $this->get_callback_url(),
			'code_verifier' => (string) $authorization['verifier'],
		] );

		if ( is_wp_error( $token_result ) ) {
			$this->logger->error( __( 'Trello OAuth authorization-code exchange failed.', 'ltw-order-to-board-sync' ), [ 'error' => $token_result->get_error_message() ] );
			
			wp_die( esc_html( $token_result->get_error_message() ) );
		}

		$this->store_tokens( $token_result );
		$access_token = $this->get_access_token();

		if ( is_wp_error( $access_token ) ) {
			$this->logger->error( __( 'Trello OAuth access-token validation failed.', 'ltw-order-to-board-sync' ), [ 'error' => $access_token->get_error_message() ] );
			
			wp_die( esc_html( $access_token->get_error_message() ) );
		}

		$verification = wp_remote_get(
			add_query_arg( [ 'fields' => 'id,username' ], self::API_BASE_URL . 'members/me' ),
			[
				'headers' => [ 'Authorization' => 'Bearer ' . $access_token, 'Accept' => 'application/json' ],
				'timeout' => 30,
				'sslverify' => true,
			]
		);

		$verification_data = is_wp_error( $verification ) ? null : json_decode( wp_remote_retrieve_body( $verification ), true );
		if ( is_wp_error( $verification )
			|| wp_remote_retrieve_response_code( $verification ) >= 400
			|| ! is_array( $verification_data )
			|| empty( $verification_data['id'] )
		) {
			$verification_error = is_wp_error( $verification ) ? $verification->get_error_message() : sprintf( __( 'Trello rejected the authorization or returned an invalid account response (HTTP %d).', 'ltw-order-to-board-sync' ), wp_remote_retrieve_response_code( $verification ) );
			
			delete_option( self::TOKEN_OPTION );
			
			$this->logger->error( __( 'Trello OAuth connection validation failed.', 'ltw-order-to-board-sync' ), [ 'error' => $verification_error ] );
			
			wp_die( esc_html( $verification_error ) );
		}

		$this->settings->mark_setup_complete();

		$this->logger->info( __( 'Trello OAuth account connected successfully.', 'ltw-order-to-board-sync' ) );

		wp_safe_redirect( admin_url( 'admin.php?page=' . LTW_ORDER_TO_BOARD_SYNC_PAGE_NAME . '#settings' ) );
		die();
	}

	/**
	 * Disconnect the stored Trello account.
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	public function disconnect(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to disconnect a Trello account.', 'ltw-order-to-board-sync' ) );
		}

		check_admin_referer( 'ltw_trello_oauth_disconnect' );

		delete_option( self::TOKEN_OPTION );

		$this->settings->reset_setup_complete();

		$this->logger->info( __( 'Trello OAuth account disconnected.', 'ltw-order-to-board-sync' ) );

		wp_safe_redirect( admin_url( 'admin.php?page=' . LTW_ORDER_TO_BOARD_SYNC_PAGE_NAME . '#settings' ) );
		die();
	}

	/**
	 * Refresh an expired access token and persist the rotated token set.
	 *
	 * @param	array	$tokens	Stored token data.
	 * @return	string|\WP_Error
	 * @since	1.0.0
	 */
	private function refresh_access_token( array $tokens ): string|\WP_Error {
		$result = $this->request_tokens(
			[
				'grant_type' => 'refresh_token',
				'refresh_token' => (string) $tokens['refresh_token'],
			]
		);

		if ( is_wp_error( $result ) ) {
			$this->logger->error( __( 'Trello OAuth access-token refresh failed.', 'ltw-order-to-board-sync' ), [ 'error' => $result->get_error_message() ] );
			
			return $result;
		}

		if ( empty( $result['refresh_token'] ) ) {
			$result['refresh_token'] = (string) $tokens['refresh_token'];
		}

		$this->store_tokens( $result );

		return (string) $result['access_token'];
	}

	/**
	 * Exchange an authorization code or refresh token for OAuth tokens.
	 *
	 * @param	array	$grant	OAuth grant parameters.
	 * @return	array|\WP_Error
	 * @since	1.0.0
	 */
	private function request_tokens( array $grant ): array|\WP_Error {
		$grant['client_id'] = $this->settings->get( 'settings', 'trello_api', 'oauth_client_id' );
		$grant['client_secret'] = $this->settings->get_oauth_client_secret();

		$response = wp_remote_post(
			self::TOKEN_URL,
			[
				'headers' => [ 'Accept' => 'application/json', 'Content-Type' => 'application/json; charset=utf-8' ],
				'body' => wp_json_encode( $grant ),
				'timeout' => 30,
				'sslverify' => true,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $status_code < 200
			|| $status_code >= 300
			|| ! is_array( $data )
			|| empty( $data['access_token'] )
			|| ! isset( $data['expires_in'] )
			|| ! is_numeric( $data['expires_in'] )
			|| (int) $data['expires_in'] < 1
			|| ( $grant['grant_type'] === 'authorization_code' && empty( $data['refresh_token'] ) )
		) {
			return new \WP_Error(
				'trello_oauth_token_request_failed',
				sprintf( __( 'Trello OAuth token request failed (HTTP %d). Check the OAuth client configuration and try again.', 'ltw-order-to-board-sync' ), $status_code )
			);
		}

		return $data;
	}

	/**
	 * Store the access and refresh tokens without autoloading them on every request.
	 *
	 * @param	array	$tokens	Trello token response.
	 * @return	void
	 * @since	1.0.0
	 */
	private function store_tokens( array $tokens ): void {
		update_option(
			self::TOKEN_OPTION,
			[
				'access_token' => (string) $tokens['access_token'],
				'refresh_token' => (string) ( $tokens['refresh_token'] ?? '' ),
				'expires_at' => time() + max( 0, (int) ( $tokens['expires_in'] ?? 0 ) ),
			],
			false
		);
	}

	/**
	 * Encode bytes using URL-safe base64 without padding.
	 *
	 * @param	string	$value	Bytes to encode.
	 * @return	string
	 * @since	1.0.0
	 */
	private function base64url_encode( string $value ): string {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}
}
