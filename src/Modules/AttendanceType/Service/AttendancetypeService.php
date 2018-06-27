<?php

namespace App\Modules\Attendancetype\Service;

use App\Modules\Attendancetype\Dao\AttendancetypeDao;

class AttendancetypeService {
    function getAllAttendanceType(){
        return AttendancetypeDao::getAllAttendanceType();
    }
}