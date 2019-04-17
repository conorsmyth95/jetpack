<?php
/**
 * Jetpack Debug Data for the legacy Jetpack debugger page and the WP 5.2-era Site Health sections.
 *
 * @package jetpack
 */

/**
 * Class Jetpack_Debug_Data
 *
 * Collect and return debug data for Jetpack.
 *
 * @since 7.3.0
 */
class Jetpack_Debug_Data {
	/**
	 * Determine the active plan and normalize it for the debugger results.
	 *
	 * @since 7.3.0
	 *
	 * @return string The plan slug.
	 */
	public static function what_jetpack_plan() {
		$plan = Jetpack_Plan::get();
		return ! empty( $plan['class'] ) ? $plan['class'] : 'undefined';
	}

	/**
	 * Convert seconds to human readable time.
	 *
	 * A dedication function instead of using Core functionality to allow for output in seconds.
	 *
	 * @since 7.3.0
	 *
	 * @param int $seconds Number of seconds to convert to human time.
	 *
	 * @return string Human readable time.
	 */
	public static function seconds_to_time( $seconds ) {
		$seconds = intval( $seconds );
		$units   = array(
			'week'   => WEEK_IN_SECONDS,
			'day'    => DAY_IN_SECONDS,
			'hour'   => HOUR_IN_SECONDS,
			'minute' => MINUTE_IN_SECONDS,
			'second' => 1,
		);
		// specifically handle zero.
		if ( 0 === $seconds ) {
			return '0 seconds';
		}
		$human_readable = '';
		foreach ( $units as $name => $divisor ) {
			$quot = intval( $seconds / $divisor );
			if ( $quot ) {
				$human_readable .= "$quot $name";
				$human_readable .= ( abs( $quot ) > 1 ? 's' : '' ) . ', ';
				$seconds        -= $quot * $divisor;
			}
		}
		return substr( $human_readable, 0, -2 );
	}

	/**
	 * Return debug data in the format expected by Core's Site Health Info tab.
	 *
	 * @since 7.3.0
	 *
	 * @param array $debug {
	 *     The debug information already compiled by Core.
	 *
	 *     @type string  $label        The title for this section of the debug output.
	 *     @type string  $description  Optional. A description for your information section which may contain basic HTML
	 *                                 markup: `em`, `strong` and `a` for linking to documentation or putting emphasis.
	 *     @type boolean $show_count   Optional. If set to `true` the amount of fields will be included in the title for
	 *                                 this section.
	 *     @type boolean $private      Optional. If set to `true` the section and all associated fields will be excluded
	 *                                 from the copy-paste text area.
	 *     @type array   $fields {
	 *         An associative array containing the data to be displayed.
	 *
	 *         @type string  $label    The label for this piece of information.
	 *         @type string  $value    The output that is of interest for this field.
	 *         @type boolean $private  Optional. If set to `true` the field will not be included in the copy-paste text area
	 *                                 on top of the page, allowing you to show, for example, API keys here.
	 *     }
	 * }
	 *
	 * @return array $args Debug information in the same format as the initial argument.
	 */
	public static function core_debug_data( $debug ) {
		$jetpack = array(
			'jetpack' => array(
				'label'       => __( 'Jetpack', 'jetpack' ),
				'description' => sprintf(
					/* translators: %1$s is URL to jetpack.com's contact support page. %2$s accessibility text */
					__(
						'Diagnostic information helpful to <a href="%1$s" target="_blank" rel="noopener noreferrer">your Jetpack Happiness team<span class="screen-reader-text">%2$s</span></a>',
						'jetpack'
					),
					esc_html( 'https://jetpack.com/contact-support/' ),
					__( '(opens in a new tab)', 'jetpack' )
				),
				'fields'      => self::debug_data(),
			),
		);
		$debug   = array_merge( $debug, $jetpack );
		return $debug;
	}

	/**
	 * Compile and return array of debug information.
	 *
	 * @since 7.3.0
	 *
	 * @return array $args {
	 *          Associated array of arrays with the following.
	 *         @type string  $label    The label for this piece of information.
	 *         @type string  $value    The output that is of interest for this field.
	 *         @type boolean $private  Optional. Set to true if data is sensitive (API keys, etc).
	 * }
	 */
	public static function debug_data() {
		$debug_info = array();

		/* Add various important Jetpack options */
		$debug_info['site_id']        = array(
			'label'   => 'Jetpack Site ID',
			'value'   => Jetpack_Options::get_option( 'id' ),
			'private' => false,
		);
		$debug_info['ssl_cert']       = array(
			'label'   => 'Jetpack SSL Verfication Bypass',
			'value'   => ( Jetpack_Options::get_option( 'fallback_no_verify_ssl_certs' ) ) ? 'Yes' : 'No',
			'private' => false,
		);
		$debug_info['time_diff']      = array(
			'label'   => "Offset between Jetpack server's time and this server's time.",
			'value'   => Jetpack_Options::get_option( 'time_diff' ),
			'private' => false,
		);
		$debug_info['version_option'] = array(
			'label'   => 'Current Jetpack Version Option',
			'value'   => Jetpack_Options::get_option( 'version' ),
			'private' => false,
		);
		$debug_info['old_version']    = array(
			'label'   => 'Previous Jetpack Version',
			'value'   => Jetpack_Options::get_option( 'old_version' ),
			'private' => false,
		);
		$debug_info['public']         = array(
			'label'   => 'Jetpack Site Public',
			'value'   => ( Jetpack_Options::get_option( 'public' ) ) ? 'Public' : 'Private',
			'private' => false,
		);
		$debug_info['master_user']    = array(
			'label'   => 'Jetpack Master User',
			'value'   => Jetpack_Options::get_option( 'master_user' ),
			'private' => false,
		);

		/* Token information is private, but awareness if there one is set is helpful. */
		$user_id     = get_current_user_id();
		$user_tokens = Jetpack_Options::get_option( 'user_tokens' );
		$blog_token  = Jetpack_Options::get_option( 'blog_token' );
		$user_token  = null;
		if ( is_array( $user_tokens ) && array_key_exists( $user_id, $user_tokens ) ) {
			$user_token = $user_tokens[ $user_id ];
		}
		unset( $user_tokens );

		$tokenset = '';
		if ( $blog_token ) {
			$tokenset = 'Blog ';
		}
		if ( $user_token ) {
			$tokenset .= 'User';
		}
		if ( ! $tokenset ) {
			$tokenset = 'None';
		}

		$debug_info['current_user'] = array(
			'label'   => 'Current User',
			'value'   => $user_id,
			'private' => false,
		);
		$debug_info['tokens_set']   = array(
			'label' => 'Tokens defined',
			'value' => $tokenset,
			'private => false,',
		);
		$debug_info['blog_token']   = array(
			'label'   => 'Blog token',
			'value'   => ( $blog_token ) ? $blog_token : 'Not set.',
			'private' => true,
		);
		$debug_info['user_token']   = array(
			'label'   => 'User token',
			'value'   => ( $user_token ) ? $user_token : 'Not set.',
			'private' => true,
		);

		/** Jetpack Environmental Information */
		$debug_info['version']       = array(
			'label'   => 'Jetpack Version',
			'value'   => JETPACK__VERSION,
			'private' => false,
		);
		$debug_info['jp_plugin_dir'] = array(
			'label'   => 'Jetpack Directory',
			'value'   => JETPACK__PLUGIN_DIR,
			'private' => false,
		);
		$debug_info['plan']          = array(
			'label'   => 'Plan Type',
			'value'   => self::what_jetpack_plan(),
			'private' => false,
		);

		foreach ( array(
			'HTTP_HOST',
			'SERVER_PORT',
			'HTTPS',
			'GD_PHP_HANDLER',
			'HTTP_AKAMAI_ORIGIN_HOP',
			'HTTP_CF_CONNECTING_IP',
			'HTTP_CLIENT_IP',
			'HTTP_FASTLY_CLIENT_IP',
			'HTTP_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_INCAP_CLIENT_IP',
			'HTTP_TRUE_CLIENT_IP',
			'HTTP_X_CLIENTIP',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_X_FORWARDED',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_IP_TRAIL',
			'HTTP_X_REAL_IP',
			'HTTP_X_VARNISH',
			'REMOTE_ADDR',
		) as $header ) {
			if ( isset( $_SERVER[ $header ] ) ) {
				$debug_info[ $header ] = array(
					'label'   => 'Server Variable ' . $header,
					'value'   => ( $_SERVER[ $header ] ) ? $_SERVER[ $header ] : 'false',
					'private' => false,
				);
			}
		}

		$debug_info['protect_header'] = array(
			'label'   => 'Trusted IP',
			'value'   => wp_json_encode( get_site_option( 'trusted_ip_header' ) ),
			'private' => false,
		);

		/** Sync Debug Information */
		/** Load Sync modules */
		require_once JETPACK__PLUGIN_DIR . 'sync/class.jetpack-sync-modules.php';
		/** Load Sync sender */
		require_once JETPACK__PLUGIN_DIR . 'sync/class.jetpack-sync-sender.php';
		/** Load Sync functions */
		require_once JETPACK__PLUGIN_DIR . 'sync/class.jetpack-sync-functions.php';

		$sync_module = Jetpack_Sync_Modules::get_module( 'full-sync' );
		if ( $sync_module ) {
			$sync_statuses              = $sync_module->get_status();
			$human_readable_sync_status = array();
			foreach ( $sync_statuses as $sync_status => $sync_status_value ) {
				$human_readable_sync_status[ $sync_status ] =
					in_array( $sync_status, array( 'started', 'queue_finished', 'send_started', 'finished' ), true )
						? date( 'r', $sync_status_value ) : $sync_status_value;
			}
			$debug_info['full_sync'] = array(
				'label'   => 'Full Sync Status',
				'value'   => wp_json_encode( $human_readable_sync_status ),
				'private' => false,
			);
		}

		$queue = Jetpack_Sync_Sender::get_instance()->get_sync_queue();

		$debug_info['sync_size'] = array(
			'label'   => 'Sync Queue Size',
			'value'   => $queue->size(),
			'private' => false,
		);
		$debug_info['sync_lag']  = array(
			'label'   => 'Sync Queue Lag',
			'value'   => self::seconds_to_time( $queue->lag() ),
			'private' => false,
		);

		$full_sync_queue = Jetpack_Sync_Sender::get_instance()->get_full_sync_queue();

		$debug_info['full_sync_size'] = array(
			'label'   => 'Full Sync Queue Size',
			'value'   => $full_sync_queue->size(),
			'private' => false,
		);
		$debug_info['full_sync_lag']  = array(
			'label'   => 'Full Sync Queue Lag',
			'value'   => self::seconds_to_time( $full_sync_queue->lag() ),
			'private' => false,
		);

		/**
		 * IDC Information
		 *
		 * Must follow sync debug since it depends on sync functionality.
		 */
		$idc_urls = array(
			'home'       => Jetpack_Sync_Functions::home_url(),
			'siteurl'    => Jetpack_Sync_Functions::site_url(),
			'WP_HOME'    => Jetpack_Constants::is_defined( 'WP_HOME' ) ? Jetpack_Constants::get_constant( 'WP_HOME' ) : '',
			'WP_SITEURL' => Jetpack_Constants::is_defined( 'WP_SITEURL' ) ? Jetpack_Constants::get_constant( 'WP_SITEURL' ) : '',
		);

		$debug_info['idc_urls']         = array(
			'label'   => 'IDC URLs',
			'value'   => wp_json_encode( $idc_urls ),
			'private' => false,
		);
		$debug_info['idc_error_option'] = array(
			'label'   => 'IDC Error Option',
			'value'   => wp_json_encode( Jetpack_Options::get_option( 'sync_error_idc' ) ),
			'private' => false,
		);
		$debug_info['idc_optin']        = array(
			'label'   => 'IDC Opt-in',
			'value'   => Jetpack::sync_idc_optin(),
			'private' => false,
		);

		// @todo -- Add testing results?
		$cxn_tests               = new Jetpack_Cxn_Tests();
		$debug_info['cxn_tests'] = array(
			'label'   => 'Connection Tests',
			'value'   => '',
			'private' => false,
		);
		if ( $cxn_tests->pass() ) {
			$debug_info['cxn_tests']['value'] = 'All Pass.';
		} else {
			$debug_info['cxn_tests']['value'] = wp_json_encode( $cxn_tests->list_fails() );
		}

		return $debug_info;
	}
}
