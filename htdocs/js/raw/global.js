// Copyright 20xx Productions

function openMask() {
	var maskHeight = $(document).height();
	var maskWidth = $(document).width();
	
	$('#mask').css({'width':maskWidth,'height':maskHeight});
	
	$('#mask').fadeIn(500);
	$('#mask').fadeTo("slow", 0.8);
}

function closeMask() {
	$('#mask').fadeOut();
}

function centerWindow(sel) {
	var winH = $(window).height();
	var winW = $(window).width();
	
	$(sel).css("top", winH/2-$(sel).height()/2);
	$(sel).css("left", winW/2-$(sel).width()/2);
}

function initTooltips() {
	if (gTooltips) {
		$(".tooltip").qtip({
			style: {
				classes: 'ui-tooltip-blue ui-tooltip-shadow'
			}
		});
	}
}

function initFancyBox(){
	$("a.fbox-img").fancybox({
		helpers: {
			title : {
				type : 'float'
			}
		}
	});
}

function setPageNum(field, page) {
	$('#'+field).val(page);
}

function init() {
	initTooltips();
	
	// benchmark page load
	var url = "/ajax/ajax_requests.php";
	var dataArr;
	
	dataArr = {
		"requireLogin": 0,
		"action": "log-pageload",
		"start": bmStartTime,
		"pageId": bmPageId,
		"page": bmPage,
		"vars": bmVars
	};
	
	if (bmLevel > 1) {
		$.post(url, dataArr);
	}
	
	// benchmark page render
	$(window).load(function () {
		var url = "/ajax/ajax_requests.php";
		var dataArr;
		
		dataArr = {
			"requireLogin": 0,
			"action": "log-pagerender",
			"start": bmStartTime,
			"pageId": bmPageId,
			"page": bmPage,
			"vars": bmVars
		};

		if (bmLevel > 1) {
			$.post(url, dataArr);
		}
	});
}