// Path in the server if the system is in a subfolder. 
// Example: http://www.example.com/folder/ path must be /board/
var server = {
	host:'http://' + document.domain,
	path:''
}

function PostModeMenu() {
	var mode = $("#mode").val();
	var date = new Date();
	date.setTime(date.getTime() + (15 * 60 * 1000));

	var options = { path: '/', expires: date };
	$.cookie("mode", mode, options);
	
	$(".thumb a").removeAttr("href");
	
	if(mode=="view" || mode == null){
		$("#mode").val("View posts");
		
		$(".thumb a").each(function(){
			var id = $(this).attr("id").substring(6);
			$(this).attr("href", server.host + server.path + "post/view/" + id);
			
			var class = $(this).attr("class_style");
			$(this).attr("class", class);
			$(this).removeAttr("class_style");
		});
	}
	else{
		$(".thumb a").each(function(){			
			var class = $(this).attr("class");
			$(this).attr("class_style", class);
		});
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
		set_tags(id, tags);
	}
	
	if(mode=="admin-approved"){		
		set_status(id, "a");
	}
	
	if(mode=="admin-locked"){	
		set_status(id, "l");
	}
	
	if(mode=="admin-pending"){		
		set_status(id, "p");
	}
	
	if(mode=="admin-deleted"){		
		set_status(id, "d");
	}
	
	if(mode=="add-fav"){
		favorite(id, "set");
	}
	
	if(mode=="remove-fav"){
		favorite(id, "unset");
	}
	
	if(mode=="vote-up"){
		vote(id, "up");
	}
	
	if(mode=="vote-down"){
		vote(id, "down");
	}
	
	if(mode=="rate-safe"){
		rate(id, "s");
	}
	
	if(mode=="rate-questionable"){
		rate(id, "q");
	}
	
	if(mode=="rate-explicit"){
		rate(id, "e");
	}
	
	if(mode=="report"){
		var reason = prompt("Enter Reason");
		reason = $.trim(reason);
		
		if((reason!="") && (reason!=null)){
			report(id, reason);
		}
	}
}

function set_tags(id, tags){
	
	if((tags!="") && (tags!=null)){
		$.ajax({
			type: "POST",
			cache: false,
			url: server.host + server.path + "ajax/image/edit",
			data: "image_id=" + id + "&tags=" + tags
		});
	}
	else{
		alert("You must enter the new tags");
	}
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

function set_status(id, status){
	
	if(((status=="l") || (status=="a") || (status=="p") || (status=="d")) && (id!=null)){
		$.ajax({
		   type: "POST",
		   cache: false,
		   url: server.host + server.path + "ajax/image/status",
		   data: "image_id=" + id + "&status=" + status
		});
	}
}

function favorite(id, favorite){
	
	if(((favorite=="set") || (favorite=="unset")) && (id!=null)){
		$.ajax({
		   	type: "POST",
			cache: false,
		   	url: server.host + server.path + "ajax/image/favorite",
		   	data: "image_id=" + id + "&favorite=" + favorite,
		   	success: style_selector(id, favorite)
		});
	}
}

function vote(id, vote){
	
	if(((vote=="up") || (vote=="down")) && (id!=null)){
		$.ajax({
		   	type: "POST",
			cache: false,
		   	url: server.host + server.path + "ajax/image/vote",
		   	data: "image_id=" + id + "&vote=" + vote,
		   	success: style_selector(id, vote)
		});
	}
	
}

function rate(id, rate){
	
	if(((rate=="s") || (rate=="q") || (rate=="e")) && (id!=null)){
		$.ajax({
			type: "POST",
			cache: false,
		   	url: server.host + server.path + "ajax/image/rate",
		   	data: "image_id=" + id + "&rating=" + rate,
		   	success: style_selector(id, rate)
		});
	}

}

function report(id, reason){
	$.ajax({
	   type: "POST",
	   cache: false,
	   url: server.host + server.path + "ajax/image/report",
	   data: "image_id=" + id + "&reason=" + reason
	});
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