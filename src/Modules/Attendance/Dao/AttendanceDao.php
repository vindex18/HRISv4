<?php

namespace App\Modules\Attendance\Dao;
use App\Modules\Attendance\Models\AttendanceModel;
use App\Modules\Employee\Dao\EmployeeDao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as DB;
use \DateTime;

class AttendanceDao {
    function __construct(){ 
        
    }

    function getMinPunch($emp_id){
        $qry = DB::table('attendance as a')
                    ->select(DB::raw('MIN(datetime) as datetime'))
                    ->where('emp_id', $emp_id)
                    ->get()
                    ->toArray();

        return (!is_null($qry[0]->datetime)) ? $qry[0]->datetime : date('Y-m-d H:i:s'); 
    }

    function getMaxPunch($emp_id){
        $qry = DB::table('attendance as a')
                    ->select(DB::raw('MAX(datetime) as datetime'))
                    ->where('emp_id', $emp_id)
                    ->get()
                    ->toArray();

        return (!is_null($qry[0]->datetime)) ? $qry[0]->datetime : date('Y-m-d H:i:s'); 
    }

    function getMinTimeIn($from, $to, $emp_id){
        //DB::connection()->enableQueryLog();
        return DB::table('attendance as a')
                        ->select(DB::raw('MIN(datetime) as datetime'))
                        ->where('emp_id', $emp_id)
                        ->where('type_id', 1)
                        //->whereBetween('datetime', [$from, $to])
                        ->where('datetime', '>=', $from)
                        ->orderBy('datetime', 'ASC')
                        ->get()
                        ->toArray();
        //$queries = DB::getQueryLog(); var_dump($queries); die("End of Query");
    }

    function getMaxTimeOut($from, $to, $emp_id){
        //check if has timein in near last if no timeout check next transaction if has timeout
        //get last date then check the last time in
        $max = DB::table('attendance')
                            ->select(DB::raw('MAX(datetime) as datetime'))
                            ->where('emp_id', $emp_id)
                            ->whereDate('datetime','<=', $to)
                            ->get()
                            ->toArray();
        

        $max = (!is_null($max[0]->datetime)) ? $max[0]->datetime : date('Y-m-d H:i:s');

        $max_on_select = DB::table('attendance')
                            ->select('type_id', 'datetime')
                            ->where('emp_id', $emp_id)
                            ->where('datetime','=', $max)
                            ->get()
                            ->toArray();

        if(empty($max_on_select)){
            return $max;
        }else{
            if($max_on_select[0]->type_id==1){  //if almost max or last == TO (OKAY) else check next day
                return $max_on_select[0]->datetime;
            }
            else
            {
                //Find Next TO
                $max = DB::table('attendance')
                        ->select('datetime','type_id')
                        ->where('emp_id', $emp_id)
                        ->where('datetime', '>=', $max_on_select[0]->datetime)
                        ->get()
                        ->toArray();

                for($c=0;$c<count($max);$c++){
                    if($max[$c]->type_id==4)
                        return $max[$c]->datetime;
                }

                return $max_on_select[0]->datetime;
            }
        }
    }

    function getNextPunch($type_id){
        switch($type_id){
            case 1: return 2; break;
            case 2: return 3; break;
            case 3: return 4; break;
            case 4: return 1; break;
            default: return 1; 
        }
    }

    function getPunchDescription($type_id){
        switch($type_id){
            case 1: return "Time-In"; break;
            case 2: return "Break-Out"; break;
            case 3: return "Break-In"; break;
            case 4: return "Time-Out"; break;
            default: return null;
        }
    }

    function insertAttendance($arr){
        return AttendanceModel::firstOrcreate($arr)->save();
    }

    function addAttendance($type_id, $emp_id){
     
        $datetime = date('Y-m-d H:i:s');
        $last_punch = AttendanceDao::getLastPunch($emp_id);
        $last_punchdt = (empty($last_punch)) ? null : $last_punch[0]->datetime;
        $last_punch = (empty($last_punch)) ? null : $last_punch[0]->type_id;

        // echo "FIRST LAST PUNCH";
        // var_dump($last_punch);
        // echo "LAST PUNCH";        
        // die();
        $qry = false;
        $arr = array('datetime' => $datetime,
                     'emp_id' => $emp_id,
                     'type_id' => (is_null($last_punch)) ? 1 : $type_id,
                     'created_at' => date('Y-m-d H:i:s'),
                     'updated_at' => date('Y-m-d H:i:s')
                );
     
        if(strtotime($last_punchdt)<strtotime($arr['datetime'])&&(!is_null($last_punchdt)&&!is_null($last_punch))){ //Checking within Time line
            if(is_null($last_punch)){
                $qry = AttendanceDao::insertAttendance($arr);
            }else{
                switch($last_punch){ //Checking Last Punch
                    case 1: $qry = ($type_id=="2"||$type_id=="4") ? AttendanceDao::insertAttendance($arr) : false; // Only Break Out & Time Out
                            $stat = ($type_id=="2") ? "Break-Out" : "Time-Out";
                            $msg = ($qry) ? $stat." Successfully! at ".date('M d, Y g:i:s A', strtotime($arr['datetime'])) : "Error Encountered on ".$stat."at  ".date('M d, Y g:i:s A', strtotime($arr['datetime']));
                            break;

                    case 2:  if($type_id=="3"){ //Only Break In 
                                    $qry = AttendanceDao::insertAttendance($arr);
                                    $msg = ($qry) ? "Break-In Successfully! at ".date('M d, Y g:i:s A', strtotime($arr['datetime'])) : "Error Encountered on Break-In! at  ".date('M d, Y g:i:s A', strtotime($arr['datetime']));
                                }elseif($type_id=="4"){ //Time Out
                                    $arr1 = array('datetime' => date('Y-m-d H:i:s', strtotime($datetime)-1),
                                                'emp_id' => $emp_id,
                                                'type_id' => 3,
                                                'created_at' => date('Y-m-d H:i:s'),
                                                'updated_at' => date('Y-m-d H:i:s')
                                                );

                                    $qry = AttendanceDao::insertAttendance($arr1);
                                    $qry = ($qry) ? AttendanceDao::insertAttendance($arr) : false;
                                    $msg = ($qry) ? "Break-Out and Time-Out Successfully! at ".date('M d, Y g:i:s A', strtotime($arr['datetime'])) : "Error Encountered on Break-Out and Time-Out! at  ".date('M d, Y g:i:s A', strtotime($arr['datetime']));
                                }else{
                                    $qry = false;
                                    $msg =  "Error Encountered on Invalid Data Submitted! at  ".date('M d, Y g:i:s A', strtotime($arr['datetime']));
                                }
                                break;

                    case 3:     if($type_id=="4"){
                                    $qry = AttendanceDao::insertAttendance($arr); //Time Out
                                    $msg = ($qry) ?  "Time-Out Successfully! at ".date('M d, Y g:i:s A', strtotime($arr['datetime'])) : "Error Encountered on Time-Out! at  ".date('M d, Y g:i:s A', strtotime($arr['datetime']));
                                }elseif($type_id=="2"){
                                    $qry = AttendanceDao::insertAttendance($arr); //Another Break out
                                    $msg = ($qry) ?  "Break-Out Successfully! at ".date('M d, Y g:i:s A', strtotime($arr['datetime'])) : "Error Encountered on Time-Out! at  ".date('M d, Y g:i:s A', strtotime($arr['datetime']));
                                }
                              
                                break;

                    case 4:     $qry = ($type_id=="1") ? AttendanceDao::insertAttendance($arr) : false; //Only Time In
                                $msg = ($qry) ? "Time-In Successfully! at ".date('M d, Y g:i:s A', strtotime($arr['datetime'])) : "Error Encountered on Time-In! at  ".date('M d, Y g:i:s A', strtotime($arr['datetime']));
                                break;

                    default:    $qry = false; 
                }
            }
        }elseif(is_null($last_punchdt)&&is_null($last_punch)){
            $qry = AttendanceDao::insertAttendance($arr);
            $msg = ($qry) ? "Time-In Successfully! at ".date('M d, Y g:i:s A', strtotime($arr['datetime'])) : "Error Encountered on Time-In! at  ".date('M d, Y g:i:s A', strtotime($arr['datetime']));
        }else{
            $log = AttendanceDao::getTimeLogStatus($emp_id);
            return array('msg' => 'Datetime Range Invalid!', 'log' => $log, 'status' => false, 'last_input' => $last_punch );
        }
        $log = AttendanceDao::getTimeLogStatus($emp_id);
        // var_dump($log); die();
        return ($qry) ? array('msg' => $msg, 'status' => true, 'log' => $log,) : array('msg' => $msg, 'status' => false, 'log' => $log, 'last_input' => $last_punch);
    }

    function getLastPunch($emp_id){
        // DB::connection()->enableQueryLog();
        return DB::table('attendance')
                    //->select(DB::raw('IF(MAX(datetime), DATE_FORMAT(datetime, "%b %e, %Y %r"), NULL) as datetime'), 'type_id')
                    ->select(DB::raw('DATE_FORMAT(datetime, "%b %e, %Y %r") as datetime'), 'type_id')
                    ->where('emp_id', $emp_id)
                    ->orderBy('datetime', 'DESC')
                    ->take(1)
                    ->get()
                    ->toArray();
        // $queries = DB::getQueryLog(); var_dump($queries); die("End of Query");
    }

    function getDistinctDateAttendanceOfEmployee($from, $to, $emp_id){
        //DB::connection()->enableQueryLog();
        return DB::table('attendance as a')
                        ->select(DB::raw('DISTINCT(DATE(datetime)) as date'))
                        ->where('emp_id', $emp_id)
                        ->whereBetween('datetime', [$from, $to])
                        ->orderBy('datetime', 'ASC')
                        ->get()
                        ->toArray();
        //$queries = DB::getQueryLog(); var_dump($queries); die("End of Query");
    }

    function convertTime($s) { 
        $m = 0; $hr = 0; 
        
        if($s > 59){ 
            $m = (int)($s/60); 
            $s = $s-($m*60); // sec left over 
        } 

        if($m>59){ 
            $hr = (int)($m / 60); 
            $m = $m - ($hr*60); // min left over 
        } 
    
        return [$hr, $m, $s];
    } 

    function traverseRecords($data, $seq, $seqtime, $seqindx, $recindx){

        echo "<br>Sequential TI-TO<br>";
        //Get Indexes Per Transaction
        echo "<br><br>Calculating......<br>";
        // print_r($recindx);
        // echo "<br>";
        $sum = 0; $ttl_wkhrs = $ttl_brkhrs = $perDay = array();
        
        for($c=0;$c<count($recindx);$c++){
            //- strtotime($data[$recindx[$c][0]]->datetime);
            $brkcount = $tcount = 0;
            echo "<br>".$brkcount." - ".$tcount."<br>";
            $sum = 0;
            if(is_numeric($recindx[$c][0])&&is_numeric($recindx[$c][1])){ //Complete Record
                echo "<br>------------------------------------------<br><br>SEGMENT
                [".($c+1)."] (".$recindx[$c][0]." - ".$recindx[$c][1].")";
               
                for($x=$recindx[$c][0];$x<=$recindx[$c][1];$x++){
                    //$ttlhrs = $data[$recindx[$x][1]]->datetime - $data[$recindx[$x][0]]->datetime;
                    echo "<br>".date('M d, Y g:i A', strtotime($data[$x]->datetime))." [".$x."] - ".$data[$x]->code." - ";
                    $f = strtotime($data[$x]->datetime);
                    
                    //($data[$x]->code=="BI"||$data[$x]->code=="BO") ? $brkcount++ : null;
                    if(array_key_exists($x+1, $data)){ 
                        $t = strtotime($data[$x+1]->datetime);
                        if($data[$x]->code=="BO"){ 
                            if($data[$x+1]->code!="TI"&&$data[$x+1]->code!="TO"){
                                $diff = $t - $f;
                                echo "DIFF: ".$diff."<br>";
                                $diffx = AttendanceDao::convertTime($diff);
                                echo "TOTAL BREAK DIFFERENCE: ".$diffx[0]."h, ".$diffx[1]."m, ".$diffx[2]."s"; 
                                $sum+=$diff;
                            }
                        }
                    }
                 
                    if($data[$x]->code=="TO"||$data[$x]->code=="TI")
                        $tcount++;
                    elseif($data[$x]->code=="BO"||$data[$x]->code=="BI")
                        $brkcount++;
                }
               
                //5400 -> 1 Hour and 30 Minutes Break, 28800 -> 8 hours
                echo "<br><br><b>";
                echo ($tcount%2==0&&$brkcount%2==0) ? "<span style='color:green';> [Complete Record]</span>" : "<span style='color:red;'>[Incomplete Record]</span>";
                //echo ($brkcount%2==0) ? "<span style='color:green';> [Complete Record]</span>" : "<span style='color:red;'>[Incomplete Record]</span>";
                
                echo "</b>";
                echo "<br><b>BREAK COUNT:</b> ".$brkcount;
                echo "<br><b>TI/TO COUNT:</b> ".$tcount;

                $ttl_brkhrs[$c] = $sum;
                $diffx = AttendanceDao::convertTime($ttl_brkhrs[$c]);


                echo 
                ($brkcount%2==0) ? "</b><br><b>TOTAL BREAK ACCUMULATED:</b> ".$diffx[0]."h, ".$diffx[1]."m, ".$diffx[2]."s<b>" :
                    "</b><br><b>TOTAL BREAK ACCUMULATED:</b> ".$diffx[0]."h, ".$diffx[1]."m, ".$diffx[2]."s<b>";

                echo ($ttl_brkhrs[$c]<=5400&&$brkcount%2==0) ? "<span style='color:green';> [Okay!]</span>" : "<span style='color:red;'> [Overbreak!]</span>";
                echo "</b>";
                // echo $ttl_brkhrs[$c]."YTTTTTTTTTTTTTTTTT";
                $temp = ($ttl_brkhrs[$c]<=1800&&$ttl_brkhrs[$c]>=0) ? $ttl_brkhrs[$c] : ($ttl_brkhrs[$c] - 1800); //Minus 1 hour
                // echo $temp."ZZZZZZZZZZZZZZZZZZZ";
                $ttl_wkhrs[$c] = (strtotime($data[$recindx[$c][1]]->datetime) - strtotime($data[$recindx[$c][0]]->datetime)) - $temp;
                // echo $ttl_wkhrs[$c]."XXXXXXXXXXXXXXXXXXX";
                $diffx = AttendanceDao::convertTime($ttl_wkhrs[$c]);
                
                echo ($tcount%2==0) ?
                "</b><br><b>TOTAL WORKING HOURS ACCUMULATED: </b> ".$diffx[0]."h, ".$diffx[1]."m, ".$diffx[2]."s<b>" :
                    "<br><b>TOTAL WORKING HOURS ACCUMULATED:<span style='color:red;'>[Incomplete Record]</span></b>";

                //&&$brkcount%2==0
                echo "<b>";
                if($ttl_wkhrs[$c]==28800&&$tcount%2==0)
                    echo "<span style='color:blue';> [Okay!]</span>"; 
                elseif($ttl_wkhrs[$c]<28800&&$tcount%2==0)
                    echo "<span style='color:red;'> [Undertime!]</span>";
                elseif($ttl_wkhrs[$c]>28800&&$tcount%2==0)
                    echo "<span style='color:green;'> [Overtime!]</span>";
                
                echo "</b>";
            }
            else{ //Not Complete
                echo "<br>------------------------------------------<br><br>SEGMENT
                [".($c+1)."] (".$recindx[$c][0]." - ".$recindx[$c][1].")";
                //for($x=$recindx[$c][0];$x<=;$x++){
            }
        }
        die('<br>--------------------------------');        
    }

    function getEmployeeAbsences($from, $to, $emp_id){
        $min_time_in = date('Y-m-d', strtotime(AttendanceDao::getMinTimeIn($from, $to, $emp_id)[0]->datetime)); //Getting Proper Range
        $max_time_in = date('Y-m-d', strtotime(AttendanceDao::getMaxTimeOut($from, $to, $emp_id)));
        DB::connection()->enableQueryLog();
        //var_dump($min_time_in); die();
        //DB::select( DB::raw("SELECT * FROM some_table WHERE some_col = '$someVariable'"));
        $data = DB::select(DB::raw("SELECT IFNULL(a.datetime, 'wala') as meron, c.datefield, a.datetime, c.type, a.type_id
        FROM calendar c LEFT JOIN attendance a ON DATE(a.datetime) = c.datefield AND a.type_id = 1 AND a.emp_id = '$emp_id' WHERE c.datefield BETWEEN '$min_time_in' AND '$max_time_in' AND c.type IN (0, 1, 2, 3, 4) ORDER BY c.datefield DESC"));
        // var_dump($data[0]->meron); die();
        $queries = DB::getQueryLog(); var_dump($queries); die("End of Query");
        
        $newdata = array();
        for($c=0;$c<count($data);$c++){
            $newdata[$c] = array('datetime' => date('M d, Y g:i:s A', strtotime($data[$c]->datetime)), 
                                 'type_id' => $data[$c]->type_id,
                                 'id' => $c+1
                            );
        }

        return $newdata;
    }

    function getEmployeeAttendance($from, $to, $emp_id){
        //DB::connection()->enableQueryLog();
        $min_time_in = AttendanceDao::getMinTimeIn($from, $to, $emp_id); //Getting Proper Range
        $max_time_in = AttendanceDao::getMaxTimeOut($from, $to, $emp_id);
       
        $data =  DB::table('attendance as a')
                    ->select('a.datetime', 'a.type_id', 't.code', 't.description', 'a.for_change', 'a.id')
                    ->leftJoin('attendancetype as t', 'a.type_id', '=', 't.id')
                    ->where('emp_id', $emp_id)
                    ->whereBetween('datetime', [$min_time_in[0]->datetime, $max_time_in])
                    ->orderBy('datetime', 'DESC')
                    ->get();
        
        $newdata = array();
        $emp_rec = EmployeeDao::getEmployee($emp_id);
        $newdata['userinfo'] = array('name' => $emp_rec['last_name'].", ".$emp_rec['first_name'], 'position' => $emp_rec['pos_title']);
        $newdata['msg'] = [];
        $c = 1;
        foreach($data as $k){
            $newdata['msg'][] = array('datetime' => date('M d, Y g:i:s A', strtotime($k->datetime)), 
                                        'type_id' => $k->type_id,
                                        'code' => $k->code,
                                        'description' => $k->description,
                                        'for_change' => ($k->for_change) ? "YES" : "NO",
                                        'seq' => $c++,
                                        'id' => $k->id
                                 );
        }

        return $newdata;
    }

    function deleteEmployeeAttendance($att_id){
        return AttendanceModel::where('id', $att_id)->delete();
    }

    function getEmployeeAttendanceSummary($from, $to, $emp_id){

        /* Check if has lapse
        Start with TI and in TO of the last day even with lapse*/

        $min_time_in = AttendanceDao::getMinTimeIn($from, $to, $emp_id);
        $max_time_in = AttendanceDao::getMaxTimeOut($from, $to, $emp_id);

        echo "MIN-TIME-IN: ".date('M d, Y g:i A', strtotime($min_time_in[0]->datetime))." ==== LAST-POSSIBLE: ".date('M d, Y g:i A', strtotime($max_time_in))."<br>";

        // DB::connection()->enableQueryLog();
        $data = DB::table('employees AS e')
                    ->select('e.first_name', 'e.last_name', 'e.is_active', 'e.pos_title', 'a.datetime', 'a.emp_id', 't.code', 't.description')
                    ->leftJoin('attendance as a' , 'e.id', '=', 'a.emp_id')
                    ->leftJoin('attendancetype as t', 'a.type_id', '=', 't.id')
                    ->leftJoin('calendar as c', 'c.datefield', '=', 'a.datetime')
                    ->where('e.id', '=', $emp_id)
                    //->whereRaw(' calendar c ON c.date = DATE(a.datetime)') //View for Weekdays
                    //->join('calendar c ON c.date = DATE(a.datetime)') //View for Weekdays
                    //->whereBetween('DATE(datetime) as datetime', [$from, $to])
                    ->where('datetime', '>=', $min_time_in[0]->datetime) //$from
                    ->where('datetime', '<=', $max_time_in) //$to
                    //->whereRaw('c.type IN (2,3,4,5,6)')
                    ->orderBy('datetime', 'ASC')
                    ->get()
                    ->toArray();

        //$queries = DB::getQueryLog(); var_dump($queries); die("End of Query");
        //var_dump($data); die();
        $seq = $seqtime = $seqindx = array();

        //$brkincount = $brkoutcount = 0;
        $count = count($data) - 1;
        for($c=0;$c<count($data);$c++){
            //check ti to to
            if($data[$c]->code=="TI"||$data[$c]->code=="TO"){
                $seq[] = $data[$c]->code;
                $seqindx[] = $c;
                $seqtime[] = $data[$c]->datetime;
            }elseif($count==$c){
                $seqindx[] = $c;
            }

            // ($data[$c]->code=="BI") ? $brkincount++ : null;
    
            // ($data[$c]->code=="BO") ? $brkoutcount++ : null;
        }

        $c = 0;
        // for($c=0;$c<count($seqindx);$c++){
        //     echo $seqindx[$c]."<br>";
        // } die();

        while($c<count($seqindx)){ //pairing index
            if(array_key_exists($c+1, $seqindx)){
                $recindx[] = array($seqindx[$c], $seqindx[$c+1]);
                $c+=2;
                continue;
            }else
                $recindx[] = array($seqindx[$c], '*');
            $c++;
        }

        //var_dump($seqindx); die();
        //var_dump($recindx); //die();
        for($c=0;$c<count($recindx);$c++){
            print_r($recindx[$c])."<br>";
        }

        //*** DEFICIENCY -> DECLARE INCOMPLETE 

        if(!is_null($data)){
            //Traversing Through Records 
            AttendanceDao::traverseRecords($data, $seq, $seqtime, $seqindx, $recindx);
        }

        //Debugging
        echo "<br><br>-----FOR DEBUGGING---------<br>";
        for($c=0;$c<count($data[$c]->datetime);$c++){
            echo $data[$c]->datetime." - ".$preset[$c]."<br>";
        }
    
        die('');
    }

    /*function getEmployeeAttendance($from, $to, $emp_id){ //With Full Type Support
        //Get Distinct?
        //Get the minimum punch >= dtfrom
      
        $distnctdate = AttendanceDao::getDistinctDateAttendanceOfEmployee($from, $to, $emp_id);
        //var_dump($distnct); die();
        for($a=0;$a<count($distnctdate);$a++){
            //DB::connection()->enableQueryLog();
            $data[$a] = DB::table('employees AS e')
                        ->select('e.first_name', 'e.last_name', 'e.is_active', 'e.pos_title', 'a.datetime', 'a.emp_id', 't.code', 't.description')
                        ->leftJoin('attendance AS a' , 'e.id', '=', 'a.emp_id')
                        ->leftJoin('attendancetype as t', 'a.type_id', '=', 't.id')
                        ->where('e.id', '=', $emp_id)
                        ->whereDate('a.datetime', $distnctdate[$a]->date)
                        ->orderBy('datetime', 'ASC')
                        ->get()
                        ->toArray();
            //$queries = DB::getQueryLog(); var_dump($queries); die("End of Query");         
        }
      
        echo "Employee Name: <b>".$data[0][0]->last_name.", ".$data[0][0]->first_name."</b><br>Position: <b>".$data[0][0]->pos_title."</b><br><br><strong>Attendance Record</strong><br>----------------------------<br><br>****************************<br>";

        for($a=0;$a<count($data);$a++){ //distinct date
            //for($b=0;$b<count($data[$a]);$b++){ //traversing through presets [TI - TO]
                //check if punch completed
                echo "<i>".date('M d, Y g:i A', strtotime($data[$a][0]->datetime))."</i><br>";
                $stat[$a] = AttendanceDao::checkIfPunchComplete($data[$a], $emp_id);
                //var_dump($stat[$a]); die();
                //echo $data[$a][$b]->datetime." - ".$data[$a][$b]->description." - ".$data[$a][$b]->code."<br>";
            //}
            echo "<br>****************************<br>";
        }
        
        //var_dump($stat); 
        die('----------End of Result-------------');
    }

    function checkIfPunchComplete($data, $emp_id){ //Checking One Day if completed (WITH FULL TYPE SUPPORT)
        //Declared to be constant
        $presets = array('TI', 'BO1', 'BI1', 'LO', 'LI', 'BO2', 'BI2', 'TO');
        $presets1 = array('Time in', 'Break Out 1', 'Break In 1', 'Lunch Out', 'Lunch In', 'Break Out 2', 'Break In 2', 'Time Out');
        $datetime = array('', '', '', '', '', '', '', '');
        
        for($a=0;$a<count($presets);$a++){
            for($c=0;$c<count($data);$c++){
                if($presets[$a]==$data[$c]->code)
                {
                    $datetime[$a] = $data[$c]->datetime;
                    if($data[$c]->code=="TI")
                        $last_time_in = $data[$c]->datetime;

                    if($data[$c]->code=="TO")
                        $last_time_out = $data[$c]->datetime;
                }
            }
        }

        //if TI or TO is 0 -> get the next TI else calculate
        if(in_array('', $datetime)){ //*** DEFICIENCY *REVISION ONCE HAS DEFICIENCY DECLARE INCOMPLETE 
            //if($datetime[7]==0){ //No Time Out - REMARKKKKKKKKKKKKKKKKKKKKKKSSSS
                //$next_time_in = AttendanceDao::getNextTimeIn($last_time_out, $emp_id);
            //if($datetime[7]==''&&$datetime[0]!='') //No TimeOut but has Time In
             //   $next_time_in = AttendanceDao::getNextTimeIn($last_time_out, $emp_id);
            echo "<u>Incomplete Record</u><br><br>";
            for($c=0;$c<count($datetime);$c++){
                if($datetime[$c]!="")
                    echo date('M d, Y g:i A', strtotime($datetime[$c]))." - ".$presets1[$c]."<br>"; //." - ".$presets[$c]."<br>"
                else
                    echo "No Record&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ".$presets1[$c]."<br>"; //." - ".$presets[$c]."<br>"
            }
            echo "<br>Total Hours: Incomplete Record";
            return 0;
            //return "Incomplete";
        }
        else //Complete then calculate total hours then check for violation 2 (15 Minutes Break) 1 (1 Hour Lunchbreak)
        {
            //echo "<br>Calculating......<br>";
            echo "<u>Complete Record</u><br><br>";
            $sum = new DateTime('00:00');
            $total = clone $sum;
            
            for($c=0;$c<count($datetime);$c++){
                $f = new DateTime($datetime[$c]);
                echo date('M d, Y g:i A', strtotime($datetime[$c]))." - ".$presets1[$c]."<br>"; //." - ".$presets[$c]."<br>"
                if($c!=3){ //Don't Include Lunch Breaks
                    if(array_key_exists($c+1,$datetime))
                    {
                        $t = new DateTime($datetime[$c+1]);
                        $diff = date_diff($f, $t);
                        //echo $diff->format('%y Years %m Months %d Days %h Hours %i Minutes %s Seconds')."<br>";
                        $diffx[$c] = $diff;
                        $total->add($diffx[$c]);
                    }
                }
            }
            //echo "<br><br>----------TIME DIFF---------------------<br>";
            echo "<br>Total Hours: ".$total->diff($sum)->format('%h Hr %i Min %s Sec'); //%y Years %m Months %d Days 
            return $total->diff($sum)->format('%h Hours %i Minutes %s Seconds');
            //echo "<br>".date('Y-m-j', strtotime('3 weekdays')); die(); I can use this function
            //echo "<br>Total Hours: ".$total->diff($sum)->format('%y Years %m Months %d Days %h Hours %i Minutes %s Seconds');
            
              Total Amount for break - 30 minutes 
              Total Amount for Lunchbreak - 1 Hour
             
        } 
     
        //Debugging
        /*echo "<br><br>-----FOR DEBUGGING---------<br>";
        for($c=0;$c<count($datetime);$c++){
            echo $datetime[$c]." - ".$presets[$c]."<br>";
        }
    
        die('<br>zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz');
    }*/

    function getNextTimeIn($last_time_out, $emp_id){
        var_dump(DB::table('attendance as a')
                ->select('a.datetime')
                ->leftJoin('attendancetype AS t' , 'a.type_id', '=', 't.id')
                ->where('a.emp_id', '=', $emp_id)
                ->where('a.datetime', '>', $last_time_out)
                ->where('t.code', 'TI')
                ->get()
                ->toArray()); die();
    }

    function getAllEmployeeAttendance($from, $to, $accstat){
        // DB::connection()->enableQueryLog();
  
        return DB::select(DB::raw("SELECT IF(MAX(a.datetime), DATE_FORMAT(a.datetime, '%b %e, %Y %r'), NULL) as max, e.first_name, e.last_name, e.is_active, e.pos_title, a.emp_id, t.code, e.id, t.description FROM employees e 
                        LEFT JOIN attendance a ON e.id = a.emp_id AND a.datetime BETWEEN '$from' AND '$to' 
                        LEFT JOIN attendancetype t ON a.type_id = t.id
                        WHERE $accstat GROUP BY e.id ORDER BY e.last_name"));

        //  $queries = DB::getQueryLog(); var_dump($queries); die("End of Query");
    }

    function getMinimumDTOfEmployeeAttendance(){
        $qry = DB::table('attendance')
                   ->select('datetime')
                   ->min('datetime');

        return (is_null($qry)) ? date('Y-m-d 00:00:00') : $qry;
    }

    function getMaximumDTOfEmployeeAttendance(){
        $qry = DB::table('attendance')
                   ->max('datetime');

        return (is_null($qry)) ? date('Y-m-d 00:00:00') : $qry;
    }

    function getTimeLogStatus($user_id){
        $last_punch = AttendanceDao::getLastPunch($user_id);
        //var_dump($last_punch); die();
        $lastpunch = (empty($last_punch)) ? null : $last_punch[0]->type_id;
        $lastpunch_dt = (empty($last_punch)) ? null : $last_punch[0]->datetime;
        $next_punch = AttendanceDao::getNextPunch($lastpunch);
        $nextpunch_dt = AttendanceDao::getPunchDescription($next_punch);
        //var_dump($lastpunch_dt); die();
        return array('status' => true, 
                     'log' => array(
                    'last_punch' => $lastpunch,
                    'lastpunch_dt' => $lastpunch_dt,
                    'lastpunch_desc' => AttendanceDao::getPunchDescription($lastpunch),
                    'next_punch' => $next_punch,
                    'nextpunch_desc' => $nextpunch_dt
                     ));
        //$last_punch = ($last_punch)
        //AttendanceDao::getNextPunch($type_id)
        //return 1 if no record
    }
}