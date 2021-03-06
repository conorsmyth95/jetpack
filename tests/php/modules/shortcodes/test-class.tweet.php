<?php

class WP_Test_Jetpack_Shortcodes_Tweet extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		// Back compat for PHPUnit 3!
		// @todo Remove this when WP's PHP version bumps.
		if ( is_callable( array( $this, 'getGroups' ) ) ) {
			$groups = $this->getGroups();
		} else {
			$annotations = $this->getAnnotations();
			$groups = array();
			foreach ( $annotations as $source ) {
				if ( ! isset( $source['group'] ) ) {
					continue;
				}
				$groups = array_merge( $groups, $source['group'] );
			}
		}

		if ( in_array( 'external-http', $groups ) ) {
			// Used by WordPress.com - does nothing in Jetpack.
			add_filter( 'tests_allow_http_request', '__return_true' );
		} else {
			/**
			 * We normally make an HTTP request to Instagram's oEmbed endpoint.
			 * This filter bypasses that HTTP request for these tests.
			 */
			add_filter( 'pre_http_request', array( $this, 'pre_http_request' ), 10, 3 );
		}
	}

	public function pre_http_request( $response, $args, $url ) {
		if ( 0 !== strpos( $url, 'https://publish.twitter.com/oembed?' ) ) {
			return $response;
		}

		$oembed_query = wp_parse_url( $url, PHP_URL_QUERY );
		$oembed_query_args = null;
		wp_parse_str( $oembed_query, $oembed_query_args );
		if ( ! isset( $oembed_query_args['url'] ) ) {
			return new WP_Error( 'unexpected-http-request', 'Test is making an unexpected HTTP request.' );
		}

		if ( 'https://twitter.com/jetpack/status/759034293385502721' !== $oembed_query_args['url'] ) {
			return new WP_Error( 'unexpected-http-request', 'Test is making an unexpected HTTP request.' );
		}

		if ( $oembed_query_args['align'] === 'none' ) {
			$align = '';
		} else {
			$align = "align=\\\"{$oembed_query_args['align']}\\\" ";
		}

		$body = <<<BODY
{
  "url": "https://twitter.com/jetpack/status/759034293385502721",
  "author_name": "Jetpack",
  "author_url": "https://twitter.com/jetpack",
  "html": "<blockquote class=\\"twitter-tweet\\" {$align}data-width=\\"500\\" data-lang=\\"{$oembed_query_args['lang']}\\" data-dnt=\\"true\\" data-partner=\\"jetpack\\"><p lang=\\"en\\" dir=\\"ltr\\">In this month’s Hook of the Month feature, learn how to customize Jetpack Related Posts! <a href=\\"https://t.co/lM6G28QpLS\\">https://t.co/lM6G28QpLS</a> <a href=\\"https://t.co/0Mn5ALQoKT\\">pic.twitter.com/0Mn5ALQoKT</a></p>&mdash; Jetpack (@jetpack) <a href=\\"https://twitter.com/jetpack/status/759034293385502721?ref_src=twsrc%5Etfw\\">July 29, 2016</a></blockquote>\\n",
  "width": 500,
  "height": null,
  "type": "rich",
  "cache_age": "3153600000",
  "provider_name": "Twitter",
  "provider_url": "https://twitter.com",
  "version": "1.0"
}
BODY;

		return array(
			'response' => array(
				'code' => 200,
			),
			'body' => $body,
		);
	}

	/**
	 * Verify that [tweet] exists.
	 *
	 * @since  4.5.0
	 */
	public function test_shortcodes_tweet_exists() {
		$this->assertEquals( shortcode_exists( 'tweet' ), true );
	}

	/**
	 * Verify that calling do_shortcode with the shortcode doesn't return the same content.
	 *
	 * @since 4.5.0
	 */
	public function test_shortcodes_tweet() {
		$content = '[tweet]';

		$shortcode_content = do_shortcode( $content );

		$this->assertNotEquals( $content, $shortcode_content );
		$this->assertEquals( '<!-- Invalid tweet id -->', $shortcode_content );
	}

	/**
	 * Verify that rendering the shortcode returns a tweet card.
	 *
	 * @since 4.5.0
	 */
	public function test_shortcodes_tweet_card() {
		$content = "[tweet https://twitter.com/jetpack/status/759034293385502721]";

		$shortcode_content = do_shortcode( $content );

		$this->assertContains( '<blockquote class="twitter-tweet"', $shortcode_content );
		// Not testing here for actual URL because wp_oembed_get might return a shortened Twitter URL with t.co domain
	}

	/**
	 * Verify that rendering the shortcode with custom parameters adds them to the tweet card.
	 *
	 * @since 4.5.0
	 */
	public function test_shortcodes_tweet_card_parameters() {
		$content = "[tweet https://twitter.com/jetpack/status/759034293385502721 align=right lang=es]";

		$shortcode_content = do_shortcode( $content );

		$this->assertContains( 'align="right"', $shortcode_content );
		$this->assertContains( 'data-lang="es"', $shortcode_content );
	}

	/**
	 * Verify that rendering the shortcode with only the tweet ID produces a correct output.
	 *
	 * @since 4.5.0
	 */
	public function test_shortcodes_tweet_id_only() {
		$content = "[tweet 759034293385502721]";

		$shortcode_content = do_shortcode( $content );

		$this->assertContains( '<blockquote class="twitter-tweet"', $shortcode_content );
		// Not testing here for actual URL because wp_oembed_get might return a shortened Twitter URL with t.co domain
	}

	/**
	 * Verify that rendering the shortcode contains Jetpack's partner ID
	 *
	 * @since 4.6.0
	 */
	public function test_shortcode_tweet_partner_id() {
		$content = "[tweet 759034293385502721]";

		$shortcode_content = do_shortcode( $content );

		$this->assertContains( 'data-partner="jetpack"', $shortcode_content );
	}

	/**
	 * Verify that rendering the shortcode returns a tweet card.
	 *
	 * @group external-http
	 * @since 7.4.0
	 */
	public function test_shortcodes_tweet_card_via_oembed_http_request() {
		$content = "[tweet https://twitter.com/jetpack/status/759034293385502721]";

		$shortcode_content = do_shortcode( $content );

		$this->assertContains( '<blockquote class="twitter-tweet"', $shortcode_content );
		// Not testing here for actual URL because wp_oembed_get might return a shortened Twitter URL with t.co domain
	}
}
