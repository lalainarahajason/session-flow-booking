<?php
/** Local diagnostic only, excluded from the distributable. Invoked via WP-CLI eval-file. */
$sfbk_start=microtime(true);$sfbk_queries=get_num_queries();$sfbk_memory=memory_get_usage();
$sfbk_screen=isset($args[0])?$args[0]:'foreign';
if($sfbk_screen==='plugin') {
 ob_start();(new SessionFlowBookings\WordPress\Admin\Menu())->render('dashboard','read','Dashboard');ob_end_clean();
} else {
 $sfbk_user=wp_get_current_user();$sfbk_site=get_bloginfo('name');
}
echo wp_json_encode(array('screen'=>$sfbk_screen,'elapsed_ms'=>round((microtime(true)-$sfbk_start)*1000,3),'queries'=>get_num_queries()-$sfbk_queries,'memory_delta'=>memory_get_usage()-$sfbk_memory,'peak_memory'=>memory_get_peak_usage(),'total_queries'=>get_num_queries())).PHP_EOL;
