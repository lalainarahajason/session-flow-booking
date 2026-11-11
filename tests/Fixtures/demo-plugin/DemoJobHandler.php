<?php
namespace SessionFlowBookingsTests;

/** Test-only outbox consumer, never part of the distributed runtime. */
final class DemoJobHandler {
 public function __construct(private readonly \wpdb $db) {}
 public function __invoke(array $job): void {
  $scope=json_decode($job['source_scope_snapshot'],true,512,JSON_THROW_ON_ERROR);
  $table=(new \SessionFlowBookings\Infrastructure\Database\TableNames($this->db))->get('domain_outbox');
  $event=$this->db->get_row($this->db->prepare('SELECT * FROM %i WHERE event_id=%s AND site_id=%d FOR UPDATE',$table,$scope['event_id'],get_current_blog_id()),ARRAY_A);
  if(!$event){throw new \RuntimeException('Missing event');}
  if($event['delivered_at']!==null){return;}
  $resource=(new DemoRepository($this->db))->get($event['aggregate_id'],true);
  if(!$resource){throw new \RuntimeException('Missing aggregate');}
  (new DemoRepository($this->db))->update($resource['id'],(int)$resource['version'],'consumed');
  if(1!==$this->db->update($table,array('delivered_at'=>gmdate('Y-m-d H:i:s')),array('event_id'=>$event['event_id'],'site_id'=>get_current_blog_id()),array('%s'),array('%s','%d'))){throw new \RuntimeException('Event ack failed');}
 }
}
