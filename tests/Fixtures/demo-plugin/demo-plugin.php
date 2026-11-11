<?php
/**
 * Plugin Name: SFBK Test Demonstrator
 * Description: Isolated test-only endpoints, never distributed.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() ) { return; }
require_once __DIR__ . '/DemoRepository.php';
require_once __DIR__ . '/DemoController.php';
add_action( 'rest_api_init', static function () { ( new SessionFlowBookingsTests\DemoController() )->register(); } );
