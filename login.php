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
    <script src='https://www.google.com/recaptcha/api.js'></script>
</head>
<body>
    <div class="container">
        <?php include "home_bar.php"?>
        <div class="headertext text-center">Login</div>
        <div class="block-info">
           <form class="form-horizontal" id="login" method="post" action="login2.php">
                <div class="row">
                    <div class="col-sm-3 col-lg-4"></div>
                    <div class="col-sm-6 col-lg-4">
                        <label for="loginemail" class="smallinfotext">Email address</label>
                        <input type="email" class="form-control input-lg" id="loginemail" name="email" placeholder="Email">
                    </div>
                </div>
                <br/>
                <div class="row">
                    <div class="col-sm-3 col-lg-4"></div>
                    <div class="col-sm-6 col-lg-4">
                        <label for="loginpassword" class="smallinfotext">Password</label>
                        <input type="password" class="form-control input-lg" id="loginpassword" name="password" placeholder="Password">
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