<?php
include "/var/www/private/top_mysqli.php";
include "/var/www/private/email_hash.php";
include "/var/www/private/passwd_hash.php";
include "/var/www/private/gmail.php";
include "/var/www/private/recaptcha.php";
require "/var/www/composer/vendor/autoload.php";

$recaptcha = new \ReCaptcha\ReCaptcha($recaptcha_Secret_key);
$resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);

$whitelist = array("inventec.com");

//Get email
$email = strtolower($_POST['email']);

//Get passwod
$passwd = strtolower($_POST['password']);

//Get command
$cmd = strtolower($_GET['cmd']);
if($cmd == "") $cmd = "add";

//Get key
$key = strtolower($_GET['key']);

//Get id
$id = strtolower($_GET['id']);

$sqlstr = "SELECT Value FROM sysinfo where Name='ActivationLink'";
$result = mysqli_query($conni, $sqlstr);
$row = mysqli_fetch_row($result);
$ActivationLink = $row[0];

$sqlstr = "SELECT Value FROM sysinfo where Name='ServiceName'";
$result = mysqli_query($conni, $sqlstr);
$row = mysqli_fetch_row($result);
$ServiceName = $row[0];

if($cmd == "verify")
{
    if (!is_numeric($id)) {
      $status = "error";
      $comment = "Invalid ID.";
    }
    else
    {
        $sqlstr = "select Email, EmailActiveKey, Enabled from User where ID='$id'";
        $result = mysqli_query($conni, $sqlstr);
        $row = mysqli_fetch_array($result);
        $email = $row['Email'];
        $hash = $row['EmailActiveKey'];
        $Enabled = $row['Enabled'];
        if($hash == $key)
        {
            if($Enabled == true)
            {
                $status = "success";
                $comment = "Your account has been activated.";
            }else
            {
                $status = "success";
                $comment = "Your account is now activated.";
                $hash = email_hash($email);
                $curtime = date("Y-m-d H:i:s");
                $sqlstr = "update User set Enabled=TRUE, UpdateTime='$curtime', EmailActiveKey='$hash' where ID='$id'";
                $result = mysqli_query($conni, $sqlstr);
                //exec("python $PythonPath mail $email remove > /dev/null &");
            }
        }
        else
        {
            $status = "error";
            $comment = "Wrong verification code. We can't verify your account.";
        }
    }
}
/*
elseif($cmd == "remove")
{
    if (!is_numeric($id)) {
      $status = "error";
      $comment = "Invalid ID.";
    }
    else
    {
        $sqlstr = "select hash, Enabled from useralert where ID='$id'";
        $result = mysqli_query($conni, $sqlstr);
        $row = mysqli_fetch_array($result);
        $hash = $row['hash'];
        $Enabled = $row['Enabled'];
        if($hash == $key)
        {
            if($Enabled == true)
            {
                $status = "success";
                $comment = "Your alert had been removed.";
                $sqlstr = "delete from useralert where ID='$id'";
                $result = mysqli_query($conni, $sqlstr);
            }else
            {
                $status = "error";
                $comment = "Your alert is not activated.";
            }
        }
        else
        {
            $status = "error";
            $comment = "Wrong verification code. We can't remove your email address.";
        }
    }
}
*/
elseif($cmd == "add")
{
    if(!$resp->isSuccess())
    {
        $status = "error";
        $comment = "Invalid Command";
    }
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $status = "error";
        $comment = "Your email address is invalid.";
    }
    else if(!validateEmailDomain($email, $whitelist)){
        $status = "error";
        $comment = "Your email address is not allowed.";
    }
    else if(strlen($passwd)<6 || strlen($passwd)>20){
        $status = "error";
        $comment = "Password length should be 6-20 characters long.";
    }
    else
    {
        $sqlstr = "select ID, Email, Enabled, UpdateTime from User where LOWER(email)='$email'";
        $result = mysqli_query($conni, $sqlstr);
        if(mysqli_num_rows($result) == 0)
        {
            $EmailActiveKey = email_hash($email);
            $passwordhash = passwd_hash($passwd);
            $curtime = date("Y-m-d H:i:s");
            $sqlstr = "insert into User (Email, Type, Enabled, PasswordHash, EmailActiveKey, CreateTime, UpdateTime) VALUES ('$email', '', FALSE, '$passwordhash','$EmailActiveKey', '$curtime', '$curtime')";
            mysqli_query($conni, $sqlstr);
            $sqlstr = "select ID from User where Email='$email'";
            $result = mysqli_query($conni, $sqlstr);
            $row = mysqli_fetch_array($result);
            $id = $row['ID'];
            $email_result = SendEmail($email,
                      "$ServiceName - Create User Account",
                      "Please visit below link to activate your account:<br>$ActivationLink?cmd=verify&id=$id&key=$EmailActiveKey");
            if($email_result == ""){
                $status = "info";
                $comment = "A confirmation email has been sent to your email address. Please click on the Activation Link to activate your account.";
            }else{
                $status = "error";
                $comment = "Internal error. Please contact with administrator.";
                //$comment = $email_result;
                $sqlstr = "delete from User where ID=$id";
                mysqli_query($conni, $sqlstr);
            }
            //exec("python $PythonPath mail $email verify > /dev/null &");
        }
        else
        {
            $row = mysqli_fetch_array($result);
            if($row['Enabled'] == true)
            {
                $status = "error";
                $comment = "The email address has already been used.";
            }
            else
            {
                $UpdateTime = new DateTime($row['UpdateTime']);
                $UpdateTime->modify("+10 minutes");
                $curtime = new DateTime();
                
                if($curtime > $UpdateTime)
                {
                    $status = "info";
                    $comment = "A confirmation email has been resent to your email address. Please click on the Activation Link to activate your account.";
                    $curtime = date("Y-m-d H:i:s");
                    $id = $row['ID'];
                    $sqlstr = "update User set UpdateTime='$curtime' where ID=$id";
                    mysqli_query($conni, $sqlstr);
                    //exec("python $PythonPath mail $email verify > /dev/null &");
                }
                else
                {
                    $status = "warning";
                    $comment = "If you didn't receive a confirmation email, please submit your email address again after 10 minues.";
                }
            }
        }
    }
}
else
{
    $status = "error";
    $comment = "Invalid Command";
}
$returnjson['Status'] = $status;
$returnjson['Comment'] = $comment;

//echo json_encode($returnjson);

function validateEmailDomain($email, $domains) {
    foreach ($domains as $domain) {
        $pos = strpos($email, $domain, strlen($email) - strlen($domain));

        if ($pos === false)
            continue;

        if ($pos == 0 || $email[(int) $pos - 1] == "@" || $email[(int) $pos - 1] == ".")
            return true;
    }

    return false;
}

function SendEmail($emailaddr, $mailsubject, $mailbody){
    global $gmail_username;
    global $gmail_password;
    global $gmail_from;
    global $gmail_fromname;
    
    $mail= new PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = "ssl";
    //$mail->SMTPDebug = 2;
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 465;
    $mail->CharSet = "utf-8";

    $mail->Username = $gmail_username;
    $mail->Password = $gmail_password;

    $mail->From = $gmail_from;
    $mail->FromName = $gmail_fromname;

    $mail->Subject = $mailsubject;
    $mail->Body = $mailbody;
    $mail->IsHTML(true);
    $mail->AddAddress($emailaddr, $emailaddr);

    if(!$mail->Send()) {
        return $mail->ErrorInfo;
    } else {
        return "";
    }    
};
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
    <script src="top.js"></script>
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
        <div class="headertext text-center"><?=strtoupper($status)?></div>
        <div class="block-info">
                <div class="row text-center smallinfotext">
                    <br><?=$comment?><br><br>
                </div>
        </div>
    </div>
    <br><br>
</body>
</html>