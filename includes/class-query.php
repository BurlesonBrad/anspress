<?php
/**
 * Question class
 *
 * @package   WordPress/AnsPress
 * @author    Rahul Aryan <support@anspress.io>
 * @license   GPL-3.0+
 * @link      https://anspress.io/
 * @copyright 2017 Rahul Aryan
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Base class for query.
 */
abstract class AnsPress_Query {
	/**
	 * The loop iterator.
	 *
	 * @access public
	 * @var int
	 */
	var $current = -1;

	/**
	 * The number of rows returned by the paged query.
	 *
	 * @access public
	 * @var int
	 */
	var $count;

	/**
	 * Array of items located by the query.
	 *
	 * @access public
	 * @var array
	 */
	var $objects;

	/**
	 * The object currently being iterated on.
	 *
	 * @access public
	 * @var object
	 */
	var $object;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @access public
	 * @var bool
	 */
	var $in_the_loop;

	/**
	 * The total number of rows matching the query parameters.
	 *
	 * @access public
	 * @var int
	 */
	var $total_count;

	/**
	 * Items to show per page
	 *
	 * @access public
	 * @var int
	 */
	var $per_page = 20;

	/**
	 * Total numbers of pages based on query.
	 *
	 * @var int
	 */
	var $total_pages = 1;

	/**
	 * Current page.
	 *
	 * @var int
	 */
	var $paged = 1;

	/**
	 * Database query offset.
	 *
	 * @var int
	 */
	var $offset;

	/**
	 * Ids to be prefetched.
	 *
	 * @var array
	 */
	var $prefetech_ids = [ 'post' => [], 'comment' => [], 'question' => [], 'answer' => [] ];

	/**
	 * Initialize the class.
	 *
	 * @param array $args Arguments.
	 */
	public function __construct( $args = [] ) {
		$this->paged = isset( $args['paged'] ) ? (int) $args['paged'] : 1;
		$this->offset = $this->per_page * ($this->paged - 1);

		$this->args = wp_parse_args( $args, array(
			'user_id' => get_current_user_id(),
			'number' 	=> $this->per_page,
			'offset' 	=> $this->offset,
			'order' 	=> 'DESC',
		));

		$this->per_page = $this->args['number'];
		$this->query();
	}

	/**
	 * Count total numbers of rows found.
	 *
	 * @param string $key MD5 hashed key.
	 */
	public function total_count( $key ) {
		global $wpdb;
		$this->total_count = wp_cache_get( $key . '_count', 'ap_total_count' );

		if ( false === $result ) {
			$this->total_count = $wpdb->get_var( apply_filters( 'ap_found_rows', 'SELECT FOUND_ROWS()', $this ) ); // WPCS: db call okay.
			wp_cache_set( $key . '_count', $this->total_count, 'ap_total_count' );
		}
	}

	/**
	 * Fetch results from database.
	 */
	public function query() {
		$this->total_pages 	= ceil( $this->total_count / $this->per_page );
		$this->count = count( $this->objects );
	}

	/**
	 * Check if loop has objects.
	 *
	 * @return boolean
	 */
	public function have() {
		if ( $this->current + 1 < $this->count ) {
			return true;
		} elseif ( $this->current + 1 === $this->count ) {
			do_action( 'ap_loop_end' );
			// Do some cleaning up after the loop.
			$this->rewind();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Rewind the object and reset index.
	 */
	public function rewind() {
		$this->current = -1;

		if ( $this->count > 0 ) {
			$this->object = $this->objects[0];
		}
	}

	/**
	 * Check if there are objects.
	 *
	 * @return bool
	 */
	public  function has() {
		if ( $this->count ) {
			return true;
		}

		return false;
	}

	/**
	 * Set up the next object and iterate index.
	 *
	 * @return object The next object to iterate over.
	 */
	public function next() {
		$this->current++;
		$this->object = $this->objects[ $this->current ];
		return $this->object;
	}

	/**
	 * Set up the current object inside the loop.
	 */
	public function the_object() {
		$this->in_the_loop 		= true;
		$this->object   			= $this->next();

		// Loop has just started.
		if ( 0 === $this->current ) {
			/**
			 * Fires if the current object is the first in the loop.
			 */
			do_action( 'ap_loop_start' );
		}
	}
}
