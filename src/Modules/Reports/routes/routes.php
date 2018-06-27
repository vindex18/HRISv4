<?php

$app->get('/reports/attendancetoexcel/{dtfrom}/{dtto}/[{accstat}]', 'ReportController:getAllEmployeeAttendanceReport');
//reports/attendance/attendancereport/toexcel/{dtfrom}/{dtto}/[{accstat}]',
