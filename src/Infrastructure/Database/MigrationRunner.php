<?php
/**
 * Provide the MigrationRunner adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Infrastructure\Database;

use SessionFlowBookings\Infrastructure\Database\Migrations\Version0001;

/** Serialized, repeatable migration with verified publication of version. */
final class MigrationRunner {
	public const VERSION = '0001';
	/**
	 * Bind the explicitly supplied dependencies and immutable context.
	 *
	 * @param \wpdb $db Site-scoped WordPress database connection.
	 */
	public function __construct( private readonly \wpdb $db ) {}
	/**
	 * Perform the isolated lifecycle operation.
	 *
	 * @throws \RuntimeException If the lifecycle operation fails.
	 * @throws \Throwable If DDL or verification fails; the version remains unpublished.
	 */
	public function run(): void {
		$lock = 'sfbk_migrate_' . substr( hash( 'sha256', DB_NAME . ':' . $this->db->prefix ), 0, 40 );
		if ( '1' !== (string) $this->db->get_var( $this->db->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
			throw new \RuntimeException( 'Migration locked' ); }
		try {
			$installed = get_option( 'sfbk_schema_version', '' );
			if ( '' !== $installed && self::VERSION !== $installed ) {
				throw new \RuntimeException( 'Unsupported schema version' ); }
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$statements = ( new Version0001() )->statements( $this->db );
			foreach ( $statements as $index => $sql ) {
				$this->db->last_error = '';
				dbDelta( $sql );
				if ( '' !== $this->db->last_error ) {
					throw new \RuntimeException( 'Migration statement failed' ); }
				update_option( 'sfbk_migration_checkpoint', $index + 1, false );
			}
			( new SchemaVerifier() )->verify( $this->db, $statements );
			update_option( 'sfbk_schema_version', self::VERSION, false );
			delete_option( 'sfbk_migration_checkpoint' );
			delete_option( 'sfbk_schema_error' );
		} catch ( \Throwable $error ) {
			update_option( 'sfbk_schema_error', 'MIGRATION_FAILED', false );
			throw $error;
		} finally {
			$this->db->get_var( $this->db->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
	}
}
