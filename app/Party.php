<?php

namespace App;

use App\Device;
use App\Helpers\FootprintRatioCalculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use DB;

class Party extends Model implements Auditable
{

    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'events';
    protected $primaryKey = 'idevents';
    protected $fillable = ['group', 'event_date', 'start', 'end', 'venue', 'location', 'latitude', 'longitude', 'free_text', 'pax', 'volunteers', 'hours', 'wordpress_post_id', 'created_at', 'updated_at'];
    protected $hidden = [];

    //Getters
    public function findAll() {//Tested
        return DB::select(DB::raw('SELECT
                    `e`.`idevents` AS `id`,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`,
                    `e`.`start` AS `start`,
                    `e`.`end` AS `end`,
                    `e`.`venue`,
                    `e`.`location`,
                    `e`.`latitude`,
                    `e`.`longitude`,
                    `e`.`pax`,
                    `e`.`volunteers`,
                    `e`.`free_text`,
                    `e`.`hours`,
                    `e`.`wordpress_post_id`,
                    `g`.`name` AS `group_name`,
                    `g`.`idgroups` AS `group_id`
                FROM `events` AS `e`
                INNER JOIN `groups` AS `g`
                    ON `g`.`idgroups` = `e`.`group`
                ORDER BY `e`.`start` DESC'));
    }

    public function findAllSearchable() {//Tested
        return DB::select(DB::raw('SELECT
                    `e`.`idevents` AS `id`,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`,
                    `e`.`start` AS `start`,
                    `e`.`end` AS `end`,
                    `e`.`venue`,
                    `e`.`location`,
                    `e`.`latitude`,
                    `e`.`longitude`,
                    `e`.`pax`,
                    `e`.`free_text`,
                    `e`.`hours`,
                    `g`.`name` AS `group_name`,
                    `g`.`idgroups` AS `group_id`
                FROM `events` AS `e`
                INNER JOIN `groups` AS `g`
                    ON `g`.`idgroups` = `e`.`group`
                WHERE `event_date` <= NOW()
                ORDER BY `e`.`event_date` DESC'));
    }

    public function findThis($id, $devices = false) {//Tested however with devices = true doesn't work
        $sql = 'SELECT
                    `e`.`idevents` AS `id`,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_date` ,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`end`) ) AS `event_end_timestamp`,
                    `e`.`start` AS `start`,
                    `e`.`end` AS `end`,
                    `e`.`venue`,
                    `e`.`location`,
                    `e`.`latitude`,
                    `e`.`longitude`,
                    `e`.`group`,
                    `e`.`pax`,
                    `e`.`volunteers`,
                    `e`.`hours`,
                    `e`.`free_text`,
                    `e`.`wordpress_post_id`,
                    `g`.`name` AS `group_name`,
                    `g`.`idgroups` AS `group_id`

                FROM `events` AS `e`
                INNER JOIN `groups` AS `g`
                    ON `g`.`idgroups` = `e`.`group`
                WHERE `e`.`idevents` = :id
                ORDER BY `e`.`start` DESC';

        $party =  DB::select(DB::raw($sql), array('id' => $id));

        if($devices){
            $devices = new Device;
            $party[0]->devices = $devices->ofThisEvent($party[0]->id);
        }

        return $party;
    }

    public function createUserList($party, $users){
        /** reset user list **/
        if(!self::deleteUserList($party)){
            return false;
        }
        $sql = 'INSERT INTO `events_users`(`event`, `user`) VALUES (:party, :user)';
        foreach($users as $k => &$user){

            try {
              DB::insert(DB::raw($sql), array('party' => $party, 'user' => $user));
            } catch (\Illuminate\Database\QueryException $e) {
              dd($e);
            }

        }
    }

    public function deleteUserList($party){
        return DB::delete(DB::raw('DELETE FROM `events_users` WHERE `event` = :party'), array('party' => $party));
    }

    public function ofThisUser($id, $only_past = false, $devices = false){//Tested
        $sql = 'SELECT *, `e`.`venue` AS `venue`, `e`.`location` as `location`, UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`
                FROM `' . $this->table . '` AS `e`
                INNER JOIN `events_users` AS `eu` ON `eu`.`event` = `e`.`idevents`
                INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`
                LEFT JOIN (
                    SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event`
                    FROM `devices` AS `dv`
                    GROUP BY  `dv`.`event`
                ) AS `d` ON `d`.`event` = `e`.`idevents`
                WHERE `eu`.`user` = :id';
        if($only_past == true){
            $sql .= ' AND `e`.`event_date` < NOW()';
        }
        $sql .= ' ORDER BY `e`.`event_date` DESC';

        try {
          $parties = DB::select(DB::raw($sql), array('id' => $id));
        } catch (\Illuminate\Database\QueryException $e) {
          dd($e);
        }

        if($devices){
            $devices = new Device;
            foreach($parties as $i => $party){
                $parties[$i]->devices = $devices->ofThisEvent($party->idevents);
            }

        }

        return $parties;

    }

    public function ofThisGroup2($group = 'admin', $only_past = false, $devices = false){//Tested
        $sql = 'SELECT
                    *,
	`e`.`venue` AS `venue`, `e`.`location` as `location`,
                    `g`.`name` AS group_name,


                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`

                FROM `' . $this->table . '` AS `e`

                    INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`

                    LEFT JOIN (
                        SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event`
                        FROM `devices` AS `dv`
                        GROUP BY  `dv`.`event`
                    ) AS `d` ON `d`.`event` = `e`.`idevents` ';
        //UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) )
        if(is_numeric($group) && $group != 'admin' ){
            $sql .= ' WHERE `e`.`group` = :id ';
        }

        if($only_past == true){
            $sql .= ' AND TIMESTAMP(`e`.`event_date`, `e`.`start`) < NOW()';
        }

        $sql .= ' ORDER BY `e`.`event_date` DESC';

        if(is_numeric($group) && $group != 'admin' ){
          try {
            $parties = DB::select(DB::raw($sql), array('id' => $group));
          } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
          }
        } else {
          try {
            $parties = DB::select(DB::raw($sql));
          } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
          }
        }

        if($devices){
            $devices = new Device;
            foreach($parties as $i => $party){
                $parties[$i]->devices = $devices->ofThisEvent($party->idevents);
            }

        }

        return $parties;
    }

    public function ofTheseGroups($groups = 'admin', $only_past = false, $devices = false){//Tested
        $sql = 'SELECT
                    *,
	`e`.`venue` AS `venue`, `e`.`location` as `location`,
                    `g`.`name` AS group_name,


                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`

                FROM `' . $this->table . '` AS `e`

                    INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`

                    LEFT JOIN (
                        SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event`
                        FROM `devices` AS `dv`
                        GROUP BY  `dv`.`event`
                    ) AS `d` ON `d`.`event` = `e`.`idevents` ';
        //UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) )
        if(is_array($groups) && $groups != 'admin' ){
            $sql .= ' WHERE `e`.`group` IN (' . implode(', ', $groups) . ') ';
        }

        if($only_past == true){
            $sql .= ' AND TIMESTAMP(`e`.`event_date`, `e`.`start`) < NOW()';
        }

        $sql .= ' ORDER BY `e`.`event_date` DESC';

        try {
          $parties = DB::select(DB::raw($sql));
        } catch (\Illuminate\Database\QueryException $e) {
          dd($e);
        }

        if($devices){
            $devices = new Device;
            foreach($parties as $i => $party){
                $parties[$i]->devices = $devices->ofThisEvent($party->idevents);
            }

        }

        return $parties;
    }

    public function ofThisGroup($group = 'admin', $only_past = false, $devices = false){//Tested
        $sql = 'SELECT
                    *,
	`e`.`venue` AS `venue`, `e`.`location` as `location`,


                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`

                FROM `' . $this->table . '` AS `e`

                    INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`

                    LEFT JOIN (
                        SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event`
                        FROM `devices` AS `dv`
                        GROUP BY  `dv`.`event`
                    ) AS `d` ON `d`.`event` = `e`.`idevents` ';
        //UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) )
        if(is_numeric($group) && $group != 'admin' ){
            $sql .= ' WHERE `e`.`group` = :id ';
        }

        // TODO: BUG: this does not work if you are an Admin, as the
        // where statement hasn't been built.  Could fix with a WHERE 1=1,
        // but leaving for now as we might deprecate this method anyway, and
        // not sure what effect it might have in various parts of the app.
        if($only_past == true){
            $sql .= ' AND TIMESTAMP(`e`.`event_date`, `e`.`start`) < NOW()';
        }

        $sql .= ' ORDER BY `e`.`event_date` DESC';

        if(is_numeric($group) && $group != 'admin' ){
          try {
            $parties = DB::select(DB::raw($sql), array('id' => $group));
          } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
          }
        } else {
          try {
            $parties = DB::select(DB::raw($sql));
          } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
          }
        }

        if($devices){
            $devices = new Device;
            foreach($parties as $i => $party){
                $parties[$i]->devices = $devices->ofThisEvent($party->idevents);
            }

        }

        return $parties;

    }

    public function findNextParties($group = null) {//Tested
        $sql = 'SELECT
                    `e`.`idevents`,
                    `e`.`venue`,
                    `e`.`location`,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`,
                    `e`.`event_date` AS `plain_date`,
                    NOW() AS `this_moment`,
                    `e`.`start`,
                    `e`.`end`,
                    `e`.`latitude`,
                    `e`.`longitude`
                FROM `' . $this->table . '` AS `e`

                WHERE TIMESTAMP(`e`.`event_date`, `e`.`start`) >= NOW() '; // added one day to make sure it only gets moved to the past the next day

        if(!is_null($group)){
            $sql .= ' AND `e`.`group` = :group ';
        }

        $sql .= ' ORDER BY `e`.`event_date` ASC
                LIMIT 10';

        if(!is_null($group)){
            try {
              return DB::select(DB::raw($sql), array('group' => $group));
            } catch (\Illuminate\Database\QueryException $e) {
              dd($e);
            }
        } else {
          try {
            return DB::select(DB::raw($sql));
          } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
          }
        }

    }

    public function findLatest($limit = 10) {
        return DB::select(DB::raw('SELECT
                    `e`.`idevents`,
                    `e`.`venue`,
                    `e`.`location`,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_date`,
                    `e`.`start`,
                    `e`.`end`,
                    `e`.`latitude`,
                    `e`.`longitude`
                FROM `' . $this->table . '` AS `e`
                ORDER BY `e`.`event_date` DESC
                LIMIT :limit'), array('limit' => $limit));
    }

    public function attendees(){//Tested
        return DB::select(DB::raw('SELECT SUM(pax) AS pax FROM ' . $this->table));
    }

    /**
    * Laravel specific code
    */

    public function scopeUpcomingEvents()
    {
        return $this->join('groups', 'groups.idgroups', '=', 'events.group')
                     ->join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
                     ->whereDate('event_date', '>=', date('Y-m-d'))
                     ->select('events.*')
                     ->groupBy('idevents')
                     ->orderBy('event_date', 'ASC');
    }

    public function scopeAllUpcomingEvents(){
        return $this->whereRaw('CONCAT(`event_date`, " ", `start`) > CURRENT_TIMESTAMP()')
                    ->orderByRaw('CONCAT(`event_date`, " ", `start`)');
    }

    public function scopeRequiresModeration(){
        return $this->whereNull('wordpress_post_id')
                      ->whereDate('event_date', '>=', date('Y-m-d'))
                        ->orderBy('event_date', 'ASC');
    }

    public function scopePastEvents(){
        return $this->whereNotNull('wordpress_post_id')
                      ->whereDate('event_date', '<', date('Y-m-d'))
                        ->orderBy('event_date', 'DESC');
    }

    public function allDevices(){
        return $this->hasMany('App\Device', 'event', 'idevents')->join('categories', 'categories.idcategories', '=', 'devices.category');
    }

    public function allInvited(){
        return $this->hasMany('App\EventsUsers', 'event', 'idevents')->where('status', '!=', 1);
    }

    public function host(){
        return $this->hasOne('App\Host', 'idgroups', 'group');
        }

    // Doesn't work if called 'group' - I guess because a reserved SQL keyword.
    public function theGroup(){
        return $this->hasOne('App\Group', 'idgroups', 'group');
    }

    public function getEventDate($format = 'd/m/Y') {

        return date($format, strtotime($this->event_date));

    }

    public function getEventStart() {

        return date('H:i', strtotime($this->start));

    }

    public function getEventEnd() {

        return date('H:i', strtotime($this->end));

    }

    public function getEventStartEnd() {

        return $this->getEventStart() . '-' . $this->getEventEnd();

    }

    public function getEventName() {

      if( !empty($this->venue) ) {
        return $this->venue;
      } else {
        return $this->location;
      }

    }

    public function isUpcoming() {

        $date_now     = new \DateTime();
        $event_start  = new \DateTime($this->event_date.' '.$this->start);

        if ( $date_now < $event_start )
          return true;
        else
          return false;

    }

    public function isInProgress() {

        $date_now     = new \DateTime();
        $event_start  = new \DateTime($this->event_date.' '.$this->start);
        $event_end    = new \DateTime($this->event_date.' '.$this->end);

        if ( $date_now >= $event_start && $date_now <= $event_end )
          return true;
        else
          return false;

    }

    public function hasFinished() {

        $date_now     = new \DateTime();
        $event_end    = new \DateTime($this->event_date.' '.$this->end);

        if ( $date_now > $event_end )
          return true;
        else
          return false;

    }

    public function getEventStats($emissionRatio)
    {
        $Device = new Device;

        $co2Diverted = 0;
        $ewasteDiverted = 0;
        $fixed_devices = 0;
        $repairable_devices = 0;
        $dead_devices = 0;

        if (!empty($this->allDevices)) {
            foreach ($this->allDevices as $device) {
                if ($device->isFixed()) {
                    $co2Diverted += $device->co2Diverted($emissionRatio, $Device->displacement);
                    $ewasteDiverted += $device->ewasteDiverted();
                }

                switch($device->repair_status) {
                case 1:
                    $fixed_devices++;
                    break;
                case 2:
                    $repairable_devices++;
                    break;
                case 3:
                    $dead_devices++;
                    break;
                }
            }

            return [
                'co2'                 => $co2Diverted,
                'ewaste'              => $ewasteDiverted,
                'fixed_devices'       => $fixed_devices,
                'repairable_devices'  => $repairable_devices,
                'dead_devices'        => $dead_devices,
                'participants'        => $this->pax,
                'volunteers'          => $this->volunteers
            ];
        }
    }

    public function devices(){
        return $this->hasMany('App\Device', 'event', 'idevents');
    }

    public function hoursVolunteered()
    {
        $lengthOfEventInHours = 3;
        $extraHostHours = 9;
        $hoursIfNoVolunteersRecorded = 12;

        $hoursVolunteered = $extraHostHours;

        if ($this->volunteers > 0) {
            $hoursVolunteered += $this->volunteers * $lengthOfEventInHours;
        } else {
            $hoursVolunteered += $hoursIfNoVolunteersRecorded;
        }

        return $hoursVolunteered;
    }

    public function getEventStartTimestampAttribute()
    {
        return strtotime($this->event_date . ' ' . $this->start);
    }
}
