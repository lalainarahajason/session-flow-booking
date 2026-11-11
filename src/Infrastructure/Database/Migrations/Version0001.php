<?php
/**
 * Provide the Version0001 adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Infrastructure\Database\Migrations;

use SessionFlowBookings\Infrastructure\Database\TableNames;

/** Initial infrastructure only. No business reservation tables. */
final class Version0001 {
	/**
	 * Return the initial infrastructure DDL using fixed names and site collation.
	 *
	 * @param \wpdb $db Site-scoped WordPress database connection.
	 * @return array Operation result according to the adapter contract.
	 */
	public function statements( \wpdb $db ): array {
		$names       = new TableNames( $db );
		$collation   = $db->get_charset_collate();
		$definitions = array(
			'idempotency_records' => 'site_id bigint unsigned NOT NULL,
principal_scope varchar(100) NOT NULL,
operation_id varchar(100) NOT NULL,
request_key char(36) NOT NULL,
fingerprint char(64) NOT NULL,
state varchar(20) NOT NULL,
lease_token char(36) NOT NULL,
lease_until datetime NOT NULL,
result_reference longtext NULL,
expires_at datetime NOT NULL,
UNIQUE KEY intention (site_id,principal_scope,operation_id,request_key),
KEY expiry (state,expires_at)',
			'provider_inbox'      => 'provider varchar(32) NOT NULL,
account varchar(100) NOT NULL,
mode varchar(8) NOT NULL,
event_id varchar(150) NOT NULL,
payload_protected longtext NOT NULL,
received_at datetime NOT NULL,
processed_at datetime NULL,
state varchar(20) NOT NULL,
UNIQUE KEY provider_event (provider,account,mode,event_id),
KEY processing (state,received_at)',
			'domain_outbox'       => 'event_id char(36) NOT NULL,
site_id bigint unsigned NOT NULL,
aggregate_type varchar(60) NOT NULL,
aggregate_id char(36) NOT NULL,
aggregate_version bigint unsigned NOT NULL,
event_type varchar(80) NOT NULL,
event_discriminator varchar(80) NOT NULL,
schema_version int unsigned NOT NULL,
payload_minimal longtext NOT NULL,
created_at datetime NOT NULL,
lease_token char(36) NULL,
lease_until datetime NULL,
delivered_at datetime NULL,
UNIQUE KEY event_id (event_id),
UNIQUE KEY aggregate_event (site_id,aggregate_id,aggregate_version,event_type,event_discriminator),
KEY delivery (delivered_at,lease_until)',
			'jobs'                => 'site_id bigint unsigned NOT NULL,
kind varchar(60) NOT NULL,
principal_scope varchar(100) NOT NULL,
source_scope_snapshot longtext NOT NULL,
state varchar(20) NOT NULL,
job_cursor longtext NULL,
lease_token char(36) NULL,
lease_until datetime NULL,
completed_items bigint unsigned NOT NULL DEFAULT 0,
total_items bigint unsigned NOT NULL DEFAULT 0,
attempt_count int unsigned NOT NULL DEFAULT 0,
available_at datetime NOT NULL,
error_code varchar(80) NULL,
file_ref varchar(255) NULL,
expires_at datetime NULL,
KEY runnable (site_id,state,available_at,lease_until)',
			'audit_events'        => 'site_id bigint unsigned NOT NULL,
request_id char(36) NOT NULL,
operation_id varchar(100) NOT NULL,
actor_type varchar(20) NOT NULL,
actor_id bigint unsigned NOT NULL,
action varchar(100) NOT NULL,
subject_id char(36) NOT NULL,
version_before bigint unsigned NOT NULL,
version_after bigint unsigned NOT NULL,
occurred_at datetime NOT NULL,
KEY subject (site_id,subject_id,occurred_at)',
		);
		$sql         = array();
		foreach ( $definitions as $suffix => $definition ) {
			$table = $names->get( $suffix );
			$sql[] = "CREATE TABLE $table (
id char(36) NOT NULL,
$definition,
PRIMARY KEY  (id)
) ENGINE=InnoDB $collation;";
		}
		return $sql;
	}
}
