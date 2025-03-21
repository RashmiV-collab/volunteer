<?php

namespace WPDataAccess\API {

	use WPDataAccess\Connection\WPDADB;
	use WPDataAccess\Data_Dictionary\WPDA_Dictionary_Lists;

	class WPDA_Tree {

		public static function get_dbs() {
			// Local databases
			global $wpdb;
			$local = $wpdb->get_results("
					SELECT  schema_name AS dbs,
							'local' AS dbs_type
					   FROM information_schema.schemata
					  ORDER BY schema_name
			  	",
				'ARRAY_A'
			);

			for ( $i=0; $i < count( $local ); $i++ ) {
				if ( $wpdb->dbname === $local[ $i ]['dbs'] ) {
					// WordPress database
					$local[ $i ]['dbs_type'] = 'wp';
				}
				// System databases
				if (
					'information_schema' === $local[ $i ]['dbs'] ||
					'mysql' === $local[ $i ]['dbs'] ||
					'performance_schema' === $local[ $i ]['dbs'] ||
					'sys' === $local[ $i ]['dbs']
				) {
					// WordPress database
					$local[ $i ]['dbs_type'] = 'system';
				}
			}

			// Remote databases
			$remote = array();
			$rdb = WPDADB::get_remote_databases();
			foreach ( $rdb as $key => $val ) {
				$remote[] = array(
					'dbs'      => $key,
					'dbs_type' => 'remote',
				);
			}
			asort( $remote );//phpcs:ignore - 8.1 proof

			return WPDA_API::WPDA_Rest_Response(
				'',
				array_merge( $local, $remote ) //phpcs:ignore - 8.1 proof
			);
		}

		public static function get_tbl( $dbs ) {

			$wpdadb = WPDADB::get_db_connection( $dbs );
			if ( $wpdadb === null ) {
				return WPDA_API::WPDA_Rest_Response(
					'',
					array()
				);
			}

			$query = $wpdadb->prepare("
					select table_name as table_name
					  from information_schema.tables
					 where table_schema = %s
					   and table_type not in ('VIEW', 'SYSTEM VIEW')
					 order by table_name
				",
				array(
					$wpdadb->dbname,
				)
			);

			return WPDA_API::WPDA_Rest_Response(
				'',
				array_column(
					$wpdadb->get_results( $query, 'ARRAY_A' ), // phpcs:ignore Standard.Category.SniffName.ErrorCode
					'table_name'
				)
			);

		}

		public static function get_vws( $dbs ) {

			$wpdadb = WPDADB::get_db_connection( $dbs );
			if ( $wpdadb === null ) {
				return WPDA_API::WPDA_Rest_Response(
					'',
					array()
				);
			}

			$query = $wpdadb->prepare("
					select table_name as view_name
					  from information_schema.tables
					 where table_schema = %s
					   and table_type in ('VIEW', 'SYSTEM VIEW')
					 order by table_name
				",
				array(
					$wpdadb->dbname,
				)
			);

			return WPDA_API::WPDA_Rest_Response(
				'',
				array_column(
				$wpdadb->get_results( $query, 'ARRAY_A' ), // phpcs:ignore Standard.Category.SniffName.ErrorCode
				'view_name'
				)
			);

		}

		public static function get_cls( $dbs, $tbl ) {

			$wpdadb = WPDADB::get_db_connection( $dbs );
			if ( $wpdadb === null ) {
				return WPDA_API::WPDA_Rest_Response(
					'',
					array()
				);
			}

			$query = $wpdadb->prepare("
	              SELECT column_name AS column_name,
	                     extra AS extra,
	                     column_type AS column_type,
	                     is_nullable AS is_nullable,
	                     IF(LEFT(column_default,1)='\'' AND RIGHT(column_default,1)='\'', SUBSTR(column_default,2,LENGTH(column_default)-2), column_default) AS column_default,
	              		 character_maximum_length AS character_maximum_length,
	                     numeric_scale AS numeric_scale,
	                     numeric_precision AS numeric_precision,
	                     ordinal_position AS ordinal_position
	                FROM information_schema.columns
	               WHERE table_schema = %s
	                 AND table_name   = %s
	               ORDER BY ordinal_position
	            ",
				array(
					$dbs,
					$tbl,
				)
			);

			return WPDA_API::WPDA_Rest_Response(
				'',
				$wpdadb->get_results( $query, 'ARRAY_A' ) // phpcs:ignore Standard.Category.SniffName.ErrorCode
			);

		}

		public static function get_idx( $dbs, $tbl ) {

			$wpdadb = WPDADB::get_db_connection( $dbs );
			if ( $wpdadb === null ) {
				return WPDA_API::WPDA_Rest_Response(
					'',
					array()
				);
			}

			$query = $wpdadb->prepare("
					SELECT index_name AS index_name,
						   IF(non_unique=0, 'YES', 'NO') AS non_unique,
						   seq_in_index AS seq_in_index,
						   column_name AS column_name,
						   collation AS collation,
						   nullable AS nullable,
						   index_type AS index_type,
						   cardinality AS cardinality
					FROM   information_schema.statistics
					WHERE  table_schema = %s
					  AND  table_name   = %s
					ORDER BY index_name, seq_in_index
				",
				array(
					$dbs,
					$tbl,
				)
			);

			return WPDA_API::WPDA_Rest_Response(
				'',
				$wpdadb->get_results( $query, 'ARRAY_A' ) // phpcs:ignore Standard.Category.SniffName.ErrorCode
			);

		}

		public static function get_trg( $dbs, $tbl ) {

			$wpdadb = WPDADB::get_db_connection( $dbs );
			if ( $wpdadb === null ) {
				return WPDA_API::WPDA_Rest_Response(
					'',
					array()
				);
			}

			$query = $wpdadb->prepare('
					SELECT trigger_name AS trigger_name,
						   event_manipulation AS event_manipulation,
						   action_timing AS action_timing,
						   action_statement AS action_statement
					FROM   information_schema.triggers
					WHERE  event_object_schema = %s
					  AND  event_object_table  = %s
					ORDER BY trigger_name
				',
				array(
					$dbs,
					$tbl,
				)
			);

			return WPDA_API::WPDA_Rest_Response(
				'',
				$wpdadb->get_results( $query, 'ARRAY_A' ) // phpcs:ignore Standard.Category.SniffName.ErrorCode
			);

		}

		public static function get_frk( $dbs, $tbl ) {

			$wpdadb = WPDADB::get_db_connection( $dbs );
			if ( $wpdadb === null ) {
				return WPDA_API::WPDA_Rest_Response(
					'',
					array()
				);
			}

			$query = $wpdadb->prepare('
					select constraint_name AS constraint_name,
						   column_name AS column_name,
						   referenced_table_name AS referenced_table_name,
						   referenced_column_name AS referenced_column_name
					from   information_schema.key_column_usage
					where table_schema = %s
					  and table_name   = %s
					  and referenced_table_name is not null
					order by ordinal_position
				',
				array(
					$dbs,
					$tbl,
				)
			);

			return WPDA_API::WPDA_Rest_Response(
				'',
				$wpdadb->get_results( $query, 'ARRAY_A' ) // phpcs:ignore Standard.Category.SniffName.ErrorCode
			);

		}

		public static function get_prc( $dbs ) {

			return self::get_rtn( $dbs, 'PROCEDURE' );
		}

		public static function get_fnc( $dbs ) {

			return self::get_rtn( $dbs, 'FUNCTION' );
		}

		private static function get_rtn( $dbs, $typ ) {

			$wpdadb = WPDADB::get_db_connection( $dbs );
			if ( $wpdadb === null ) {
				return WPDA_API::WPDA_Rest_Response(
					'',
					array()
				);
			}

			$query = $wpdadb->prepare('
					SELECT routine_name AS routine_name,
					       routine_definition AS routine_definition
					  FROM information_schema.routines
					 WHERE routine_schema = %s
					   AND routine_type   = %s
				',
				array(
					$dbs,
					$typ,
				)
			);

			return WPDA_API::WPDA_Rest_Response(
				'',
				$wpdadb->get_results( $query, 'ARRAY_A' ) // phpcs:ignore Standard.Category.SniffName.ErrorCode
			);

		}

	}

}