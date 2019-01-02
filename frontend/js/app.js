var lf_items;
$(document).ready(function(evt){
	console.log(getCacheStatus());
	$("#nav_new_item").hide();
	$("#nav_user_settings").hide();
	$("#nav_search").hide();
	$.getJSON(BASE_URL+"User/Status",function(data){if(setUserStatus(data))fetchAllItems();}).fail(apiCallFailed);
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
	$("#btn_new_item").click(function(evt){
		$("#new_item_form").trigger("reset");
		$("#newItemCategories").tagsinput("removeAll");
		$("#btn_choose_image_placeholder").css("display","block");
		$("#btn_choose_image_image").css("display","none");
		$("#btn_choose_image_image").attr("src","");
	});
	$("#btn_choose_image").click(function(evt){
		$("#newItemPicture").click();
		evt.stopPropagation();
		return false;
	});
	$("#new_item_form").submit(function(evt){
		var payload=objectifyForm($(this).serializeArray());
		payload['picture']=$("#btn_choose_image_image").attr("src");
		if(payload['number']>0&&payload['itemname']!=""){
		payload=JSON.stringify(payload);
		$.post(BASE_URL+"Items",payload,function(data){
			$("#modal_new_item").modal("hide");
			setUserStatus(data);
			console.log(data);
			addNewItem(data.payload.item[0]);		
		}).fail(apiCallFailed);
		}
		evt.stopPropagation();
		return false;
	});
}

function addNewItem(data){
	console.log("Neues Item wird hinzugefügt.");
	lf_items.items.push(data);
	displayItems(lf_items);
}
/**
fetch all Items which belong to the user
**/
function fetchAllItems(){
	console.log("fetching items!");
	$.getJSON(BASE_URL+"Items")
		.done(function(data){
			console.log(data);
			lf_items=data.payload;
			displayItems(data.payload);
		})
		.fail(apiCallFailed);
}
function displayItems(data){
	$("#list_categories").html("");
	var template=Handlebars.compile($("#template_categories_list").html());
	$.each(data.categories,function(k,val){
		let html=template(val);
		$(html).appendTo("#list_categories").click(function(evt){
			//alert("click!");
		});
	});
	$("#lagerfeld").html("");
	var template=Handlebars.compile($("#template_items_list").html());
	$.each(data.items,function(k,val){
		let html=template(val);
		let elem=$(html);
		elem.appendTo("#lagerfeld");
		if(val.anzahl<=1)elem.find(".badge").css("display","none");
		if(val.picture==null)elem.find(".list-item-picture").css("visibility","hidden");
	});
	//$("#content").nestable("destroy");
	$('#content').nestable({
		"listNodeName":"ul",
		"itemClass":"list-group-item",
		"listClass":"list-group",
		onDragStart: function (l, e) {
        		//only enable container items
			console.log("on drag start!");
                	l.find("[data-container=0]").addClass('dd-nochildren');
       		},
		beforeDragStop: function(l,e, p){
        		/**@todo: wenn mehrere Items, dann nachfragen, wieviele verschoben werden sollen**/
			// l is the main container
        		// e is the element that was moved
        		// p is the place where element was moved.
			return true;
    		},
		callback: function(l,e){
        		// l is the main container
        		// e is the element that was moved
			console.log("moved!");
			console.log(e.data("id"));
			console.log(e.parent("lagerfeld-item").data("id"));
			console.log("change item: set id to X");
    		}
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
	if(data.payload.user.status=="not logged in"){
		$("#container").hide();
		$("#nav_user_settings").hide();
		$("#nav_search").hide();
		$("#nav_user_login a").attr("href",data.payload.user.authURL);
		$("#nav_user_login").show();
		$("#nav_menu").hide();
		$("#nav_brand").hide();
		return false;
	}
	else if(data.payload.user.status=="logged in"){
		$("#nav_user_settings").find(".nav-desc").html(data.payload.user.userdata.email);
		$("#nav_user_settings").show();
		$("#nav_search").show();
		$("#container").show();
		$("#nav_user_login").hide();
		$("#modal_user_settings").find(".modal-body").html("logged in as : "+data.payload.user.userdata.email);
		$("#nav_menu").show();
		$("#nav_brand").hide();
		$("#modal_left").find(".modal-title").html($("#nav_brand").html());
		return true;
	}
	return false;
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
