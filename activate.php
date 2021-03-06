<?php
/**
 * Installation and activation of anspress, register hooks that are fired when the plugin is activated.
 *
 * @package     AnsPress
 * @copyright   Copyright (c) 2013, Rahul Aryan
 * @license     https://www.gnu.org/licenses/gpl-2.0.txt GNU Public License
 * @since       0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activate AnsPress.
 */
class AP_Activate {
	/**
	 * Instance of this class.
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Char set.
	 *
	 * @var string
	 */
	public $charset_collate;

	/**
	 * Tables
	 *
	 * @var array
	 */
	public $tables = array();

	/**
	 * Network wide activate.
	 *
	 * @var boolean
	 */
	public $network_wide;

	/**
	 * Return an instance of this class.
	 *
	 * @param string|boolean $network_wide Actiavte plugin network wide.
	 * @return object A single instance of this class.
	 */
	public static function get_instance( $network_wide = '' ) {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			anspress();
			self::$instance = new self;
			global $network_wide;
			$network_wide = $network_wide;
		}

		return self::$instance;
	}

	/**
	 * Construct class.
	 */
	public function __construct() {
		global $network_wide;
		$this->network_wide = $network_wide;

		// Append table names in $wpdb.
		ap_append_table_names();

		if ( $this->network_wide ) {
			$this->network_activate();
		} else {
			$this->activate();
		}
	}

	/**
	 * Ap_qameta table.
	 */
	public function qameta_table() {
		global $wpdb;

		// @codingStandardsIgnoreLine
		$this->tables[] = 'CREATE TABLE `' . $wpdb->ap_qameta . '` (
			`post_id` bigint(20) NOT NULL,
			`selected_id` bigint(20) DEFAULT NULL,
			`comments` bigint(20) DEFAULT 0,
			`answers` bigint(20) DEFAULT 0,
			`ptype` varchar(100) DEFAULT NULL,
			`featured` tinyint(1) DEFAULT 0,
			`selected` tinyint(1) DEFAULT 0,
			`votes_up` bigint(20) DEFAULT 0,
			`votes_down` bigint(20) DEFAULT 0,
			`subscribers` TEXT DEFAULT NULL,
			`views` bigint(20) DEFAULT 0,
			`closed` tinyint(1) DEFAULT 0,
			`flags` bigint(20) DEFAULT 0,
			`terms` LONGTEXT DEFAULT NULL,
			`attach` LONGTEXT DEFAULT NULL,
			`activities` LONGTEXT DEFAULT NULL,
			`fields` LONGTEXT DEFAULT NULL,
			`roles` varchar(100) DEFAULT NULL,
			`last_updated` timestamp NULL DEFAULT NULL,
			UNIQUE KEY `post_id` (`post_id`)
		)' . $this->charset_collate . ';';
	}

	/**
	 * AnsPress ap_votes table.
	 */
	public function votes_table() {
		global $wpdb;

		$this->tables[] = 'CREATE TABLE `' . $wpdb->ap_votes . '` (
				`vote_id` bigint(20) NOT NULL AUTO_INCREMENT,
				`vote_post_id` bigint(20) NOT NULL,
				`vote_user_id` bigint(20) DEFAULT NULL,
				`vote_type` varchar(100) DEFAULT NULL,
				`vote_value` varchar(100) DEFAULT NULL,
				`vote_date` timestamp NULL DEFAULT NULL,
				PRIMARY KEY (`vote_id`)
			)' . $this->charset_collate . ';';
	}

	/**
	 * AnsPress views table.
	 */
	public function views_table() {
		global $wpdb;

		$this->tables[] = 'CREATE TABLE `' . $wpdb->ap_views . '` (
				`view_id` bigint(20) NOT NULL AUTO_INCREMENT,
				`view_user_id` bigint(20) DEFAULT NULL,
				`view_type` varchar(100) DEFAULT NULL,
				`view_ref_id` bigint(20) DEFAULT NULL,
				`view_ip` varchar(39),
				`view_date` timestamp NULL DEFAULT NULL,
				PRIMARY KEY (`view_id`)
			)' . $this->charset_collate . ';';
	}

	/**
	 * AnsPress reputation table.
	 */
	public function reputation_table() {
		global $wpdb;

		$this->tables[] = 'CREATE TABLE `' . $wpdb->ap_reputations . '` (
				`rep_id` bigint(20) NOT NULL AUTO_INCREMENT,
				`rep_user_id` bigint(20) DEFAULT NULL,
				`rep_event` varchar(100) DEFAULT NULL,
				`rep_ref_id` bigint(20) DEFAULT NULL,
				`rep_date` timestamp NULL DEFAULT NULL,
				PRIMARY KEY (`rep_id`)
			)' . $this->charset_collate . ';';
	}

	/**
	 * AnsPress email subscription related table.
	 */
	public function subscribers_table() {
		global $wpdb;

		$this->tables[] = 'CREATE TABLE `' . $wpdb->ap_subscribers . '` (
				`subs_id` bigint(20) NOT NULL AUTO_INCREMENT,
				`subs_user_id` bigint(20) NOT NULL,
				`subs_ref_id` bigint(20) NOT NULL,
				`subs_event` varchar(100) NOT NULL,
				PRIMARY KEY (`subs_id`)
			)' . $this->charset_collate . ';';
	}

	/**
	 * Insert and update tables
	 */
	public function insert_tables() {
		global $wpdb;
		$this->charset_collate = ! empty( $wpdb->charset ) ? 'DEFAULT CHARACTER SET ' . $wpdb->charset : '';

		$this->qameta_table();
		$this->votes_table();
		$this->views_table();
		$this->reputation_table();
		$this->subscribers_table();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		if ( count( $this->tables ) > 0 ) {
			foreach ( $this->tables as $table ) {
				dbDelta( $table );
			}
		}
	}

	/**
	 * Create base pages, add roles, add caps and create tables
	 */
	public function activate() {

		// add roles.
		$ap_roles = new AP_Roles;
		$ap_roles->add_roles();
		$ap_roles->add_capabilities();

		ap_create_base_page();

		if (  in_array( ap_opt( 'ap_version' ), [ '2.3.8', '3.0.0' ], true )  ) {
			update_option( 'ap_update_helper', true );
		}

		if ( ap_opt( 'ap_version' ) !== AP_VERSION ) {
			ap_opt( 'ap_installed', 'false' );
			ap_opt( 'ap_version', AP_VERSION );
		}

		if ( get_option( 'anspress_db_version' ) === '' ) {
			update_option( 'anspress_using_previous', 'true' );
		}

		$this->insert_tables();
		update_option( 'anspress_db_version', AP_DB_VERSION );
		update_option( 'anspress_opt', get_option( 'anspress_opt' ) + ap_default_options() );

		ap_opt( 'ap_flush', 'true' );
		flush_rewrite_rules( false );
	}

	/**
	 * Network activate.
	 */
	public function network_activate() {
		global $wpdb;

		// Get all blogs in the network and activate plugin on each one
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ); // db call ok, cache ok.

		foreach ( (array) $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id ); // @codingStandardsIgnoreLine
			$this->activate();
			restore_current_blog();
		}
	}
}


