<?php
/**
 * Class for base page
 *
 * @package   AnsPress
 * @author    Rahul Aryan <support@anspress.io>
 * @license   GPL-3.0+
 * @link      https://anspress.io
 * @copyright 2014 Rahul Aryan
 */

/**
 * Handle output of all default pages of AnsPress
 */
class AnsPress_Common_Pages {
	/**
	 * Register all pages of AnsPress
	 */
	public static function register_common_pages() {
		ap_register_page( 'base', ap_opt( 'base_page_title' ), array( __CLASS__, 'base_page' ) );
		ap_register_page( ap_opt( 'question_page_slug' ), __( 'Question', 'anspress-question-answer' ), array( __CLASS__, 'question_page' ), false );
		ap_register_page( ap_opt( 'ask_page_slug' ), __( 'Ask', 'anspress-question-answer' ), array( __CLASS__, 'ask_page' ) );
		ap_register_page( 'search', __( 'Search', 'anspress-question-answer' ), array( __CLASS__, 'search_page' ), false );
		ap_register_page( 'edit', __( 'Edit Answer', 'anspress-question-answer' ), array( __CLASS__, 'edit_page' ), false );
		ap_register_page( 'user', ap_opt( 'user_page_title' ), array( __CLASS__, 'user_page' ) );
	}

	/**
	 * Layout of base page
	 */
	public static function base_page() {
		global $questions, $wp;
		$query = $wp->query_vars;

		$keywords   = ap_sanitize_unslash( 'ap_s', 'query_var', false );
		$type       = ap_sanitize_unslash( 'type', 'request' );

		$tax_relation = ! empty( $wp->query_vars['ap_tax_relation'] ) ? $wp->query_vars['ap_tax_relation'] : 'OR';
		$args = array();
		$args['tax_query'] = array( 'relation' => $tax_relation );
		$args['tax_query'] = array( 'relation' => $tax_relation );

		if ( false !== $keywords ) {
			$args['s'] = array( 'relation' => $tax_relation );
		}

		/**
		 * FILTER: ap_main_questions_args
		 * Filter main question list args
		 * @var array
		 */
		$args = apply_filters( 'ap_main_questions_args', $args );

		anspress()->questions = $questions = new Question_Query( $args );
		ap_get_template_part( 'question-list' );
	}

	/**
	 * Output single question page
	 * @return void
	 */
	public static function question_page() {

		// Set Header as 404 if question id is not set.
		if ( false === get_question_id() ) {
			SELF::set_404();
			return;
		}

		$post = get_post( get_question_id() );

		// Check if user is allowed to read this question.
		if ( ! ap_user_can_read_question( get_question_id() ) ) {
			if( 'moderate' == $post->post_status ) {
				$msg = __('This question is waiting for a moderator and cannot be viewed. Please check back later to see if it has been approved.', 'anspress-question-answer' );
			} else {
				$msg = __('Sorry! you are not allowed to read this question.', 'anspress-question-answer' );
			}
			echo '<div class="ap-no-permission">' . $msg . '</div>';

			return;
		}

		global $questions;

		anspress()->questions = $questions = new Question_Query( [ 'p' => get_question_id() ] );

		if ( ap_have_questions() ) {
			/**
			 * Set current question as global post
			 * @since 2.3.3
			 */

			while ( ap_have_questions() ) : ap_the_question();
				global $post;
				setup_postdata( $post );
			endwhile;

			if ( 'future' == $post->post_status ) {
				echo '<div class="future-notice">';
				/**
				 * Filter to modify future post notice. If filter does not return false
				 * then retunrd string will be shown.
				 *
				 * @param  boolean $notice 		False by default.
				 * @param  object  $question   	Post object.
				 * @return boolean|string
				 * @since  2.4.7
				 */
				$notice = apply_filters( 'ap_future_post_notice', false, $post );
				if ( false === $notice ) {
					$time_to_publish = human_time_diff( strtotime( $post->post_date ), current_time( 'timestamp', true ) );
					echo '<strong>' . sprintf( __('Question will be publish in %s', 'anspress-question-answer' ), $time_to_publish ) . '</strong>';
					echo '<p>' . __( 'This question is in waiting queue and is not accessible by anyone until it get published.', 'anspress-question-answer' ) . '</p>';
				} else {
					echo $notice; // xss okay.
				}

				echo '</div>';
			}

			include( ap_get_theme_location( 'question.php' ) );

			do_action( 'ap_after_question' );
			wp_reset_postdata();

		} else {
			SELF::set_404();
		}

	}

	/**
	 * Output ask page template
	 */
	public static function ask_page() {
		$post_id = ap_sanitize_unslash( 'id', 'r', false );

		if ( $post_id && ! ap_verify_nonce( 'edit-post-' . $post_id ) ) {
			esc_attr_e( 'Something went wrong, please try again', 'anspress-question-answer' );
			return;
		}

		include ap_get_theme_location( 'ask.php' );
	}

	/**
	 * Load search page template
	 */
	public static function search_page() {
		$keywords   = ap_sanitize_unslash( 'ap_s', 'query_var', false );
		wp_safe_redirect( add_query_arg( [ 'ap_s' => $keywords ], ap_get_link_to( '/' ) ) );
	}

	/**
	 * Output edit page template
	 */
	public static function edit_page() {
		$post_id = (int) ap_sanitize_unslash( 'id', 'r' );

		if ( ! ap_verify_nonce( 'edit-post-' . $post_id ) || empty( $post_id ) || ! ap_user_can_edit_answer( $post_id ) ) {
				echo '<p>' . esc_attr__( 'Sorry, you cannot edit this answer.', 'anspress-question-answer' ) . '</p>';
				return;
		}

		global $editing_post;
		$editing_post = ap_get_post( $post_id );

		ap_answer_form( $editing_post->post_parent, true );
	}

	/**
	 * If page is not found then set header as 404
	 */
	public static function set_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		include ap_get_theme_location( 'not-found.php' );
	}

	/**
	 * Layout of base page
	 */
	public static function user_page() {
		global $questions;
		$args['ap_current_user_ignore'] = true;
		$args['author'] = (int) get_query_var( 'ap_user_id' );

		/**
		 * FILTER: ap_authors_questions_args
		 * Filter authors question list args
		 *
		 * @var array
		 */
		$args = apply_filters( 'ap_authors_questions_args', $args );

		anspress()->questions = $questions = new Question_Query( $args );
		ap_get_template_part( 'user/index' );
	}
}

