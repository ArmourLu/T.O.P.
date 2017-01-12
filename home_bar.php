<?php
$menu = array(
    'home'  => array('text'=>'<span class="glyphicon glyphicon-home" aria-hidden="true">',  'url'=>'index.php'),
    'login'  => array('text'=>'Login',  'url'=>'login.php'),
    'register' => array('text'=>'Register', 'url'=>'register.php'),
);

?>
<!-- Home Bar -->
<div class="block-header infotext text-center">
<img src="/top/image/top/logo_small.png"><br>
<?=$ServiceName?><br>
<?php
foreach($menu as $item){
    if(basename($_SERVER['PHP_SELF']) == $item[url]){
        echo '<a class="btn btn-success" href="'.$item[url].'" role="button">'.$item[text].'</span></a> ';
    }else{
        echo '<a class="btn btn-default" href="'.$item[url].'" role="button">'.$item[text].'</span></a> ';
    }
    
}
?>
</div>
<!-- Home Bar -->
