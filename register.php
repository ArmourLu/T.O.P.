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
    <script src="/top/sweetalert/sweetalert.min.js"></script>
    <link rel="stylesheet" type="text/css" href="/top/sweetalert/sweetalert.css">
    <link rel="stylesheet" href="/top/HoldOn.js/css/HoldOn.css">
    <script src="/top/HoldOn.js/js/HoldOn.js"></script>
    <script src="register.js"></script>
    <script src='https://www.google.com/recaptcha/api.js'></script>
</head>
<body>
    <div class="container">
        <?php include "home_bar.php"?>
        <div class="headertext text-center">Register New Account</div>
        <div class="block-info">
           <form class="form-horizontal" id="newaccount" action="register2.php" method="POST">
                <div class="row">
                    <div class="col-sm-3 col-lg-4"></div>
                    <div class="col-sm-6 col-lg-4">
                        <label for="newemail" class="smallinfotext">Email address</label>
                        <input type="email" class="form-control input-lg" id="newemail" name="email" placeholder="Email">
                    </div>
                </div>
                <br/>
                <div class="row">
                    <div class="col-sm-3 col-lg-4"></div>
                    <div class="col-sm-6 col-lg-4">
                        <label for="newpassword" class="smallinfotext">Password</label>
                        <input type="password" class="form-control input-lg" id="newpassword" name="password" placeholder="Password">
                    </div>
                </div>
                <br/>
                <div class="row">
                    <div class="col-sm-3 col-lg-4"></div>
                    <div class="col-sm-6 col-lg-4">
                        <label for="newconfirmpassword" class="smallinfotext">Confirm Password</label>
                        <input type="password" class="form-control input-lg" id="newconfirmpassword" placeholder="Confirm Password">
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
                        <button id="newaccountsubmit" class="btn btn-primary btn-lg" type="button">Submit</button>
                        <button id="newaccountclear" class="btn btn-default btn-lg" type="button">Clear</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <br><br>
</body>
</html>