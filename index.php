<?php
include "/var/www/private/top_mysqli.php";

$sqlstr = "SELECT Value FROM sysinfo where Name='ServiceName'";
$result = mysqli_query($conni, $sqlstr);
$row = mysqli_fetch_row($result);
$ServiceName = $row[0];

?>
<!doctype html>
<head>
<?php include "common_head.php"?>
</head>
<body>
    <div class="container">
        <?php include "home_bar.php"?>
        <div class="headertext text-center">Welcome to <?=$ServiceName?></div>
        <div class="block-info">
                <div class="row text-center smallinfotext">
                    <br>
                    <img src="/top/image/top/sersor_humity.png">&nbsp;&nbsp;&nbsp;&nbsp;
                    <img src="/top/image/top/sersor_light.png">&nbsp;&nbsp;&nbsp;&nbsp;
                    <img src="/top/image/top/sersor_psi.png">&nbsp;&nbsp;&nbsp;&nbsp;
                    <img src="/top/image/top/sersor_soil.png">&nbsp;&nbsp;&nbsp;&nbsp;
                    <img src="/top/image/top/sersor_temp.png">
                    <br>
                    <br>
                    <img src="/top/image/top/TOP_Teach.png">
                    <br><br>
                </div>
        </div>
    </div>
    <br><br>
</body>
</html>