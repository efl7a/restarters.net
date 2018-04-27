<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

class Search extends Model
{

  public function parties($list = array(), $groups = array(), $from = null, $to = null){
      $conditions = array();

      $sql .= 'SELECT
                *,
                `e`.`location` AS `venue`,
                UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`
              FROM `events` AS `e`

              INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`

              LEFT JOIN (
                SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event`
                FROM `devices` AS `dv`
                GROUP BY  `dv`.`event`
              ) AS `d` ON `d`.`event` = `e`.`idevents`
              WHERE 1=1 ';



      if(!empty($list)){
        $conditions[] = ' `e`.`idevents` IN (' . implode(', ', $list). ') ' ;
      }

      //        TIMESTAMP(`e`.`event_date`, `e`.`start`) >= NOW() '; // added one day to make sure it only gets moved to the past the next day


      if(!is_null($groups)){
          $conditions[] = ' `e`.`group` IN (' . implode(', ', $groups) . ') ';
      }
      if(!is_null($from)){
        $conditions[] = ' UNIX_TIMESTAMP(`e`.`event_date`) >= ' . $from ;
      }
      if(!is_null($to)){
        $conditions[] = ' UNIX_TIMESTAMP(`e`.`event_date`) <= ' . $to ;
      }
      if(!empty($conditions)) {
        $sql .= ' AND ' . implode(' AND ', $conditions);
      }
      $sql .= ' ORDER BY `e`.`event_date` DESC';

      $parties = DB::select(DB::raw($sql));

      $devices = new Device;
      foreach($parties as $i => $party){
        $parties[$i]->devices = $devices->ofThisEvent($party->idevents);
      }

      return $parties;

    }

    public function deviceStatusCount($parties){
        $sql = 'SELECT COUNT(*) AS `counter`, `d`.`repair_status` AS `status`, `d`.`event`
                FROM `devices` AS `d`';
        $sql .= ' WHERE `repair_status` > 0 ';
        $sql .= ' AND `d`.`event` IN (' . implode( ', ', $parties ). ')';
        $sql .= ' GROUP BY `status`';

        return DB::select(DB::raw($sql));
    }

    public function countByCluster($parties, $cluster){
        $sql = 'SELECT COUNT(*) AS `counter`, `repair_status` FROM `devices` AS `d`
                INNER JOIN `events` AS `e`
                    ON `d`.`event` = `e`.`idevents`
                INNER JOIN `categories` AS `c`
                    ON `d`.`category` = `c`.`idcategories`
                WHERE `c`.`cluster` = :cluster AND `d`.`repair_status` > 0 ';
        $sql .= ' AND `d`.`event` IN (' . implode( ', ', $parties ) . ') ';
        $sql.= '  GROUP BY `repair_status`
                  ORDER BY `repair_status` ASC
                ';
        try {
          return DB::select(DB::raw($sql), array('cluster' => $cluster));
        } catch (\Illuminate\Database\QueryException $e) {
          dd($e);
        }

    }

    public function findMostSeen($parties, $status = 1, $cluster = null ){
        $sql = 'SELECT COUNT(`d`.`category`) AS `counter`, `c`.`name` FROM `devices` AS `d`
                INNER JOIN `events` AS `e`
                    ON `d`.`event` = `e`.`idevents`
                INNER JOIN `categories` AS `c`
                    ON `d`.`category` = `c`.`idcategories`
                WHERE 1=1 and `c`.`idcategories` <> ' . MISC_CATEGORY_ID;
        $sql .= ' AND `d`.`event` IN (' . implode( ', ', $parties ) . ')';


        if(!is_null($status) && is_numeric($status)){
            $sql .= ' AND `d`.`repair_status` = :status ';
        }
        if(!is_null($cluster) && is_numeric($cluster)){
            $sql .= ' AND `c`.`cluster` = :cluster ';
        }

        $sql.= ' GROUP BY `d`.`category`
                ORDER BY `counter` DESC';
        $sql .= (!is_null($cluster) ? '  LIMIT 1' : '');

        try {
          if(!is_null($status) && is_numeric($status) && is_null($cluster)){
              return DB::select(DB::raw($sql), array('status' => $status));
          } elseif(!is_null($cluster) && is_numeric($cluster) && is_null($status)){
              return DB::select(DB::raw($sql), array('cluster' => $cluster));
          } elseif(!is_null($status) && is_numeric($status) && !is_null($cluster) && is_numeric($cluster)) {
              return DB::select(DB::raw($sql), array('status' => $status, 'cluster' => $cluster));
          } else {
              return DB::select(DB::raw($sql));
          }
        } catch (\Illuminate\Database\QueryException $e) {
          dd($e);
        }

    }


}
