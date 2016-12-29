function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;
    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};
$(document).ready(function ($) {
    $('[data-toggle="tooltip"]').tooltip();
    $("#newaccount").submit(function(event){
        prepare_submit();
        event.preventDefault();
        var alertresult = [];
        if($("#newemail").val() == "" || $("#newpassword").val() == "" || $("#newconfirmpassword").val() == ""){
            alertresult["Status"] = "error";
            alertresult["Comment"] = "Please enter Email and Password.";
            after_submit(alertresult);
        }
        else if($("#newpassword").val() != $("#newconfirmpassword").val()){
            alertresult["Status"] = "error";
            alertresult["Comment"] = "Your password and confirmation password do not match.";
            after_submit(alertresult);
        }
        else if($("#newpassword").val().length<6 || $("#newpassword").val().length>20){
            alertresult["Status"] = "error";
            alertresult["Comment"] = "Password length should be 6-20 characters long.";
            after_submit(alertresult);
        }
        else{
            $.getJSON("newaccount.php?cmd=add&email="+$("#newemail").val()+"&password="+$("#newpassword").val(),function(alertresult){
                after_submit(alertresult);
                $("#newaccount :input").val('');
            });
        }
    });
    
    cmd = getUrlParameter("cmd");
    key = getUrlParameter("key");
    id = getUrlParameter("id");
    if(cmd != undefined && key != undefined && id != undefined){
        prepare_submit();
        $.getJSON("newaccount.php?cmd=" + cmd + "&id=" + id + "&key=" + key,function(alertresult){
            after_submit(alertresult);
        });
    }
});
function prepare_submit(){
    $(":input").prop('disabled',true);
    HoldOn.open({
        theme:"sk-bounce",
        message: "<h1> Please wait </h1>",
        content:"",
        backgroundColor:"black",
        textColor:"white"
    });
};
function after_submit(alertresult){
    swal({title:alertresult.Status.toUpperCase(),
          text:alertresult.Comment,
          type:alertresult.Status.toLowerCase()
         },function(){
            $(":input").prop('disabled',false);
            HoldOn.close();
    });
    if(history.pushState){
        history.pushState('','',location.href.split('?')[0]);
    }
};