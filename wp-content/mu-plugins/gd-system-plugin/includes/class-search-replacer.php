<?php

namespace WPaaS;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

/**
 * Based on https://github.com/wp-cli/search-replace-command/tree/main
 */
class Search_Replacer {

	private $from;
	private $to;
	private $recurse_objects;
	private $max_recursion;

	/**
	 * @param string  $from            String we're looking to replace.
	 * @param string  $to              What we want it to be replaced with.
	 * @param bool    $recurse_objects Should objects be recursively replaced?
	 */
	public function __construct( $from, $to, $recurse_objects = false ) {
		$this->from            = $from;
		$this->to              = $to;
		$this->recurse_objects = $recurse_objects;

		// Get the XDebug nesting level. Will be zero (no limit) if no value is set
		$this->max_recursion = intval( ini_get( 'xdebug.max_nesting_level' ) );
	}

	/**
	 * Take a serialised array and unserialise it replacing elements as needed and
	 * unserialising any subordinate arrays and performing the replace on those too.
	 * Ignores any serialized objects unless $recurse_objects is set to true.
	 *
	 * @param array|string $data            The data to operate on.
	 * @param bool         $serialised      Does the value of $data need to be unserialized?
	 *
	 * @return array       The original array with all elements replaced as needed.
	 */
	public function run( $data, $serialised = false ) {
		return $this->run_recursively( $data, $serialised );
	}

	/**
	 * @param int          $recursion_level Current recursion depth within the original data.
	 * @param array        $visited_data    Data that has been seen in previous recursion iterations.
	 */
	private function run_recursively( $data, $serialised, $recursion_level = 0, $visited_data = array() ) {

		// some unseriliased data cannot be re-serialised eg. SimpleXMLElements
		try {

			if ( $this->recurse_objects ) {

				// If we've reached the maximum recursion level, short circuit
				if ( 0 !== $this->max_recursion && $recursion_level >= $this->max_recursion ) {
					return $data;
				}

				if ( is_array( $data ) || is_object( $data ) ) {
					// If we've seen this exact object or array before, short circuit
					if ( in_array( $data, $visited_data, true ) ) {
						return $data; // Avoid infinite loops when there's a cycle
					}
					// Add this data to the list of
					$visited_data[] = $data;
				}
			}

			// The error suppression operator is not enough in some cases, so we disable
			// reporting of notices and warnings as well.
			$error_reporting = error_reporting();
			error_reporting( $error_reporting & ~E_NOTICE & ~E_WARNING );
			$unserialized = is_string( $data ) ? @unserialize( $data ) : false;

			error_reporting( $error_reporting );

			if ( false !== $unserialized ) {
				$data = $this->run_recursively( $unserialized, true, $recursion_level + 1 );
			} elseif ( is_array( $data ) ) {
				$keys = array_keys( $data );
				foreach ( $keys as $key ) {
					$data[ $key ] = $this->run_recursively( $data[ $key ], false, $recursion_level + 1, $visited_data );
				}
			} elseif ( $this->recurse_objects && ( is_object( $data ) || $data instanceof \__PHP_Incomplete_Class ) ) {
				if ( ! ( $data instanceof \__PHP_Incomplete_Class ) ) {
					foreach ( $data as $key => $value ) {
						$data->$key = $this->run_recursively( $value, false, $recursion_level + 1, $visited_data );
					}
				}
			} elseif ( is_string( $data ) ) {

				$data = str_replace( $this->from, $this->to, $data );

			}

			if ( $serialised ) {
				return serialize( $data );
			}
		} catch ( Exception $error ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Deliberally empty.

		}

		return $data;
	}


	public function php_handle_col( $col, $table, $old, $new ) {

		list( $primary_keys, $columns, $all_columns ) = Search_Replacer::get_columns( $table );

		global $wpdb;

		$table_sql = self::esc_sql_ident( $table );
		$col_sql   = self::esc_sql_ident( $col );

		$base_key_condition = "$col_sql" . $wpdb->prepare( ' LIKE BINARY %s', '%' . self::esc_like( $old ) . '%' );
		$where_key          = "WHERE $base_key_condition";

		$escaped_primary_keys = self::esc_sql_ident( $primary_keys );
		$primary_keys_sql     = implode( ',', $escaped_primary_keys );
		$order_by_keys        = array_map(
			static function ( $key ) {
				return "{$key} ASC";
			},
			$escaped_primary_keys
		);
		$order_by_sql         = 'ORDER BY ' . implode( ',', $order_by_keys );
		$limit                = 1000;

		// 2 errors:
		// - WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- escaped through self::esc_sql_ident
		// - WordPress.CodeAnalysis.AssignmentInCondition -- no reason to do copy-paste for a single valid assignment in while
		// phpcs:ignore
		while ( $rows = $wpdb->get_results( "SELECT {$primary_keys_sql} FROM {$table_sql} {$where_key} {$order_by_sql} LIMIT {$limit}" ) ) {

			foreach ( $rows as $keys ) {
				$where_sql = '';
				foreach ( (array) $keys as $k => $v ) {
					if ( '' !== $where_sql ) {
						$where_sql .= ' AND ';
					}
					$where_sql .= self::esc_sql_ident( $k ) . ' = ' . self::esc_sql_value( $v );
				}

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- escaped through self::esc_sql_ident
				$col_value = $wpdb->get_var( "SELECT {$col_sql} FROM {$table_sql} WHERE {$where_sql}" );

				if ( '' === $col_value ) {
					continue;
				}

				$value = $this->run( $col_value );

				if ( $value === $col_value ) {
					continue;
				}

				$update_where = array();
				foreach ( (array) $keys as $k => $v ) {
					$update_where[ $k ] = $v;
				}

				$wpdb->update( $table, array( $col => $value ), $update_where );
			}
			// Because we are ordering by primary keys from least to greatest,
			// we can exclude previous chunks from consideration by adding greater-than conditions
			// to insist the next chunk's keys must be greater than the last of this chunk's keys.
			$last_row            = end( $rows );
			$next_key_conditions = array();

			// NOTE: For a composite key (X, Y, Z), selecting the next rows requires the following conditions:
			// ( X = lastX AND Y = lastY AND Z > lastZ ) OR
			// ( X = lastX AND Y > lastY ) OR
			// ( X > lastX )
			for ( $last_key_index = count( $primary_keys ) - 1; $last_key_index >= 0; $last_key_index-- ) {
				$next_key_subconditions = array();

				for ( $i = 0; $i <= $last_key_index; $i++ ) {
					$k = $primary_keys[ $i ];
					$v = $last_row->{ $k };

					if ( $i < $last_key_index ) {
						$next_key_subconditions[] = self::esc_sql_ident( $k ) . ' = ' . self::esc_sql_value( $v );
					} else {
						$next_key_subconditions[] = self::esc_sql_ident( $k ) . ' > ' . self::esc_sql_value( $v );
					}
				}

				$next_key_conditions[] = '( ' . implode( ' AND ', $next_key_subconditions ) . ' )';
			}

			$where_key_conditions = array();
			if ( $base_key_condition ) {
				$where_key_conditions[] = $base_key_condition;
			}
			$where_key_conditions[] = '( ' . implode( ' OR ', $next_key_conditions ) . ' )';

			$where_key = 'WHERE ' . implode( ' AND ', $where_key_conditions );
		}
	}

	/**
	 * Escapes (backticks) MySQL identifiers (aka schema object names) - i.e. column names, table names, and database/index/alias/view etc names.
	 * See https://dev.mysql.com/doc/refman/5.5/en/identifiers.html
	 *
	 * @param string|array $idents A single identifier or an array of identifiers.
	 * @return string|array An escaped string if given a string, or an array of escaped strings if given an array of strings.
	 */
	private static function esc_sql_ident( $idents ) {
		$backtick = static function ( $v ) {
			// Escape any backticks in the identifier by doubling.
			return '`' . str_replace( '`', '``', $v ) . '`';
		};
		if ( is_string( $idents ) ) {
			return $backtick( $idents );
		}
		return array_map( $backtick, $idents );
	}

	/**
	 * Puts MySQL string values in single quotes, to avoid them being interpreted as column names.
	 *
	 * @param string|array $values A single value or an array of values.
	 * @return string|array A quoted string if given a string, or an array of quoted strings if given an array of strings.
	 */
	private static function esc_sql_value( $values ) {
		$quote = static function ( $v ) {
			// Don't quote integer values to avoid MySQL's implicit type conversion.
			if ( preg_match( '/^[+-]?[0-9]{1,20}$/', $v ) ) { // MySQL BIGINT UNSIGNED max 18446744073709551615 (20 digits).
				return esc_sql( $v );
			}

			// Put any string values between single quotes.
			return "'" . esc_sql( $v ) . "'";
		};

		if ( is_array( $values ) ) {
			return array_map( $quote, $values );
		}

		return $quote( $values );
	}

	private static function get_columns( $table ) {
		global $wpdb;

		$table_sql       = self::esc_sql_ident( $table );
		$primary_keys    = array();
		$text_columns    = array();
		$all_columns     = array();
		$suppress_errors = $wpdb->suppress_errors();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- escaped through self::esc_sql_ident
		$results = $wpdb->get_results( "DESCRIBE $table_sql" );

		if ( ! empty( $results ) ) {
			foreach ( $results as $col ) {
				if ( 'PRI' === $col->Key ) {
					$primary_keys[] = $col->Field;
				}
				if ( self::is_text_col( $col->Type ) ) {
					$text_columns[] = $col->Field;
				}
				$all_columns[] = $col->Field;
			}
		}
		$wpdb->suppress_errors( $suppress_errors );
		return array( $primary_keys, $text_columns, $all_columns );
	}

	private static function is_text_col( $type ) {
		foreach ( array( 'text', 'varchar' ) as $token ) {
			if ( false !== stripos( $type, $token ) ) {
				return true;
			}
		}

		return false;
	}

	private static function esc_like( $old ) {
		global $wpdb;

		// Remove notices in 4.0 and support backwards compatibility
		if ( method_exists( $wpdb, 'esc_like' ) ) {
			// 4.0
			$old = $wpdb->esc_like( $old );
		} else {
			// phpcs:ignore WordPress.WP.DeprecatedFunctions.like_escapeFound -- BC-layer for WP 3.9 or less.
			$old = like_escape( esc_sql( $old ) ); // Note: this double escaping is actually necessary, even though `esc_like()` will be used in a `prepare()`.
		}

		return $old;
	}
}
