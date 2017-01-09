<?php
include "/var/www/private/top_mysqli.php";
include "/var/www/private/recaptcha.php";

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
    </div>
    <br><br>
</body>
</html>