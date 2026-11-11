<?php
/** Development-only independent PHP processes synchronized over pipes. Never packaged. */
final class SfbkConcurrentRunner {
 public static function mutate(string $id,string $key,string $mode='child'): array {
  $processes=array();$allpipes=array();
  for($i=0;$i<2;$i++) {
   $pipes=array();$process=proc_open(array(PHP_BINARY,__FILE__,$mode,$id,$key),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
   if(!is_resource($process)){throw new RuntimeException('Could not create concurrent process');}
   $processes[]=$process;$allpipes[]=$pipes;
  }
  foreach($allpipes as $pipes){$ready=fgets($pipes[1]);if(trim($ready??'')!=='READY'){throw new RuntimeException('Child did not reach barrier: '.$ready.stream_get_contents($pipes[2]));}}
  foreach($allpipes as $pipes){fwrite($pipes[0],"GO\n");fclose($pipes[0]);}
  $results=array();foreach($processes as $index=>$process){$output=stream_get_contents($allpipes[$index][1]);$error=stream_get_contents($allpipes[$index][2]);fclose($allpipes[$index][1]);fclose($allpipes[$index][2]);if(proc_close($process)!==0){throw new RuntimeException($error);} $results[]=json_decode($output,true,512,JSON_THROW_ON_ERROR);}
  return $results;
 }
}
if(in_array(($argv[1]??''),array('child','claim'),true)) {
 require dirname(__DIR__).'/bootstrap-integration.php';
 $request=new WP_REST_Request('PUT','/sfbk-test/v1/resources/'.$argv[2]);$request->set_url_params(array('id'=>$argv[2]));$request->set_body('{"label":"concurrent"}');$request->set_header('X-WP-Nonce',wp_create_nonce('wp_rest'));$request->set_header('If-Match','"1"');$request->set_header('Idempotency-Key',$argv[3]);
 fwrite(STDOUT,"READY\n");if(trim(fgets(STDIN))!=='GO'){exit(2);}
 if($argv[1]==='claim'){global $wpdb;$job=(new SessionFlowBookings\Infrastructure\Database\WpdbJobStore($wpdb))->claim();fwrite(STDOUT,json_encode(array('id'=>$job['id']??null),JSON_THROW_ON_ERROR));exit(0);}
 $response=(new SessionFlowBookingsTests\DemoController())->handle($request);fwrite(STDOUT,json_encode(array('status'=>$response->get_status(),'data'=>$response->get_data(),'headers'=>$response->get_headers()),JSON_THROW_ON_ERROR));
}
