<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserGroups extends Model
{

    protected $table = 'users_groups';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    //Table Relations


    // Setters
    /**
     * create associations
     * @ int $i
     duser
     * @ array $groups (group ids)
     * return boolean
     * */
    public function createUsersGroups($iduser, $groups){
        if(!self::deleteUsersGroups($iduser)){
            return false;
        }
        else {
            $sql = 'INSERT INTO `users_groups` (`user`, `group`) VALUES (:user, :group)';

            foreach($groups as $k => &$group){
                try {
                  DB::insert(DB::raw($sql), array('user' => $iduser, 'group' => $group));
                } catch (\Illuminate\Database\QueryException $e) {
                  if (env('APP_ENV') == "local" || env('APP_ENV') == "development") {
                    dd($e);
                  }
                  return false;
                }
            }

            return true;
        }
    }


    //Getters
    /**
     * delete associations by user
     * @ int $iduser
     * return boolean
     * */
    public function deleteUsersGroups($iduser){
        $sql = 'DELETE FROM `users_groups` WHERE `user` = :id';
        try {
          DB::delete(DB::raw($sql), array('user' => $iduser));
          return true;
        } catch (\Illuminate\Database\QueryException $e) {
          if (env('APP_ENV') == "local" || env('APP_ENV') == "development") {
            dd($e);
          }
          return false;
        }
    }


}