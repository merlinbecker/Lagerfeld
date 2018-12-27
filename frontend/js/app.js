$(document).ready(function(evt){
	console.log(getCacheStatus());
	$("#nav_new_item").hide();
	$("#nav_user_settings").hide();
	$("#nav_search").hide();
	$.getJSON(BASE_URL+"User/Status",setUserStatus).fail(apiCallFailed);
	bindButtons();
});
function bindButtons(){
	$("#nav_user_settings").find("a").click(function(evt){
		$("#modal_user_settings").modal({});
		evt.stopPropagation();
		return false;
	});
	$("#btn_logout").click(function(evt){
		$("#modal_user_settings").modal("hide");
		$.getJSON(BASE_URL+"User/LogOut").done(setUserStatus).fail(apiCallFailed);
		evt.stopPropagation();
		return false;
	});
	$("#nav_new_item").find("a").click(function(evt){
		$("#modal_new_item").modal();
		evt.stopPropagation();
		return false;
	});
	$("#new_item_form").submit(function(evt){
		alert("submit!");
		evt.stopPropagation();
		return false;
	});
}

function apiCallFailed(){
	console.log("API Call failed!");
}
function setUserStatus(data){
	if(data.payload.status=="not logged in"){
		$("#nav_new_item").hide();
		$("#nav_user_settings").hide();
		$("#nav_search").hide();
		$("#nav_user_login a").attr("href",data.payload.authURL);
		$("#nav_user_login").show();
	}
	else if(data.payload.status=="logged in"){
		$("#nav_user_settings").find(".nav-desc").html(data.payload.userdata.email);
		$("#nav_new_item").show();
		$("#nav_user_settings").show();
		$("#nav_search").show();
		$("#nav_user_login").hide();
		$("#modal_user_settings").find(".modal-body").html("logged in as : "+data.payload.userdata.email);
		console.log("logged in");
	}
	console.log(data.payload);
}
function getCacheStatus(){
var appCache = window.applicationCache;

	switch (appCache.status) {
  		case appCache.UNCACHED: // UNCACHED == 0
    			return 'UNCACHED';
    		break;
  		case appCache.IDLE: // IDLE == 1
    			return 'IDLE';
    		break;
  		case appCache.CHECKING: // CHECKING == 2
    			return 'CHECKING';
    		break;
  		case appCache.DOWNLOADING: // DOWNLOADING == 3
    			return 'DOWNLOADING';
    		break;
  		case appCache.UPDATEREADY:  // UPDATEREADY == 4
    			return 'UPDATEREADY';
    		break;
  		case appCache.OBSOLETE: // OBSOLETE == 5
    			return 'OBSOLETE';
    		break;
  		default:
    			return 'UKNOWN CACHE STATUS';
    		break;
	};
}
