<?php
namespace LTW\Order_To_Board_Sync\Status;

/**
 * Contains system info methods.
 * 
 * Basically get as much info as required and echo it. This is mostly used for debugging purposes..
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
 * System_Info Class
 */
class System_Info {
	/**
	 * Returns a complete standalone HTML page with both
	 * server and website information sections.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	public function render_full_page(): string {
		$plain_text = $this->get_plain_text_report();

		$html = '<div class="ltw-order-to-board-sync-system-info">';
		$html .= $this->display_server_info();
		$html .= $this->display_website_info();
		$html .= $this->render_copy_button( $plain_text );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Returns the Server Environment section as HTML.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	public function display_server_info(): string {
		$html = '<h2>' . __( 'Server Environment', 'ltw-order-to-board-sync' ) . '</h2>' . "\n";
		$html .= '<figure>' . "\n";
		$html .= '  <table>' . "\n";
		$html .= '      <thead>' . "\n";
		$html .= '          <tr><th scope="col">' . __( 'Setting', 'ltw-order-to-board-sync' ) . '</th><th scope="col">' . __( 'Value', 'ltw-order-to-board-sync' ) . '</th></tr>' . "\n";
		$html .= '      </thead>' . "\n";
		$html .= '      <tbody>' . "\n";

		$html .= $this->render_row( __( 'Server architecture', 'ltw-order-to-board-sync' ), $this->get_server_architecture() );
		$html .= $this->render_row( __( 'Web server', 'ltw-order-to-board-sync' ), $this->get_web_server() );
		$html .= $this->render_row( __( 'PHP version', 'ltw-order-to-board-sync' ), $this->get_php_version() );
		$html .= $this->render_row( __( 'PHP SAPI', 'ltw-order-to-board-sync' ), $this->get_php_sapi() );
		$html .= $this->render_row( __( 'PHP memory limit', 'ltw-order-to-board-sync' ), $this->get_php_memory_limit() );
		$html .= $this->render_row( __( 'PHP max execution time', 'ltw-order-to-board-sync' ), $this->get_php_max_execution_time() );
		$html .= $this->render_row( __( 'PHP max input vars', 'ltw-order-to-board-sync' ), $this->get_php_max_input_vars() );
		$html .= $this->render_row( __( 'Upload max filesize', 'ltw-order-to-board-sync' ), $this->get_upload_max_filesize() );
		$html .= $this->render_row( __( 'Post max size', 'ltw-order-to-board-sync' ), $this->get_post_max_size() );
		$html .= $this->render_row( __( 'MySQL / MariaDB version', 'ltw-order-to-board-sync' ), $this->get_database_version() );
		$html .= $this->render_row( __( 'Database charset', 'ltw-order-to-board-sync' ), $this->get_database_charset() );
		$html .= $this->render_row( __( 'Database collation', 'ltw-order-to-board-sync' ), $this->get_database_collation() );

		$html .= '      </tbody>' . "\n";
		$html .= '  </table>' . "\n";
		$html .= '</figure>' . "\n";

		return $html;
	}

	/**
	 * Returns the WordPress / Website Environment section as HTML.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	public function display_website_info(): string {
		$html = '<h2>' . __( 'WordPress Environment', 'ltw-order-to-board-sync' ) . '</h2>' . "\n";
		$html .= '<figure>' . "\n";
		$html .= '  <table>' . "\n";
		$html .= '      <thead>' . "\n";
		$html .= '          <tr><th scope="col">' . __( 'Setting', 'ltw-order-to-board-sync' ) . '</th><th scope="col">' . __( 'Value', 'ltw-order-to-board-sync' ) . '</th></tr>' . "\n";
		$html .= '      </thead>' . "\n";
		$html .= '      <tbody>' . "\n";

		$html .= $this->render_row( __( 'WordPress version', 'ltw-order-to-board-sync' ), $this->get_wordpress_version() );
		$html .= $this->render_row( __( 'Site URL', 'ltw-order-to-board-sync' ), $this->get_site_url() );
		$html .= $this->render_row( __( 'Home URL', 'ltw-order-to-board-sync' ), $this->get_home_url() );
		$html .= $this->render_row( __( 'Is multisite', 'ltw-order-to-board-sync' ), $this->get_is_multisite() );
		$html .= $this->render_row( __( 'Site language', 'ltw-order-to-board-sync' ), $this->get_site_language() );
		$html .= $this->render_row( __( 'Timezone', 'ltw-order-to-board-sync' ), $this->get_timezone() );
		$html .= $this->render_row( __( 'Permalink structure', 'ltw-order-to-board-sync' ), $this->get_permalink_structure() );
		$html .= $this->render_row( __( 'WP memory limit', 'ltw-order-to-board-sync' ), $this->get_wp_memory_limit() );
		$html .= $this->render_row( __( 'WP debug mode', 'ltw-order-to-board-sync' ), $this->get_wp_debug_mode() );
		$html .= $this->render_row( __( 'Active theme', 'ltw-order-to-board-sync' ), $this->get_active_theme() );
		$html .= $this->render_row_html( __( 'Active Plugins', 'ltw-order-to-board-sync' ), $this->get_active_plugins_html() );
		$html .= $this->render_row( __( 'Search engine visibility', 'ltw-order-to-board-sync' ), $this->get_search_engine_visibility() );

		$html .= '      </tbody>' . "\n";
		$html .= '  </table>' . "\n";
		$html .= '</figure>' . "\n";

		return $html;
	}

	/**
    * Returns the full status report as plain text
    * suitable for pasting into an email or document.
    *
    * @return	string
    * @since	1.0.0
    *
    */
    public function get_plain_text_report(): string {
        $lines   = [];
        $lines[] = '=== ' . __( 'lessthanweb Woocommerce Trello Status Report', 'ltw-order-to-board-sync' ) . ' ===';
        $lines[] = '';
        $lines[] =  __( '--- Server Environment ---', 'ltw-order-to-board-sync' );
        $lines[] = __( 'Server architecture: ', 'ltw-order-to-board-sync' ) . $this->get_server_architecture();
        $lines[] = __( 'Web server: ', 'ltw-order-to-board-sync' ) . $this->get_web_server();
        $lines[] = __( 'PHP version: ', 'ltw-order-to-board-sync' ) . $this->get_php_version();
        $lines[] = __( 'PHP SAPI: ', 'ltw-order-to-board-sync' ) . $this->get_php_sapi();
        $lines[] = __( 'PHP memory limit: ', 'ltw-order-to-board-sync' ) . $this->get_php_memory_limit();
        $lines[] = __( 'PHP max execution time: ', 'ltw-order-to-board-sync' ) . $this->get_php_max_execution_time();
        $lines[] = __( 'PHP max input vars: ', 'ltw-order-to-board-sync' ) . $this->get_php_max_input_vars();
        $lines[] = __( 'Upload max filesize: ', 'ltw-order-to-board-sync' ) . $this->get_upload_max_filesize();
        $lines[] = __( 'Post max size: ', 'ltw-order-to-board-sync' ) . $this->get_post_max_size();
        $lines[] = __( 'MySQL / MariaDB version: ', 'ltw-order-to-board-sync' ) . $this->get_database_version();
        $lines[] = __( 'Database charset: ', 'ltw-order-to-board-sync' ) . $this->get_database_charset();
        $lines[] = __( 'Database collation: ', 'ltw-order-to-board-sync' ) . $this->get_database_collation();
        $lines[] = '';
        $lines[] = __( '--- WordPress Environment ---', 'ltw-order-to-board-sync' );
        $lines[] = __( 'WordPress version: ', 'ltw-order-to-board-sync' ) . $this->get_wordpress_version();
        $lines[] = __( 'Site URL: ', 'ltw-order-to-board-sync' ) . $this->get_site_url();
        $lines[] = __( 'Home URL: ', 'ltw-order-to-board-sync' ) . $this->get_home_url();
        $lines[] = __( 'Is multisite: ', 'ltw-order-to-board-sync' ) . $this->get_is_multisite();
        $lines[] = __( 'Site language: ', 'ltw-order-to-board-sync' ) . $this->get_site_language();
        $lines[] = __( 'Timezone: ', 'ltw-order-to-board-sync' ) . $this->get_timezone();
        $lines[] = __( 'Permalink structure: ', 'ltw-order-to-board-sync' ) . $this->get_permalink_structure();
        $lines[] = __( 'WP memory limit: ', 'ltw-order-to-board-sync' ) . $this->get_wp_memory_limit();
        $lines[] = __( 'WP debug mode: ', 'ltw-order-to-board-sync' ) . $this->get_wp_debug_mode();
        $lines[] = __( 'Active theme: ', 'ltw-order-to-board-sync' ) . $this->get_active_theme();
        $lines[] = __( 'Active plugins: ', 'ltw-order-to-board-sync' ) . $this->get_active_plugins_count();
        $lines[] = $this->get_active_plugins_plain_text();
        $lines[] = __( 'Search engine visibility: ', 'ltw-order-to-board-sync' ) . $this->get_search_engine_visibility();
        $lines[] = '';
        $lines[] = __( '=== End of Report ===', 'ltw-order-to-board-sync' );

        return implode( "\n", $lines );
    }

	/* -------------------------------------------------------------------------
	  * Private helpers – data retrieval (server)
	  * ---------------------------------------------------------------------- */

	/**
	 * Returns server architecture string (OS + release + machine).
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_server_architecture(): string {
		if ( function_exists( 'php_uname' ) ) {
			return sprintf(
				'%s %s %s',
				php_uname( 's' ),
				php_uname( 'r' ),
				php_uname( 'm' )
			);
		}

		return 'Unable to determine';
	}

	/**
	 * Returns the web server software string.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_web_server(): string {
		return ! empty( $_SERVER['SERVER_SOFTWARE'] ) ? (string) wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) : __( 'Unable to determine', 'ltw-order-to-board-sync' );
	}

	/**
	 * Returns the current PHP version with 64-bit indicator.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_php_version(): string {
		$bit = ( PHP_INT_SIZE * 8 === 64 ) ? ' (64bit)' : ' (32bit)';

		return PHP_VERSION . $bit;
	}

	/**
	 * Returns the PHP Server API (SAPI).
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_php_sapi(): string {
		return PHP_SAPI;
	}

	/**
	 * Returns the PHP memory_limit ini value.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_php_memory_limit(): string {
		return (string) ini_get( 'memory_limit' );
	}

	/**
	 * Returns the PHP max_execution_time ini value.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_php_max_execution_time(): string {
		return (string) ini_get( 'max_execution_time' ) . __( ' seconds', 'ltw-order-to-board-sync' );
	}

	/**
	 * Returns the PHP max_input_vars ini value.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_php_max_input_vars(): string {
		return (string) ini_get( 'max_input_vars' );
	}

	/**
	 * Returns the upload_max_filesize ini value.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_upload_max_filesize(): string {
		return (string) ini_get( 'upload_max_filesize' );
	}

	/**
	 * Returns the post_max_size ini value.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_post_max_size(): string {
		return (string) ini_get( 'post_max_size' );
	}

	/**
	 * Returns the database server version (MySQL / MariaDB).
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_database_version(): string {
		global $wpdb;

		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return __( 'N/A (wpdb not available)', 'ltw-order-to-board-sync' );
		}

		// Prefer the full server info string when available.
		if ( method_exists( $wpdb, 'db_server_info' ) ) {
			$info = $wpdb->db_server_info();

			if ( is_string( $info ) && $info !== '' ) {
				return $info;
			}
		}

		// Fallback to the cleaned version number.
		if ( method_exists( $wpdb, 'db_version' ) ) {
			$version = $wpdb->db_version();

			return is_string( $version ) ? $version : __( 'Unknown', 'ltw-order-to-board-sync' );
		}

		return __( 'Unknown', 'ltw-order-to-board-sync' );
	}

	/**
	 * Returns the database charset used by WordPress.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_database_charset(): string {
		global $wpdb;

		return isset( $wpdb->charset ) ? (string) $wpdb->charset : __( 'N/A', 'ltw-order-to-board-sync' );
	}

	/**
	 * Returns the database collation used by WordPress.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_database_collation(): string {
		global $wpdb;

		return isset( $wpdb->collate ) ? (string) $wpdb->collate : __( 'N/A', 'ltw-order-to-board-sync' );
	}

	/* -------------------------------------------------------------------------
	  * Private helpers – data retrieval (WordPress / website)
	  * ---------------------------------------------------------------------- */

	/**
	 * Returns the current WordPress version.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_wordpress_version(): string {
		return get_bloginfo( 'version' );
	}

	/**
	 * Returns the site URL (wpurl).
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_site_url(): string {
		return get_bloginfo( 'wpurl' );
	}

	/**
	 * Returns the home URL.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_home_url(): string {
		return get_bloginfo( 'url' );
	}

	/**
	 * Returns whether the installation is multisite.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_is_multisite(): string {
		return is_multisite() ? __( 'Yes', 'ltw-order-to-board-sync' ) : __( 'No', 'ltw-order-to-board-sync' );
	}

	/**
	 * Returns the site language / locale.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_site_language(): string {
		return get_locale();
	}

	/**
	 * Returns the configured timezone string or offset.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_timezone(): string {
		$tz = get_option( 'timezone_string' );

		if ( is_string( $tz ) && $tz !== '' ) {
			return $tz;
		}

		$offset = get_option( 'gmt_offset' );

		return is_numeric( $offset ) ? 'UTC' . ( (float) $offset >= 0 ? '+' : '' ) . $offset : __( 'Unknown', 'ltw-order-to-board-sync' );
	}

	/**
	 * Returns the current permalink structure.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_permalink_structure(): string {
		$structure = get_option( 'permalink_structure' );

		return ( is_string( $structure ) && $structure !== '' ) ? $structure : __( 'Plain', 'ltw-order-to-board-sync' );
	}

	/**
	 * Returns the effective WP memory limit.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_wp_memory_limit(): string {
		if ( defined( 'WP_MEMORY_LIMIT' ) ) {
			return (string) WP_MEMORY_LIMIT;
		}

		return __( 'Not defined (using PHP default)', 'ltw-order-to-board-sync' );
	}

	/**
	 * Returns whether WP_DEBUG is enabled.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_wp_debug_mode(): string {
		return ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? __( 'Enabled', 'ltw-order-to-board-sync' ) : __( 'Disabled', 'ltw-order-to-board-sync' );
	}

	/**
	 * Returns the active theme name and version.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_active_theme(): string {
		$theme = wp_get_theme();

		if ( ! $theme->exists() ) {
			return __( 'Unknown', 'ltw-order-to-board-sync' );
		}

		$name    = $theme->get( 'Name' );
		$version = $theme->get( 'Version' );
		$parent  = $theme->parent();

		$label = sprintf( '%s (v%s)', $name, $version );

		if ( $parent ) {
			$label .= sprintf( __( ' — Child of %s', 'ltw-order-to-board-sync' ), $parent->get( 'Name' ) );
		}

		return $label;
	}

	/**
	 * Returns the number of currently active plugins.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_active_plugins_count(): string {
		if ( ! function_exists( 'get_option' ) ) {
			return __( 'N/A', 'ltw-order-to-board-sync' );
		}

		$active = get_option( 'active_plugins', [] );
		$count  = is_array( $active ) ? count( $active ) : 0;

		// Network-activated plugins on multisite.
		if ( is_multisite() ) {
			$network = get_site_option( 'active_sitewide_plugins', [] );

			if ( is_array( $network ) ) {
				$count += count( $network );
			}
		}

		return (string) $count;
	}

	/**
	 * Returns whether the site is discouraging search engines.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_search_engine_visibility(): string {
		return (bool) get_option( 'blog_public' ) ? __( 'Visible', 'ltw-order-to-board-sync' ) : __( 'Discouraged', 'ltw-order-to-board-sync' );
	}

	/**
	* Returns structured data for all active plugins
	* (site + network-activated on multisite).
	*
	* Each item: name, version, uri, network (bool).
	*
	* @return	array
	* @since	1.0.0
	*
	*/
	private function get_active_plugins_data(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();
		$active = (array) get_option( 'active_plugins', [] );
		$network = [];

		if ( is_multisite() ) {
			$network = array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) );
		}

		$paths = array_unique( array_merge( $active, $network ) );

		$list = [];

		foreach ( $paths as $path ) {
			if ( ! isset( $all_plugins[ $path ] ) ) {
				continue;
			}

			$plugin = $all_plugins[ $path ];
			$uri    = $plugin['PluginURI'] ?? '';

			// Fallback to wordpress.org when PluginURI is empty
			if ( $uri === '' ) {
				$slug = dirname( $path );

				if ( $slug === '.' ) {
					// Single-file plugin in the plugins root
					$slug = basename( $path, '.php' );
				}

				$uri = 'https://wordpress.org/plugins/' . sanitize_title( $slug ) . '/';
			}

			$list[] = [
				'name' => $plugin['Name'] ?? $path,
				'version' => $plugin['Version'] ?? '',
				'uri' => $uri,
				'network' => in_array( $path, $network, true ),
			];
		}

		usort(
			$list,
			static function ( array $a, array $b ): int {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		return $list;
	}

	/**
	* Returns an HTML list of active plugins (name, version, homepage link).
	*
	* @return	string
	* @since	1.0.0
	*
	*/
	private function get_active_plugins_html(): string {
		$plugins = $this->get_active_plugins_data();

		if ( empty( $plugins ) ) {
			return __( 'None', 'ltw-order-to-board-sync' );
		}

		// Get count of all active plugins
		$html = __( 'Total: ', 'ltw-order-to-board-sync' ) . $this->get_active_plugins_count() . '<br>'; 

		$html .= '<ul>' . "\n";

		foreach ( $plugins as $plugin ) {
			$name    = esc_html( $plugin['name'] );
			$version = $plugin['version'] !== '' ? ' (v' . esc_html( $plugin['version'] ) . ')' : '';
			$suffix  = $plugin['network'] ? ' <em>' . __( '[Network]', 'ltw-order-to-board-sync' ) . '</em>' : '';

			if ( $plugin['uri'] !== '' ) {
				$uri  = esc_url( $plugin['uri'] );
				$item = '<a href="' . $uri . '" target="_blank" rel="noopener noreferrer">' . $name . '</a>' . $version . $suffix;
			} else {
				$item = $name . $version . $suffix;
			}

			$html .= '    <li>' . $item . '</li>' . "\n";
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	* Returns a plain-text list of active plugins
	* (name, version, homepage URL).
	*
	* @return	string
	* @since	1.0.0
	*
	*/
	private function get_active_plugins_plain_text(): string {
		$plugins = $this->get_active_plugins_data();

		if ( empty( $plugins ) ) {
			return __( '  None', 'ltw-order-to-board-sync' );
		}

		$lines = [];

		foreach ( $plugins as $plugin ) {
			$line = '  - ' . $plugin['name'];

			if ( $plugin['version'] !== '' ) {
				$line .= ' (v' . $plugin['version'] . ')';
			}

			if ( $plugin['network'] ) {
				$line .= ' [Network]';
			}

			if ( $plugin['uri'] !== '' ) {
				$line .= ' - ' . $plugin['uri'];
			}

			$lines[] = $line;
		}

		return implode( "\n", $lines );
	}

	/* -------------------------------------------------------------------------
	  * Private helpers – HTML output
	  * ---------------------------------------------------------------------- */

	/**
	 * Returns a single table row (label + value).
	 *
	 * @param	string  $label Human-readable setting name.
	 * @param	string  $value Current value.
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function render_row( string $label, string $value ): string {
		$html  = '                <tr>' . "\n";
		$html .= '                    <th scope="row">' . esc_html( $label ) . '</th>' . "\n";
		$html .= '                    <td>' . esc_html( $value ) . '</td>' . "\n";
		$html .= '                </tr>' . "\n";

		return $html;
	}

	/**
    * Returns a single table row where the value is trusted HTML
    * (e.g. a list of plugin links). Label is still escaped.
    *
    * @param	string $label Human-readable setting name.
    * @param	string $html_value Pre-built safe HTML for the value cell.
    * @return	string
    * @since	1.0.0
    *
    */
    private function render_row_html( string $label, string $html_value ): string {
        $html  = '                <tr>' . "\n";
        $html .= '                    <th scope="row">' . esc_html( $label ) . '</th>' . "\n";
        $html .= '                    <td>' . $html_value . '</td>' . "\n";
        $html .= '                </tr>' . "\n";

        return $html;
    }

	/**
	* Returns the "Copy status report" button and the JavaScript
	* that copies the plain-text report to the clipboard.
	*
	* @param	string $plain_text The plain-text status report.
	* @return	string
	* @since	1.0.0
	*
	*/
	private function render_copy_button( string $plain_text ): string {
		// Escape for safe use inside a HTML data attribute / JS string.
		$encoded = esc_attr( $plain_text );

		$html  = '<section>' . "\n";
		$html .= '	<p>' . "\n";
		$html .= '		<button type="button" id="wp-status-copy-btn">' . __( 'Copy status report', 'ltw-order-to-board-sync' ) . '</button>' . "\n";
		$html .= '		<span id="wp-status-copy-feedback" aria-live="polite"></span>' . "\n";
		$html .= '	</p>' . "\n";
		$html .= '	<textarea id="wp-status-plain-text" readonly hidden>' . esc_textarea( $plain_text ) . '</textarea>' . "\n";
		$html .= '</section>' . "\n";
		$html .= '<script>' . "\n";
		$html .= '(function () {' . "\n";
		$html .= '    var btn = document.getElementById("wp-status-copy-btn");' . "\n";
		$html .= '    var feedback = document.getElementById("wp-status-copy-feedback");' . "\n";
		$html .= '    var textarea = document.getElementById("wp-status-plain-text");' . "\n";
		$html .= '    if (!btn || !textarea) { return; }' . "\n";
		$html .= '    btn.addEventListener("click", function () {' . "\n";
		$html .= '        var text = textarea.value;' . "\n";
		$html .= '        function showFeedback(msg) {' . "\n";
		$html .= '            if (feedback) { feedback.textContent = msg; }' . "\n";
		$html .= '            setTimeout(function () { if (feedback) { feedback.textContent = ""; } }, 2500);' . "\n";
		$html .= '        }' . "\n";
		$html .= '        if (navigator.clipboard && navigator.clipboard.writeText) {' . "\n";
		$html .= '            navigator.clipboard.writeText(text).then(function () {' . "\n";
		$html .= '                showFeedback("Copied!");' . "\n";
		$html .= '            }).catch(function () {' . "\n";
		$html .= '                fallbackCopy();' . "\n";
		$html .= '            });' . "\n";
		$html .= '        } else {' . "\n";
		$html .= '            fallbackCopy();' . "\n";
		$html .= '        }' . "\n";
		$html .= '        function fallbackCopy() {' . "\n";
		$html .= '            textarea.hidden = false;' . "\n";
		$html .= '            textarea.select();' . "\n";
		$html .= '            try {' . "\n";
		$html .= '                document.execCommand("copy");' . "\n";
		$html .= '                showFeedback("Copied!");' . "\n";
		$html .= '            } catch (e) {' . "\n";
		$html .= '                showFeedback("Copy failed – please select the text manually.");' . "\n";
		$html .= '            }' . "\n";
		$html .= '            textarea.hidden = true;' . "\n";
		$html .= '        }' . "\n";
		$html .= '    });' . "\n";
		$html .= '})();' . "\n";
		$html .= '</script>' . "\n";

		return $html;
	}
}