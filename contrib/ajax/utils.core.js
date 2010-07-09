// Path in the server if the system is in a subfolder. 
// Example: http://www.example.com/folder/ path must be /board/
var server = {
	host:'http://' + document.domain + '/',
	path:'',
	pages:['post', 'comment']
}

var classes = [];
var savedClasses = false;

$(document).ready(function(){
	setPath();
	onPageRefresh();
});

function setPath(){
	var paths = location.pathname.split("/");
	var realPath = [];
	
	for(var i=1; i< paths.length; i++) {
			if(inArray(server.pages, paths[i])){
				break;
			}
			else{
				realPath.push(paths[i]);
			}
    }
	if(realPath.length > 0){
		server.path = realPath.join('/') + "/";
	}
}

function inArray(arr, val) {
      isin = false;
      for (i = 0; i < arr.length; i++)
      	if (val == arr[i]){
      		isin = true;
		}
      return isin;
}

function onPageRefresh(){
	var mode = $.cookie("mode");
	
	$("#mode option").each(function(){
		var value = $(this).val();
		if(value == mode){
			$(this).attr("selected", "selected");
		}
	});
	
	if((mode != "view") && (mode != null)){
		$(".thumb a").removeAttr("href");
	}
}

function PostModeMenu() {
	
	var mode = $("#mode").val();
	var date = new Date();
	date.setTime(date.getTime() + (15 * 60 * 1000));
	
	var options = { path: '/', expires: date };		
	$.cookie("mode", mode, options);
			
	if(mode=="view" || mode == null){
		$("#mode").val("View posts");
		
		var i = 0;
		$(".thumb a").each(function(){
			var id = $(this).attr("id").substring(6);
			$(this).attr("href", server.host + server.path + "post/view/" + id);
			
			$(this).attr("class", classes[i]);
			i++;
		});
	}
	else{
		$(".thumb a").removeAttr("href");
		
		$(".thumb a").each(function(){
			var class = $(this).attr("class");
			
			if(!savedClasses){
				classes.push(class);
			}
		});
		savedClasses = true;
	}
}

function FileClick(id){
	var mode = $.cookie("mode");
	
	if($("#mode").val() != $.cookie("mode")){
		mode = $("#mode").val();
	}
		
	if(mode=="edit"){
		var tags;
		var image = get_image_info(id);
		
		if(image){
			tags = image.tags;
		}
		else{
			tags = "tagme";
		}
		
		tags = prompt("Enter Tags", tags);
		tags = $.trim(tags);
		if((tags!="") && (tags!=null)){
			set_tags(id, tags);
		}
	}
	
	if(mode=="report"){
		var reason = prompt("Enter Reason");
		reason = $.trim(reason);
		
		if((reason!="") && (reason!=null)){
			PostReport(id, reason);
		}
	}
	
	if(mode=="delete"){
		if(confirm("Are you sure you want to delete post " + id + "?")){
			PostDelete(id);
		}
	}
	
	if(mode=="ban"){
		if(confirm("Are you sure you want to ban and delete post " + id + "?")){
			var reason = prompt("Enter Reason");
			PostBan(id, reason);
		}
	}
	
	if(mode=="admin-approved"){		
		PostStatus(id, "a");
	}
	
	if(mode=="admin-locked"){	
		PostStatus(id, "l");
	}
	
	if(mode=="admin-pending"){		
		PostStatus(id, "p");
	}
	
	if(mode=="admin-deleted"){		
		PostStatus(id, "d");
	}
	
	if(mode=="add-fav"){
		PostFavorite(id, "set");
	}
	
	if(mode=="remove-fav"){
		PostFavorite(id, "unset");
	}
	
	if(mode=="vote-up"){
		PostVote(id, "up");
	}
	
	if(mode=="vote-down"){
		PostVote(id, "down");
	}
	
	if(mode=="rate-safe"){
		PostRate(id, "s");
	}
	
	if(mode=="rate-questionable"){
		PostRate(id, "q");
	}
	
	if(mode=="rate-explicit"){
		PostRate(id, "e");
	}
}

function set_tags(id, tags){
	
	$.ajax({
		type: "POST",
		cache: false,
		url: server.host + server.path + "ajax/image/edit",
		data: "image_id=" + id + "&tags=" + tags
	});
}

function get_image_info(id){
	var image;
	
	$.ajax({
		type: "POST",
		async: false,
  		url: server.host + server.path + "ajax/image/info",
		data: "image_id=" + id,
  		dataType: "json",
  		success: function(data){
				image = data;
		}
	});
	
	if (image) return image;
}

function PostReport(id, reason){
	$.ajax({
	   type: "POST",
	   cache: false,
	   url: server.host + server.path + "ajax/image/report",
	   data: "image_id=" + id + "&reason=" + reason
	});
}

function PostDelete(id){
	$.ajax({
	   	type: "POST",
	   	cache: false,
	   	url: server.host + server.path + "ajax/image/delete",
	   	data: "image_id=" + id,
	   	success: function(){
			$('#thumb_' + id).fadeOut("slow");
		}
	});
}

function PostBan(id, reason){
	$.ajax({
	   	type: "POST",
	   	cache: false,
	   	url: server.host + server.path + "ajax/image/ban",
	   	data: "image_id=" + id + "&reason=" + reason,
	   	success: function(){
			$('#thumb_' + id).fadeOut("slow");
		}
	});
}

function PostStatus(id, status){
	
	if(((status=="l") || (status=="a") || (status=="p") || (status=="d")) && (id!=null)){
		$.ajax({
		   type: "POST",
		   cache: false,
		   url: server.host + server.path + "ajax/image/status",
		   data: "image_id=" + id + "&status=" + status
		});
	}
}

function PostFavorite(id, favorite){
	
	if(((favorite=="set") || (favorite=="unset")) && (id!=null)){
		$.ajax({
		   	type: "POST",
			cache: false,
		   	url: server.host + server.path + "ajax/image/favorite",
		   	data: "image_id=" + id + "&favorite=" + favorite,
			success: function(){
				style_selector(id, favorite);
				var action;
				if(favorite=="set"){
					action = "added";
				}
				else{
					action = "removed";
				}
				$('#subheading p').detach();
				$('#subheading').append("<p>Post " + id + " was " + action + " to favorites.</p>");
				$('#subheading').slideDown("slow").delay(3000).slideUp("slow");
			}
		});
	}
}

function PostVote(id, vote){
	
	if(((vote=="up") || (vote=="down")) && (id!=null)){
		$.ajax({
		   	type: "POST",
			cache: false,
		   	url: server.host + server.path + "ajax/image/vote",
		   	data: "image_id=" + id + "&vote=" + vote,
			success: function(){
				style_selector(id, vote);
				$('#subheading p').detach();
				$('#subheading').append("<p>Post " + id + " was voted " + vote + ".</p>");
				$('#subheading').slideDown("slow").delay(3000).slideUp("slow");
			}
		});
	}
	
}

function PostRate(id, rate){
	
	if(((rate=="s") || (rate=="q") || (rate=="e")) && (id!=null)){
		$.ajax({
			type: "POST",
			cache: false,
		   	url: server.host + server.path + "ajax/image/rate",
		   	data: "image_id=" + id + "&rating=" + rate,
		   	success: function(){
				style_selector(id, rate);
				
				var rate_name;
				
				switch(rate){
					case "s": rate_name = "safe";
					break;
					case "q": rate_name = "questionable";
					break;
					case "e": rate_name = "explicit";
					break;
				}
				
				$('#subheading p').detach();
				$('#subheading').append("<p>Post " + id + " was rated as " + rate_name + ".</p>");
				$('#subheading').slideDown("slow").delay(3000).slideUp("slow");
			}
		});
	}

}

function style_selector(id, style){
	switch(style){
		case "a": change_style(id, "approved");
		break;
		case "l": change_style(id, "locked");
		break;
		case "p": change_style(id, "pending");
		break;
		case "d": change_style(id, "deleted");
		break;
		case "set": change_style(id, "favorited");
		break;
		case "unset": change_style(id, "un-favorited");
		break;
		case "up": change_style(id, "voted-up");
		break;
		case "down": change_style(id, "voted-down");
		break;
		case "s": change_style(id, "rated-safe");
		break;
		case "q": change_style(id, "rated-questionable");
		break;
		case "e": change_style(id, "rated-explicit");
		break;
	}
}

function change_style(id, mode){
	$(".thumb a").each(function(){
		if(id == $(this).attr("id").substring(6)){
			$(this).attr("class", mode);
		}
	});
}

function CommentPost(image_id){
	var comment = $("#comment_box").val();
	comment = $.trim(comment);
		
	if((comment!="") && (comment!=null)){
		$.ajax({
		   	type: "POST",
		   	cache: false,
		   	url: server.host + server.path + "ajax/comment/add",
		   	data: "image_id=" + image_id + "&comment=" + comment,
			success: function(){
				$("#comment_box").val("");
				$("#comment_box").fadeOut("slow");
				$("#comment_button").fadeOut("slow",function(){
					$('#comment_form').append("<div class='info'><p>Comment was added.</p></div>");
				});
				$("#comment_form .info").fadeOut("slow");
			}
		});
	}
}

function CommentRemove(comment_id){
	//comment_id = $.trim(comment_id);
	//vote = $.trim(vote);
	var element = "#comment-" + comment_id;
	if((comment_id!="") && (comment_id!=null) && (vote!="") && (vote!=null)){
		$.ajax({
		   	type: "POST",
		   	cache: false,
		   	url: server.host + server.path + "ajax/comment/remove",
		   	data: "comment_id=" + comment_id,
			success: function(){
					$(element).fadeOut("slow");
			}
		});
	}
}

function CommentVote(comment_id,vote){
	//comment_id = $.trim(comment_id);
	//vote = $.trim(vote);
	var element = "#vote-" + vote + "-" + comment_id;
	if((comment_id!="") && (comment_id!=null) && (vote!="") && (vote!=null)){
		$.ajax({
		   	type: "POST",
		   	cache: false,
		   	url: server.host + server.path + "ajax/comment/vote",
		   	data: "comment_id=" + comment_id + "&vote=" + vote,
			success: function(){
					$(element).fadeOut("slow");
			}
		});
	}
}