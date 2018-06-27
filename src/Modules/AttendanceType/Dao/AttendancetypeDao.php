<?php

namespace App\Modules\Attendancetype\Dao;
use App\Modules\Attendancetype\Models\AttendancetypeModel;


class AttendancetypeDao {
    function __construct(){ 
        
    }

    function getAllAttendanceType(){
        return AttendancetypeModel::getAllAttendancetype();
    }
}