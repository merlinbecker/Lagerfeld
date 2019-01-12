var template_items;
$(document).ready(function(evt){
	console.log(getCacheStatus());
	$("#nav_new_item").hide();
	$("#nav_user_settings").hide();
	$("#nav_search").hide();
	template_items=Handlebars.compile($("#template_items_list").html());
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
	if(data.parent_id==0)delete data.parent_id;
	$(".dd").nestable("add",data);
}
/**
fetch all Items which belong to the user
**/
function fetchAllItems(){
	console.log("fetching items!");
	$.getJSON(BASE_URL+"Items")
		.done(function(data){
			console.log(data);
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
	let tree=unflatten(data.items);	
	console.log(tree);
	$('#lagerfeld').nestable({
		"listNodeName":"ul",
		"expandBtnHTML":"<button class='btn dd-expand' data-action='expand'><i class='fa fa-plus'></i></button>",
		"collapseBtnHTML":"<button class='btn dd-collapse' data-action='collapse'><i class='fa fa-minus'></i></button>",
		"itemClass":"list-group-item",
		"listClass":"list-group",
		"json":tree,
		"scroll":true,
		itemRenderer: function(item_attrs, content, children, options, item) {
			let html=template_items(item);
			if(children!="<ul class=\"list-group\"></ul>")
				html = html.replace("CHILDREN", children);
			else html=html.replace("CHILDREN","");	
			return html;
		},
		onDragStart: function (l, e) {
        		//only enable container items
			console.log("on drag start!");
                	l.find("[data-container=0]").addClass('dd-nochildren');
			l.find("[data-container=1]").find(".indicator_container").css("display","block");
       		},
		beforeDragStop: function(l,e, p){
        		/**@todo: wenn mehrere Items, dann nachfragen, wieviele verschoben werden sollen**/
			// l is the main container
        		// e is the element that was moved
        		// p is the place where element was moved.
			l.find("[data-container=1]").find(".indicator_container").css("display","none");
			e.find(".indicator_container").css("display","none");
			let par=p.parent().data("id");
			let ident=e.data("id");
			let data_ids=e.data("grouped-ids");
			if(par==undefined){par=0;}
			var data=Object();
			data['id']=ident;
			data['grouped_ids']=data_ids;
			//check if more than one item is grouped
			let count_group=String(data['grouped_ids']).split(",");
			if(count_group.length>1){
				let anzahl=window.prompt("Wieviele Items sollen verschoben werden?", count_group.length);
				if(anzahl == null) return false;
				data['anzahl']=anzahl;
			}else data['anzahl']=1;
			data['parent']=par;
			let splitted=false;
			if(data['anzahl']<count_group.length){
				splitted=true;
				let rest=count_group.length-data['anzahl'];
				//change the old items count
				//(at)todo: auch die data-grouped ändern!!
				e.find(".lf-item-anzahl").html("x"+rest);
			}
			updateItem(data,splitted);
			console.log("MOVE Item"+ident+" in "+par+" with grouped items"+data_ids+" and do a split ",splitted);
			return false;
    		},
		callback: function(l,e){
        		// l is the main container
        		// e is the element that was moved
    		}
		});
		$("#lagerfeld").nestable("collapseAll");
		$(".lf-container-select").click(function(evt){
			alert("click!");
		});
    }
function updateItem(data,splitted=false){
	payload=JSON.stringify(data);
	var old_id=data.id;
	$.post(BASE_URL+"Items",payload,function(data){
			setUserStatus(data);
			console.log(data);
			if(!splitted)
				$(".dd").nestable("remove",old_id);
			addNewItem(data.payload.item[0]);		
		}).fail(apiCallFailed);
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
		$("#content").hide();
		$("#nav_user_settings").hide();
		$("#nav_search").hide();
		$("#nav_user_login a").attr("href",data.payload.user.authURL);
		$("#nav_user_login").show();
		$("#nav_menu").hide();
		$("#nav_brand").show();
		return false;
	}
	else if(data.payload.user.status=="logged in"){
		$("#nav_user_settings").find(".nav-desc").html(data.payload.user.userdata.email);
		$("#nav_user_settings").show();
		$("#nav_search").show();
		$("#content").show();
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
Handlebars.registerHelper('ifCond', function (v1, operator, v2, options) {
    switch (operator) {
        case '==':
            return (v1 == v2) ? options.fn(this) : options.inverse(this);
        case '===':
            return (v1 === v2) ? options.fn(this) : options.inverse(this);
        case '!=':
            return (v1 != v2) ? options.fn(this) : options.inverse(this);
        case '!==':
            return (v1 !== v2) ? options.fn(this) : options.inverse(this);
        case '<':
            return (v1 < v2) ? options.fn(this) : options.inverse(this);
        case '<=':
            return (v1 <= v2) ? options.fn(this) : options.inverse(this);
        case '>':
            return (v1 > v2) ? options.fn(this) : options.inverse(this);
        case '>=':
            return (v1 >= v2) ? options.fn(this) : options.inverse(this);
        case '&&':
            return (v1 && v2) ? options.fn(this) : options.inverse(this);
        case '||':
            return (v1 || v2) ? options.fn(this) : options.inverse(this);
        default:
            return options.inverse(this);
    }
});
function unflatten(arr) {
  var tree = [],
      mappedArr = {},
      arrElem,
      mappedElem;

  // First map the nodes of the array to an object -> create a hash table.
  for(var i = 0, len = arr.length; i < len; i++) {
    arrElem = arr[i];
    mappedArr[arrElem.id] = arrElem;
    mappedArr[arrElem.id]['children'] = [];
  }


  for (var id in mappedArr) {
    if (mappedArr.hasOwnProperty(id)) {
      mappedElem = mappedArr[id];
      // If the element is not at the root level, add it to its parent array of children.
      if (mappedElem.parent_id) {
        mappedArr[mappedElem['parent_id']]['children'].push(mappedElem);
      }
      // If the element is at the root level, add it to first level elements array.
      else {
        tree.push(mappedElem);
      }
    }
  }
  return tree;
}
