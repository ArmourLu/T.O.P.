<?php
$Debug_Table = 0;
$Debug_Time = 0;
$Debug_SQL = 0;

if($Debug_Time) $start = microtime(true);

include "/var/www/private/dht_mysqli.php";

if($Debug_Time) $returnjson['ConnectTime'] = number_format((microtime(true) - $start), 2) . "s";
if($Debug_Time) $start = microtime(true);

$max_query_count = 60 * 60 * 24;

//Get count
$query_count = $_GET['c'];
if($query_count=='' || !is_numeric($query_count)) $query_count=1;

//Get interval
$query_interval = $_GET['i'];
if($query_interval=='' || !is_numeric($query_interval)) $query_interval=1;

//Check query count
if($query_count/$query_interval > $max_query_count) $query_count = $max_query_count * $query_interval;

//Get sensor#
$query_sensor = $_GET['s'];
if(strtolower($query_sensor)=='a')
{
	$query_sensor = 'a';
}
else
{
	if($query_sensor=='' || !is_numeric($query_sensor)) $query_sensor=0;
}

//Get from date
$query_from_datetime = $_GET['f'];

//Get sensor number
$sensor_num = 6;

if (date('Y-m-d H:i:s', strtotime($query_from_datetime))!= $query_from_datetime)
{
    $query_from_datetime="";
}
else
{
    $query_end_datetime = date("Y-m-d H:i:s",strtotime($query_from_datetime) + $query_count - 1);
}

$query_fields = "ID, Reading, UNIX_TIMESTAMP(DateTime)";

// Query by datetime
if($query_end_datetime != ""){
    if($query_interval==1)
    {
       $sqlstr = "select $query_fields from dht where datetime <= '$query_end_datetime' and  datetime >= '$query_from_datetime' order by id desc";
    }
    else
    {
        $sqlstr = "select $query_fields from dht where datetime <= '$query_end_datetime' and  datetime >= '$query_from_datetime' and UNIX_TIMESTAMP(DateTime)%$query_interval=0 order by id desc";
    }
}
else{
    if($query_interval==1)
    {
	   $sqlstr = "select $query_fields from dht force index (id) order by id desc limit 0,$query_count";
    }
    else
    {
        $query_count = $query_count / $query_interval;
        $sqlstr = "select $query_fields from dht force index (id) where UNIX_TIMESTAMP(DateTime)%$query_interval=0 order by id desc limit 0,$query_count";
    }
}
if($Debug_Time) $returnjson['PrepareTime'] = number_format((microtime(true) - $start), 2) . "s";
if($Debug_Time) $start = microtime(true);
//Execute SQL
$result = mysqli_query($conni, $sqlstr);
if($Debug_Time) $returnjson['SQLTime'] = number_format((microtime(true) - $start), 2) . "s";
$DataArray = array();

// Loop for fetching date from mysql
$LastID = 0;
$ArrayCount = 0;

$PreDate = 0;

if($Debug_Time) $start = microtime(true);

while ($row = mysqli_fetch_row($result)) {
	if($ArrayCount>=$query_count) break;
    if($PreDate==$row[2]) continue;

    if($ArrayCount == 0)
    {
        $readings_count = $sensor_num;
        $LastID = (int)$row[0];
    }


    $readings = explode(',',$row[1]);
    if(count($readings) != $sensor_num) continue;

    $tmpArray = array();
    $tmpArray[0] = (int)$row[0];
    $tmpArray[1] = date("Y-m-d H:i:s",$row[2]);
    
    if($query_sensor === 'a')
    {
        for($i=0 ; $i < $readings_count ; $i++){
            $tmpArray[$i+2] = (float)$readings[$i];
        }
    }
    else{
        if($readings_count < $query_sensor+1)
        {
            $tmpArray[2] = 0;
        }
        else
        {
            $tmpArray[2] = (float)$readings[$query_sensor];
        }
    }

    $DataArray[$ArrayCount] = $tmpArray;
    $ArrayCount++;

    $PreDate = $row[2];
}

if($Debug_Time) $returnjson['ParseTime'] = number_format((microtime(true) - $start), 2) . "s";
$returnjson['Status'] = "OK";
if($Debug_SQL) $returnjson['SQL'] = $sqlstr;
$returnjson['Count'] = count($DataArray);
$returnjson['Interval'] = (int)$query_interval;
$returnjson['SensorCount'] = count($DataArray[0]);
$returnjson['Sensor'] = $query_sensor;
$returnjson['Data'] = $DataArray;
echo json_encode($returnjson);
?>