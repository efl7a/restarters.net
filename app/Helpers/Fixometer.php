<?php

namespace App\Helpers;

class FixometerHelper {

  public static function allAges() {

    return [
      'N/A'   => 'N/A',
      '16-20' => '16-20',
      '20-30' => '20-30',
      '30-40' => '30-40',
      '40-50' => '40-50',
      '50-60' => '50-60',
      '60-70' => '60-70',
    ];

  }

  /** checks if user has a role **/
  public static function hasRole($user, $role){

        if($user->role()->first()->role == 'Root'){
            return true;
        }
        else {
            if($user->role()->first()->role == ucwords($role)){
                return true;
            }
            else {
                return false;
            }
        }
    }

}

?>
