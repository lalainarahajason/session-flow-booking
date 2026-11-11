<?php
/**
 * Provide the DtoValidator adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\WordPress\Rest;

use SessionFlowBookings\Domain\OperationException;

/** Closed JSON schema subset for runtime DTOs; no scalar coercion. */
final class DtoValidator {
	/**
	 * Validate the closed DTO recursively without scalar coercion.
	 *
	 * @param mixed  $value Value being checked against its schema.
	 * @param array  $schema Closed schema and validation constraints.
	 * @param string $path Canonical request path or JSON Pointer.
	 */
	public function validate( mixed $value, array $schema, string $path = '' ): void {
		if ( isset( $schema['anyOf'] ) || isset( $schema['oneOf'] ) ) {
			$matches = 0;
			foreach ( $schema['anyOf'] ?? $schema['oneOf'] as $branch ) {
				try {
					$this->validate( $value, $branch, $path );
					++$matches; } catch ( OperationException $error ) {
									continue; }
			}
			if ( 0 === $matches || ( isset( $schema['oneOf'] ) && 1 !== $matches ) ) {
				$this->fail( $path ); }
			return;
		}
		$type  = $schema['type'] ?? null;
		$valid = match ( $type ) {
			'object' => $value instanceof \stdClass,
			'array' => is_array( $value ) && array_is_list( $value ),
			'integer' => is_int( $value ),
			'number' => is_int( $value ) || is_float( $value ),
			'string' => is_string( $value ),
			'boolean' => is_bool( $value ),
			'null' => null === $value,
			default => false,
		};
		if ( ! $valid ) {
			$this->fail( $path ); }
		if ( isset( $schema['enum'] ) && ! in_array( $value, $schema['enum'], true ) ) {
			$this->fail( $path ); }
		if ( array_key_exists( 'const', $schema ) && $value !== $schema['const'] ) {
			$this->fail( $path ); }
		if ( 'object' === $type ) {
			foreach ( $schema['required'] ?? array() as $required ) {
				if ( ! property_exists( $value, $required ) ) {
					$this->fail( $path . '/' . $required ); }
			}
			foreach ( get_object_vars( $value ) as $key => $item ) {
				$child = $path . '/' . str_replace( array( '~', '/' ), array( '~0', '~1' ), $key );
				if ( ! isset( $schema['properties'][ $key ] ) ) {
					$this->fail( $child ); }
				$this->validate( $item, $schema['properties'][ $key ], $child );
			}
		}
		if ( 'array' === $type ) {
			if ( count( $value ) < ( $schema['minItems'] ?? 0 ) || count( $value ) > ( $schema['maxItems'] ?? PHP_INT_MAX ) ) {
				$this->fail( $path ); }
			foreach ( $value as $index => $item ) {
				$this->validate( $item, $schema['items'], $path . '/' . $index ); }
		}
		if ( is_int( $value ) || is_float( $value ) ) {
			if ( $value < ( $schema['minimum'] ?? -PHP_INT_MAX ) || $value > ( $schema['maximum'] ?? PHP_INT_MAX ) ) {
				$this->fail( $path ); }
		}
		if ( is_string( $value ) ) {
			$length = mb_strlen( $value );
			if ( $length < ( $schema['minLength'] ?? 0 ) || $length > ( $schema['maxLength'] ?? PHP_INT_MAX ) ) {
				$this->fail( $path ); }
			if ( isset( $schema['pattern'] ) && 1 !== preg_match( '~' . str_replace( '~', '\~', $schema['pattern'] ) . '~uD', $value ) ) {
				$this->fail( $path ); }
			if ( isset( $schema['format'] ) ) {
				$format_valid = match ( $schema['format'] ) {
					'uuid' => 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $value ),
					'email' => false !== filter_var( $value, FILTER_VALIDATE_EMAIL ),
					'uri' => false !== filter_var( $value, FILTER_VALIDATE_URL ),
					'date-time' => false !== \DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i:s\Z', $value, new \DateTimeZone( 'UTC' ) ) && ( false === \DateTimeImmutable::getLastErrors() ),
					default => false,
				};
				if ( ! $format_valid ) {
					$this->fail( $path ); }
			}
		}
	}
	/**
	 * Reject an invalid field with a JSON Pointer suitable for JSON responses.
	 *
	 * @param string $path Canonical request path or JSON Pointer.
	 * @throws OperationException If the operation cannot satisfy its preconditions or persist its result.
	 */
	private function fail( string $path ): never {
		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- JSON Pointer is serialized as JSON, never rendered as HTML.
		throw new OperationException(
			'VALIDATION_ERROR',
			422,
			false,
			array(
				array(
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- JSON Pointer is serialized by WP_REST_Response, not rendered as HTML.
					'path' => '' === $path ? '/' : $path,
					'code' => 'INVALID_VALUE',
				),
			)
		);
	}
}
