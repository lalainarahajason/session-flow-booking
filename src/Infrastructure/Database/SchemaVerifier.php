<?php
/**
 * Provide the SchemaVerifier adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Infrastructure\Database;

/** Verify physical storage, columns and indexes after DDL. */
final class SchemaVerifier {
	/**
	 * Check required storage engine, columns and ordered unique indexes.
	 *
	 * @param \wpdb $db Site-scoped WordPress database connection.
	 * @param array $statements Expected initial table definitions.
	 * @throws \RuntimeException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function verify( \wpdb $db, array $statements ): void {
		foreach ( $statements as $sql ) {
			preg_match( '/CREATE TABLE ([a-zA-Z0-9_]+)/', $sql, $match );
			$table  = $match[1];
			$engine = $db->get_var( $db->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table ) );
			if ( 'InnoDB' !== $engine ) {
				throw new \RuntimeException( 'Transactional table missing' ); }
			$columns = $db->get_col( $db->prepare( 'SHOW COLUMNS FROM %i', $table ) );
			preg_match_all( '/^([a-z_]+) (?:bigint|int|varchar|char|longtext|datetime)/m', $sql, $required );
			if ( array_diff( $required[1], $columns ) ) {
				throw new \RuntimeException( 'Incomplete columns' ); }
			$indexes = $db->get_results( $db->prepare( 'SHOW INDEX FROM %i', $table ), ARRAY_A );
			preg_match_all( '/(?:UNIQUE KEY|KEY) ([a-z_]+) \(([^)]+)\)/', $sql, $keys, PREG_SET_ORDER );
			foreach ( $keys as $key ) {
				$actual = array_values( array_filter( $indexes, static fn( $row ) => $row['Key_name'] === $key[1] ) );
				if ( array_column( $actual, 'Column_name' ) !== explode( ',', $key[2] ) || ( str_starts_with( $key[0], 'UNIQUE' ) && 0 !== (int) ( $actual[0]['Non_unique'] ?? 1 ) ) ) {
					throw new \RuntimeException( 'Incomplete index' ); }
			}
		}
	}
}
