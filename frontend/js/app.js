$(document).ready(function(evt){
	console.log(getCacheStatus());
	$("#nav_new_item").hide();
	$("#nav_user_settings").hide();
	$("#nav_search").hide();
	$.getJSON(BASE_URL+"User/Status",setUserStatus).fail(apiCallFailed);
	bindButtons();
});
function readFile(file) {
	var reader = new FileReader();

	reader.onloadend = function () {
		processFile(reader.result, file.type);
	}

	reader.onerror = function () {
		alert('Die Datei konnte nicht gelesen werden!');
	}

	reader.readAsDataURL(file);
}

function processFile(dataURL, fileType) {
	var maxWidth = 128;
	var maxHeight = 128;

	var image = new Image();
	image.src = dataURL;

	image.onload = function () {
		var width = image.width;
		var height = image.height;
		var shouldResize = (width > maxWidth) || (height > maxHeight);

		if (!shouldResize) {
			setUploadPicture(dataURL);
			return;
		}

		var newWidth;
		var newHeight;

		if (width > height) {
			newHeight = height * (maxWidth / width);
			newWidth = maxWidth;
		} else {
			newWidth = width * (maxHeight / height);
			newHeight = maxHeight;
		}

		var canvas = document.createElement('canvas');

		canvas.width = newWidth;
		canvas.height = newHeight;

		var context = canvas.getContext('2d');

		context.drawImage(this, 0, 0, newWidth, newHeight);

		dataURL = canvas.toDataURL(fileType);
		setUploadPicture(dataURL);
	};

	image.onerror = function () {
		alert('Das Bild konnte nicht verarbeitet werden!');
	};
}
function setUploadPicture(dataUrl){
	$("#btn_choose_image_placeholder").css("display","none");
	$("#btn_choose_image_image").css("display","block");
	$("#btn_choose_image_image").attr("src",dataUrl);
}
function bindButtons(){
	if (window.File && window.FileReader && window.FormData) {
		var inputField = $('#newItemPicture');

		inputField.on('change', function (e) {
		var file = e.target.files[0];

		if (file) {
			if (/^image\//i.test(file.type)) {
				readFile(file);
			} else {
				alert('Bitte wähle ein Bild!');
			}
		}
		});
	} else {
		alert("File upload is not supported!");
	}

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
	$("#btn_choose_image").click(function(evt){
		$("#newItemPicture").click();
		evt.stopPropagation();
		return false;
	});
	$("#new_item_form").submit(function(evt){
		var payload=objectifyForm($(this).serializeArray());
		payload['picture']=$("#btn_choose_image_image").attr("src");
		payload=JSON.stringify(payload);
		console.log(payload);
		$.post(BASE_URL+"Items",payload,function(data){
			console.log("DATEN ERHALTEN!");
			console.log(data);
		}).fail(apiCallFailed);
		alert("submit!");
		evt.stopPropagation();
		return false;
	});
}

function objectifyForm(formArray) {//serialize data function
  var returnArray = {};
  for (var i = 0; i < formArray.length; i++){
    returnArray[formArray[i]['name']] = formArray[i]['value'];
  }
  return returnArray;
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
