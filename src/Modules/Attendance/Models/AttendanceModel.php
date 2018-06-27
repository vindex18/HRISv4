<?php

namespace App\Modules\Attendance\Models;
use App\Modules\Employee\Models;
use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes; 

class AttendanceModel extends Model {
    //use SoftDeletes;
    
    protected $table = 'attendance';
    protected $primaryKey = 'id';
    protected $fillable = [
        'type_id', 
        'datetime',
        'emp_id'
    ];

    public function getAllEmployeeAttendance(){
        return $this->all();
    }

    public function employee(){
        return $this->belongsTo('App\Modules\Employee\Models\EmployeeModel', 'id')->withDefault(); 
    }
}

