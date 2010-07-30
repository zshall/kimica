BBcode = {
	Quote: function(fieldId, user, message){
		field=document.getElementById(fieldId);
				
		if (document.selection) {
			field.focus();
			sel = document.selection.createRange();
			sel.text = '[quote=' + user + ']' + message + '[/quote]';
		}
		else if (field.selectionStart || field.selectionStart == 0) {
			var startPos = field.selectionStart;
			var endPos = field.selectionEnd;
			field.focus();
			field.value = field.value.substring(0, startPos) + '[quote=' + user + ']' + message + '[/quote]' + field.value.substring(endPos, field.value.length);
		} 
	}
}