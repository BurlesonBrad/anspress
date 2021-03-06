<?php
	/**
	 * Plugin rewrite rules and query variables
	 *
	 * @package   AnsPress
	 * @author    Rahul Aryan <support@anspress.io>
	 * @license   GPL-2.0+
	 * @link      https://anspress.io
	 * @copyright 2014 Rahul Aryan
	 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * This class handle all rewrite rules and define query varibale of anspress
 * @since 2.0.0
 */
class AnsPress_Rewrite
{
	/**
	 * Register query vars
	 * @param  array $query_vars Registered query variables.
	 * @return array
	 */
	public static function query_var( $query_vars ) {
		$query_vars[] = 'edit_post_id';
		$query_vars[] = 'ap_nonce';
		$query_vars[] = 'question_id';
		$query_vars[] = 'answer_id';
		$query_vars[] = 'question';
		$query_vars[] = 'question_name';
		$query_vars[] = 'answer_id';
		$query_vars[] = 'answer';
		$query_vars[] = 'ask';
		$query_vars[] = 'ap_page';
		$query_vars[] = 'qcat_id';
		$query_vars[] = 'qcat';
		$query_vars[] = 'qtag_id';
		$query_vars[] = 'q_tag';
		$query_vars[] = 'q_cat';
		$query_vars[] = 'ap_s';
		$query_vars[] = 'message_id';
		$query_vars[] = 'parent';
		$query_vars[] = 'ap_user';
		$query_vars[] = 'user_page';
		$query_vars[] = 'ap_paged';

		return $query_vars;
	}

	/**
	 * Rewrite rules.
	 *
	 * @return array
	 */
	public static function rewrites() {
		global $wp_rewrite;
		global $ap_rules;

		unset( $wp_rewrite->extra_permastructs['question'] );
		unset( $wp_rewrite->extra_permastructs['answer'] );

		$base_page_id 		= ap_opt( 'base_page' );

		// Try to create base page if doesn't exists.
		if ( ! ap_get_post( $base_page_id ) ) {
			ap_create_base_page();
		}

		$slug = ap_base_page_slug() . '/';

		$question_permalink = ap_opt( 'question_page_permalink' );
		$question_slug = ap_opt( 'question_page_slug' );

		if ( 'question_perma_2' === $question_permalink ) {
			$question_placeholder = $question_slug . '/([^/]+)';
			$question_perma = '&question_name=' . $wp_rewrite->preg_index( 1 );
		} elseif ( 'question_perma_3' === $question_permalink ) {
			$question_placeholder = $question_slug . '/([^/]+)';
			$question_perma = '&question_id=' . $wp_rewrite->preg_index( 1 );
		} elseif ( 'question_perma_4' === $question_permalink ) {
			$question_placeholder = $question_slug . '/([^/]+)/([^/]+)';
			$question_perma = '&question_id=' . $wp_rewrite->preg_index( 1 ) . '&question_name=' . $wp_rewrite->preg_index( 2 );
		} else {
			$question_placeholder = ap_base_page_slug() . '/' .$question_slug . '/([^/]+)';
			$question_perma = '&question_name=' . $wp_rewrite->preg_index( 1 );
		}

		$new_rules = array(
			$slug . 'parent/([^/]+)/?' => 'index.php?page_id=' . $base_page_id . '&parent=' . $wp_rewrite->preg_index( 1 ),

			$slug . 'page/?([0-9]{1,})/?$' => 'index.php?page_id=' . $base_page_id . '&paged=' . $wp_rewrite->preg_index( 1 ),

			$slug . '([^/]+)/page/?([0-9]{1,})/?$' => 'index.php?page_id=' . $base_page_id . '&ap_page=' . $wp_rewrite->preg_index( 1 ) . '&paged=' . $wp_rewrite->preg_index( 2 ),
		);


		$new_rules[ $question_placeholder . '/([^/]+)/?$' ] = 'index.php?page_id=' . $base_page_id . '&ap_page=question' . $question_perma . '&answer_id=' . $wp_rewrite->preg_index( 2 );

		$new_rules[ $question_placeholder . '/?$' ]  = 'index.php?page_id=' . $base_page_id . '&ap_page=question' . $question_perma;

		$new_rules[ $slug . 'search/([^/]+)/?' ] = 'index.php?page_id=' . $base_page_id . '&ap_page=search&ap_s=' . $wp_rewrite->preg_index( 1 );

		$new_rules[ $slug . 'user/([^/]+)/?' ] = 'index.php?page_id=' . $base_page_id . '&ap_page=user&ap_user=' . $wp_rewrite->preg_index( 1 );

		$new_rules[ $slug . 'ask/([^/]+)/?' ] = 'index.php?page_id=' . $base_page_id . '&ap_page=ask&parent=' . $wp_rewrite->preg_index( 1 );

		$new_rules[ $slug . '([^/]+)/?' ] = 'index.php?page_id=' . $base_page_id . '&ap_page=' . $wp_rewrite->preg_index( 1 );

		$ap_rules = apply_filters( 'ap_rewrite_rules', $new_rules, $slug, $base_page_id );

		return $wp_rewrite->rules = $ap_rules + $wp_rewrite->rules;
	}

	public static function bp_com_paged($args) {
		if ( function_exists( 'bp_current_component' ) ) {
			$bp_com = bp_current_component();

			if ( 'questions' == $bp_com || 'answers' == $bp_com ) {
				return preg_replace( '/page.([0-9]+)./', '?paged=$1', $args );
			}
		}

		return $args;
	}

	/**
	 * Push custom query args in `$wp`.
	 *
	 * If `question_name` is passed then `question_id` var will be added.
	 * Same for `ap_user`.
	 *
	 * @param object $wp WP query object.
	 */
	public static function add_query_var( $wp ) {
		if ( ! empty( $wp->query_vars['question_name'] ) ) {
			$wp->set_query_var( 'ap_page', 'question' );
			$question = get_page_by_path( sanitize_title( $wp->query_vars['question_name'] ), 'OBJECT', 'question' );

			if ( $question ) {
				$wp->set_query_var( 'question_id', $question->ID );
			} else {
				// Rediret to 404 page if question does not exists.
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				get_template_part( 404 );
				exit();
			}
		}

		if ( ! empty( $wp->query_vars['ap_user'] ) ) {
			$user = get_user_by( 'login', sanitize_text_field( urldecode( $wp->query_vars['ap_user'] ) ) );

			if ( $user ) {
				$wp->set_query_var( 'ap_user_id', (int) $user->ID );
			} else {
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				get_template_part( 404 );
				exit();
			}
		}
	}
}
