// Path in the server if the system is in a subfolder. 
// Example: http://www.example.com/folder/ path must be /board/
var server = {
	host:'http://' + document.domain + '/',
	path:'',
	pages:['post', 'comment']
}

var tempPosts = new Array();

var savedStyle = {
	liStyle:'',
	aClass:[],
	aSaved:false
}

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
	
	$(".thumbblock li").each(function(){		
		savedStyle.liStyle = $(this).attr("style");
	});
	savedStyle.aSaved = true;
	
	tempPosts.length = 0;
	$(".thumb a").each(function(){
		tempPosts.push($(this).attr("id").substring(6));
	});
}

function removeItem(originalArray, itemToRemove) {
	var j = 0;
	while (j < originalArray.length) {
		if (originalArray[j] == itemToRemove) {
			originalArray.splice(j, 1);
		} else { j++; }
	}

	return originalArray;
}

function PostModeMenu() {
	
	var mode = $("#mode").val();
	var date = new Date();
	date.setTime(date.getTime() + (15 * 60 * 1000));
	
	var options = { path: '/', expires: date };		
	$.cookie("mode", mode, options);
	
	tempPosts.length = 0;
	$(".thumb a").each(function(){
		tempPosts.push($(this).attr("id").substring(6));
	});
				
	if(mode=="view" || mode == null){
		$("#mode").val("View posts");
		
		var i = 0;
		$(".thumb a").each(function(){
			var id = $(this).attr("id").substring(6);
			$(this).attr("href", server.host + server.path + "post/view/" + id);
			
			$(this).attr("class", savedStyle.aClass[i]);
			i++;
		});
	}
	else{
		$(".thumb a").removeAttr("href");
		
		$(".thumbblock li").each(function(){		
			savedStyle.liStyle = $(this).attr("style");
		});
		
		$(".thumb a").each(function(){
			var class = $(this).attr("class");
			
			if(!savedStyle.aSaved){
				savedStyle.aClass.push(class);
			}
		});
		savedStyle.aSaved = true;
	}
}

function FileClick(id){
	var mode = $.cookie("mode");
	
	if($("#mode").val() != $.cookie("mode")){
		mode = $("#mode").val();
	}
		
	if(mode=="edit"){
		var tags;
		var image = Post.Info(id);
		
		if(image){
			tags = image.tags;
		}
		else{
			tags = "tagme";
		}
		
		tags = prompt("Enter Tags", tags);
		tags = $.trim(tags);
		if((tags!="") && (tags!=null)){
			Post.Edit(id, tags);
		}
	}
	
	if(mode=="report"){
		var reason = prompt("Enter Reason");
		reason = $.trim(reason);
		
		if((reason!="") && (reason!=null)){
			Post.Report(id, reason);
		}
	}
	
	if(mode=="delete"){
		if(confirm("Are you sure you want to delete post " + id + "?")){
			Post.Delete(id);
		}
	}
	
	if(mode=="ban"){
		if(confirm("Are you sure you want to ban and delete post " + id + "?")){
			var reason = prompt("Enter Reason");
			Post.Ban(id, reason);
		}
	}
	
	if(mode=="admin-approved"){		
		Post.Status(id, "a");
	}
	
	if(mode=="admin-locked"){	
		Post.Status(id, "l");
	}
	
	if(mode=="admin-pending"){		
		Post.Status(id, "p");
	}
	
	if(mode=="admin-deleted"){		
		Post.Status(id, "d");
	}
	
	if(mode=="add-fav"){
		Post.Favorite(id, "set");
	}
	
	if(mode=="remove-fav"){
		Post.Favorite(id, "unset");
	}
	
	if(mode=="vote-up"){
		Post.Vote(id, "up");
	}
	
	if(mode=="vote-down"){
		Post.Vote(id, "down");
	}
	
	if(mode=="rate-safe"){
		Post.Rate(id, "s");
	}
	
	if(mode=="rate-questionable"){
		Post.Rate(id, "q");
	}
	
	if(mode=="rate-explicit"){
		Post.Rate(id, "e");
	}
}

Post = {
	Info: function(id){
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
	},
	
	Next: function(id){
		var image = 'error';
		
		$.ajax({
			type: "POST",
			async: false,
			url: server.host + server.path + "ajax/image/next",
			data: "image_id=" + id,
			dataType: "json",
			success: function(data){
					image = data;
			}
		});
		
		return image;
	},
	
	
	Prev: function(id){
		var image = 'error';
		
		$.ajax({
			type: "POST",
			async: false,
			url: server.host + server.path + "ajax/image/prev",
			data: "image_id=" + id,
			dataType: "json",
			success: function(data){
					image = data;
			}
		});
		
		return image;
	},
	
	Edit: function(id, tags){
		$.ajax({
			type: "POST",
			cache: false,
			url: server.host + server.path + "ajax/image/edit",
			data: "image_id=" + id + "&tags=" + tags,
			success: function(data){
					var msg = $("<p>Tags has been setted for the post " + id + ".</p>").hide();
					
					$('#subheading p').detach();					
					$('#subheading').append(msg);
					msg.fadeIn("slow").delay(3000).fadeOut("slow");
			}
		});
	},
	
	Report: function(id, reason){
		$.ajax({
			type: "POST",
			cache: false,
			url: server.host + server.path + "ajax/image/report",
			data: "image_id=" + id + "&reason=" + reason
		});
	},
	
	Delete: function(id){
		var newPost = Post.Next(tempPosts[tempPosts.length - 1]);
		tempPosts = removeItem(tempPosts, id);
		tempPosts.push(newPost.id);
				
		$.ajax({
	   		type: "POST",
	   		cache: false,
	   		url: server.host + server.path + "ajax/image/delete",
	   		data: "image_id=" + id,
	   		success: function(){
				$('#thumb_' + id).fadeOut("slow", function(){
					$('#thumb_' + id).detach();
					if(newPost != 'error'){
						var postImage = '<img width="'+newPost.thumb_width+'" height="'+newPost.thumb_height+'" src="'+newPost.thumb+'" alt="'+newPost.tooltip+'" title="'+newPost.tooltip+'">';
						var postLink = '<a onclick="FileClick('+newPost.id+');" id="thumb_' + newPost.id + '" style="display: inline-block; height: ' + newPost.thumb_height + 'px; width: ' + newPost.thumb_width + 'px;">' + postImage + '</a>';
						$('.thumbblock').append('<li id="thumb_' + newPost.id + '" class="thumb" style="'+savedStyle.liStyle+'">' + postLink + '</li>');
					}
				});
			}
		});
	},
	
	Ban: function(id, reason){
		var newPost = Post.Next(tempPosts[tempPosts.length - 1]);
		tempPosts = removeItem(tempPosts, id);
		tempPosts.push(newPost.id);

		$.ajax({
	   		type: "POST",
	   		cache: false,
	   		url: server.host + server.path + "ajax/image/ban",
	   		data: "image_id=" + id + "&reason=" + reason,
	   		success: function(){
				$('#thumb_' + id).fadeOut("slow", function(){
					$('#thumb_' + id).detach();
					if(newPost != 'error'){
						var postImage = '<img width="'+newPost.thumb_width+'" height="'+newPost.thumb_height+'" src="'+newPost.thumb+'" alt="'+newPost.tooltip+'" title="'+newPost.tooltip+'">';
						var postLink = '<a onclick="FileClick('+newPost.id+');" id="thumb_' + newPost.id + '" style="display: inline-block; height: ' + newPost.thumb_height + 'px; width: ' + newPost.thumb_width + 'px;">' + postImage + '</a>';
						$('.thumbblock').append('<li id="thumb_' + newPost.id + '" class="thumb" style="'+savedStyle.liStyle+'">' + postLink + '</li>');
					}
				});
			}
		});
	},
	
	Status: function(id, status){
		if(((status=="l") || (status=="a") || (status=="p") || (status=="d")) && (id!=null)){
			$.ajax({
			   type: "POST",
			   cache: false,
			   url: server.host + server.path + "ajax/image/status",
			   data: "image_id=" + id + "&status=" + status
			});
		}
	},
	
	Favorite: function(id, favorite){
		if(((favorite=="set") || (favorite=="unset")) && (id!=null)){
			$.ajax({
				type: "POST",
				cache: false,
				url: server.host + server.path + "ajax/image/favorite",
				data: "image_id=" + id + "&favorite=" + favorite,
				success: function(){
					style_selector(id, favorite);
					
					var msg = $("<p>Error.</p>").hide();
					
					if(favorite=="set"){
						$('#post-favorite-set').hide();
						$('#post-favorite-unset').show();
						msg = $("<p>Post " + id + " was added to favorites.</p>").hide();
					}
					else{
						$('#post-favorite-unset').hide();
						$('#post-favorite-set').show();
						msg = $("<p>Post " + id + " was removed from favorites.</p>").hide();
					}
					
					$('#subheading p').detach();
					$('#subheading').append(msg);
					msg.fadeIn("slow").delay(3000).fadeOut("slow");
				}
			});
		}
	},
		
	Vote: function(id, vote){
		if(((vote=="up") || (vote=="null") || (vote=="down")) && (id!=null)){
			$.ajax({
				type: "POST",
				cache: false,
				url: server.host + server.path + "ajax/image/vote",
				data: "image_id=" + id + "&vote=" + vote,
				success: function(){
					style_selector(id, vote);
					
					var msg = $("<p>Error.</p>").hide();
					
					if(vote=="up"){
						$('#post-vote-up').hide();
						$('#post-vote-remove').show();
						$('#post-vote-down').show();

						msg = $("<p>Post " + id + " was voted up.</p>").hide();
					}
					else if(vote=="null"){
						$('#post-vote-up').show();
						$('#post-vote-remove').hide();
						$('#post-vote-down').show();
						
						msg = $("<p>Post " + id + " was removed.</p>").hide();
					}
					else{
						$('#post-vote-up').show();
						$('#post-vote-remove').show();
						$('#post-vote-down').hide();
						
						msg = $("<p>Post " + id + " was voted down.</p>").hide();
					}
					
					$('#subheading p').detach();
					$('#subheading').append(msg);
					msg.fadeIn("slow").delay(3000).fadeOut("slow");
				}
			});
		}
		
	},
	
	Rate: function(id, rate){
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
					
					var msg = $("<p>Post " + id + " was rated as " + rate_name + ".</p>").hide();
					
					$('#subheading p').detach();					
					$('#subheading').append(msg);
					msg.fadeIn("slow").delay(3000).fadeOut("slow");
				}
			});
		}
	
	}
}

Comment = {
	Post: function(image_id){
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
	},
	
	Remove: function(comment_id){
		var element = "#comment-" + comment_id;
		if((comment_id!="") && (comment_id!=null)){
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
	},
	
	Vote: function(comment_id,vote){
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