<?php
include "/var/www/private/top_mysqli.php";
include "/var/www/private/email_hash.php";
include "/var/www/private/passwd_hash.php";
include "/var/www/private/gmail.php";
include "/var/www/html/top/PHPMailer/PHPMailerAutoload.php";

$whitelist = array("inventec.com");

//Get email
$email = strtolower($_GET['email']);

//Get passwod
$passwd = strtolower($_GET['password']);

//Get command
$cmd = strtolower($_GET['cmd']);

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
            $comment = "Wrong verification code. We can't verify your email address.";
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
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
    
    $mail= new PHPMailer();     //建立新物件        
    $mail->IsSMTP();            //設定使用SMTP方式寄信        
    $mail->SMTPAuth = true;     //設定SMTP需要驗證        
    $mail->SMTPSecure = "ssl";  // Gmail的SMTP主機需要使用SSL連線   
    //$mail->SMTPDebug = 2;
    $mail->Host = "smtp.gmail.com"; //Gamil的SMTP主機        
    $mail->Port = 465;           //Gamil的SMTP主機的SMTP埠位為465埠。        
    $mail->CharSet = "utf-8";    //設定郵件編碼        

    $mail->Username = $gmail_username; //設定驗證帳號        
    $mail->Password = $gmail_password; //設定驗證密碼        

    $mail->From = $gmail_from; //設定寄件者信箱        
    $mail->FromName = $gmail_fromname;             //設定寄件者姓名        

    $mail->Subject = $mailsubject; //設定郵件標題        
    $mail->Body = $mailbody;      //設定郵件內容        
    $mail->IsHTML(true);                        //設定郵件內容為HTML        
    $mail->AddAddress($emailaddr, $emailaddr); //設定收件者郵件及名稱        

    if(!$mail->Send()) {
        return $mail->ErrorInfo;
        //return "Error";
    } else {
        return "";
    }    
};
?>