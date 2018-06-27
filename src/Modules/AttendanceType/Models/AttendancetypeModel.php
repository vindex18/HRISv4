<?php

namespace App\Modules\Attendancetype\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as DB;
//use Illuminate\Database\Eloquent\SoftDeletes; 

class AttendancetypeModel extends Model {
    //use SoftDeletes;
    
    protected $table = 'attendancetype';
    protected $primaryKey = 'id';
    protected $fillable = [
        'code', 
        'description',
    ];

    function getAllAttendanceType(){
        return DB::table('attendancetype')->get()->toJson();
    }

}

