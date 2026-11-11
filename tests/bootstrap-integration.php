<?php
// This bootstrap always uses the dedicated MySQL test database, never wp-env production data.
$_SERVER['HTTP_HOST'] = 'localhost:8897';
require dirname(__DIR__) . '/vendor/autoload.php';
define('ABSPATH', '/var/www/html/');
define('DB_NAME', 'sfbk_test');
define('DB_USER', 'sfbk');
define('DB_PASSWORD', 'sfbk_local_only');
define('DB_HOST', 'sfbk-sprint1-mysql-1:3306');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
define('WP_INSTALLING', true);
define('WP_ENVIRONMENT_TYPE', 'local');
define('WP_DEBUG', false);
define('WP_SITEURL', 'http://localhost:8897');
define('WP_HOME', 'http://localhost:8897');
define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
define('WP_CONTENT_URL', 'http://localhost:8897/wp-content');
$table_prefix = 'sfbktest_';
require ABSPATH . 'wp-settings.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
if (!is_blog_installed()) { wp_install('SFBK test','sfbktest','test@example.test',false,'','sfbk_test_only_password'); }
if ((int)get_option('db_version') !== (int)$wp_db_version) { wp_upgrade(); }
require dirname(__DIR__) . '/session-flow-bookings.php';
wp_set_current_user(1);
SessionFlowBookings\WordPress\Lifecycle::activate();
require __DIR__ . '/Fixtures/demo-plugin/DemoRepository.php';
require __DIR__ . '/Fixtures/demo-plugin/DemoController.php';
require __DIR__ . '/Support/FixtureFactory.php';
