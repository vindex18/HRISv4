<?php

$app->get('/attendance/allemployee/{dtfrom}/{dtto}/[{accstat}]', 'AttendanceController:getAllEmployeeAttendance'); //:[0-9]+ //:[0-9]+
$app->get('/attendance/employee/{dtfrom}/{dtto}/{emp_id}', 'AttendanceController:getEmployeeAttendance'); //:[0-9]+
$app->get('/attendance/employee-summary/{dtfrom}/{dtto}/{emp_id}', 'AttendanceController:getEmployeeAttendanceSummary'); //:[0-9]+
$app->delete('/attendance/employee/{att_id}', 'AttendanceController:deleteEmployeeAttendance');
$app->post('/attendance/employee', 'AttendanceController:addAttendance');
$app->post('/authenticate/token', function($req, $res, $args){
    return $next($req, $res);
});
$app->get('/attendance/timelogstatus', 'AttendanceController:getTimeLogStatus');
$app->get('/attendance/absences/{dtfrom}/{dtto}/{emp_id}', 'AttendanceController:getEmployeeAbsences');
