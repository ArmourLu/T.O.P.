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
    <title><?=$ServiceName?></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="author" content="Armour Lu, Inventec">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="/top/bootstrap-switch/css/bootstrap3/bootstrap-switch.css">
    <link href='https://fonts.googleapis.com/css?family=Ubuntu' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css?family=Roboto:500' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/start/jquery-ui.css">
    <link rel="stylesheet" href="/top/HoldOn.js/css/HoldOn.css">
    <link href="/top/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
    <script src="/top/js/jquery.cookie.js"></script>
    <script src="/top/bootstrap-switch/js/bootstrap-switch.min.js"></script>
    <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
    <script>
        /*** Handle jQuery plugin naming conflict between jQuery UI and Bootstrap ***/
        $.widget.bridge('uibutton', $.ui.button);
        $.widget.bridge('uitooltip', $.ui.tooltip);
    </script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="/top/js/jquery.nicescroll.js"></script>
    <script src="/top/sweetalert/sweetalert.min.js"></script>
    <link rel="stylesheet" type="text/css" href="/top/sweetalert/sweetalert.css">
    <script src="/top/HoldOn.js/js/HoldOn.js"></script>
    <link type="text/css" href="/top/amcharts/plugins/export/export.css" rel="stylesheet">
    <script src="/top/amcharts/amcharts.js"></script>
    <script src="/top/amcharts/serial.js"></script>
    <script src="/top/amcharts/themes/light.js"></script>
    <script src="/top/amcharts/plugins/export/export.js"></script>
    <script src="/top/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <script src='https://www.google.com/recaptcha/api.js'></script>
</head>
<body>
    <div class="container">
        <?php include "home_bar.php"?>
        <div class="headertext text-center">Login</div>
        <div class="block-info">
           <form class="form-horizontal" id="login" method="get" action="#">
                <div class="row">
                    <div class="col-sm-3 col-lg-4"></div>
                    <div class="col-sm-6 col-lg-4">
                        <label for="loginemail" class="smallinfotext">Email address</label>
                        <input type="email" class="form-control input-lg" id="loginemail" placeholder="Email">
                    </div>
                </div>
                <br/>
                <div class="row">
                    <div class="col-sm-3 col-lg-4"></div>
                    <div class="col-sm-6 col-lg-4">
                        <label for="loginpassword" class="smallinfotext">Password</label>
                        <input type="password" class="form-control input-lg" id="loginpassword" placeholder="Password">
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-3 col-lg-4"></div>
                    <div class="col-sm-6 col-lg-4">
                        <br>
                        <div class="g-recaptcha" data-sitekey="<?=$recaptcha_Site_key?>"></div>
                    </div>
                </div>
                <br/>
                <div class="row">
                    <div class="col-sm-3 col-lg-4"></div>
                    <div class="col-sm-6 col-lg-4 text-center">
                        <button id="loginsubmit" class="btn btn-primary btn-lg" type="button">Login</button>
                        <button id="loginclear" class="btn btn-default btn-lg" type="button">Clear</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <br><br>
</body>
</html>