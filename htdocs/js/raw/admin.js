// Copyright 20xx Productions

function setMessage(id, msg, msgClass) {
	if (id == '') {
		return false;
	}

	// strip all message classes
	$('#'+id).removeClass('message-success');
	$('#'+id).removeClass('message-notice');
	$('#'+id).removeClass('message-error');
	
	// add new class
	if (msgClass != '') {
		$('#'+id).addClass(msgClass);
	}
	
	$('#'+id+' #msg-content').html(msg);
	
	// show message
	showMessage(id);
}

function showMessage(id) {
	if (id == '') {
		return false;
	}
	
	$('#'+id).show();
}

function hideMessage(id) {
	if (id == '') {
		return false;
	}
	
	$('#'+id).hide();
}
