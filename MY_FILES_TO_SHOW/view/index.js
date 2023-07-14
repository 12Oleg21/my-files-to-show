$(document).ready(function() {
    setInterval(function(){
         $.pjax.defaults.timeout = 5000;
         reloadStatuses(); }, 30000);
});

function changeRecord(type, record){
    var url = "changerecord?type=" + type;
    $.get(url,{record : record});
}

function changeEmergencyMode(type,status=0,message){
    var status = {status:status};
    var url = "index?type=" + type;
    krajeeDialog.confirm(message, function (result) {
        if (result) {
            $.pjax({
                        type: "GET",
                        url: url,
                        push: false,
                        data: status,
                        scrollTo: false,
                        container: "#emergencymode",
                    });
        }
    });
}

function reloadStatuses(){
   var url = "index";
   $.pjax({
                    type: "GET",
                    url: url,
                    push: false,
                    scrollTo: false,
                    container: "#emergencymode",
                });
}
