<?php
include "/var/www/private/top_mysqli.php";
include "/var/www/private/email_hash.php";
include "/var/www/private/passwd_hash.php";
include "/var/www/private/gmail.php";
include "/var/www/html/top/PHPMailer/PHPMailerAutoload.php";
include "/var/www/private/recaptcha.php";

$whitelist = array("inventec.com");

//Get email
$email = strtolower($_POST['email']);

//Get passwod
$passwd = strtolower($_POST['password']);

//Get g-recaptcha-response
$recaptcha_response = $_POST['g-recaptcha-response'];

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
    if(!check_reCAPTCHA())
    {
        $status = "error";
        $comment = "Are you a bot? :/";
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

echo json_encode($returnjson);

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

function check_reCAPTCHA()
{
    global $recaptcha_response;
    global $recaptcha_Secret_key;
    
        
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $myvars = 'secret=' . $recaptcha_Secret_key . '&response=' . $recaptcha_response;

    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_POST, 1);
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt( $ch, CURLOPT_HEADER, 0);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

    $response = json_decode(curl_exec( $ch ),true);
    
    return $response["success"];
};
?>