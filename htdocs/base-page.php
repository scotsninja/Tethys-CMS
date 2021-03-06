<?php
/* Copyright 20xx Productions */

require_once('src/common.php');

// get page
$page = $_GET['url'];

if ($page == '') {
	include(CORE_DIR_DEPTH.'403.php');
	exit();
}

// friendly urls for blogs
if (preg_match('~^blogs(/.*)?~i', $page, $matches)) {
	$page = 'blogs';
	$_SERVER['QUERY_STRING'] .= '&id='.substr($matches[1], 1);
}

// friendly urls
switch ($page) {
	case 'homepage':
	case 'index.php':
		$page = 'index.php';
		$dPage = '';
	break;
	case 'blog':
	case 'blogs':
		$page = 'blogs.php';
		$dPage = 'blogs';
	break;
	case 'contact':
		$page = 'contact.php';
		$dPage = 'contact';
	break;
	default:
		$dPage = $page;
	break;
}

// get parameters
$paramStr = str_replace('&&', '&', $_SERVER['QUERY_STRING']);
parse_str($paramStr, $getParams);

// generate unique identifier for disqus comments
$dUrl = CORE_DOMAIN.$dPage;
$dUrl .= ($getParams['id']) ? '/'.$getParams['id'] : '';
$dIdentifier = getDisqusIdentifier($dUrl);

// if page file exists, include file
if (file_exists($page)) {
	include($page);
} else if ($page == 'temp-page') {
	if (is_numeric($getParams['id']) && $getParams['id'] > 0) {
		$tempPage = Page::getTempById($getParams['id']);
	}

	if ($tempPage) {
		/* meta tags */
		$includes['meta']['title'] = $tempPage['title'];
		
		if ($tempPage['css'] != '') {
			$includes['css'] = '<style type="text/css">'.$tempPage['css'].'</style>';
		}

		include(CORE_INCLUDE_DIR.'header.php');

		SystemMessage::output();

		if ($tempPage['includePage'] > 0) {
			$includePage = Page::getById($tempPage['includePage']);
			$includeWidth = 12 - $tempPage['width'];
		}

		if ($includePage) {
			if ($tempPage['includePosition'] == 'left') {
				echo '<div class="'.Page::getGridClass($includeWidth).'">'.$includePage->value.'</div>';
				echo '<div class="'.Page::getGridClass($tempPage['width']).' last">'.$tempPage['value'].'</div>';
			} else {
				echo '<div class="'.Page::getGridClass($tempPage['width']).'">'.$tempPage['value'].'</div>';
				echo '<div class="'.Page::getGridClass($includeWidth).' last">'.$includePage->value.'</div>';
			}
		} else {
			echo '<div class="'.Page::getGridClass($tempPage['width']).' last">'.$tempPage['value'].'</div>';
		}
		
		include(CORE_INCLUDE_DIR.'footer.php');
	} else {
		echo '<div class="'.SystemMessage::getMessageClass(MSG_ERROR).'">Invalid Page Id</div>';
	}
} else {
	// if page file does not exist, try to load page from db
	$pageObj = Page::getByUrl($page);
	
	if ($pageObj) {
		if ($pageObj->userLevel != 'none') {
			if ($pageObj->userLevel == 'admin') {
				User::requireLogin('admin');
			} else {
				User::requireLogin('user');
			}
		}
		
		/* meta tags */
		$includes['meta']['title'] = $pageObj->title;
		$includes['meta']['description'] = $pageObj->description;
		$includes['meta']['keywords'] = $pageObj->keywords;
		$includes['meta']['author'] = $pageObj->author;
		
		if ($pageObj->css != '') {
			$includes['css'] = '<style type="text/css">'.$pageObj->css.'</style>';
		}

		include(CORE_INCLUDE_DIR.'header.php');

		SystemMessage::output();

		try {
			$pageObj->output();
		} catch (Exception $e) {
			echo '<div class="'. SystemMessage::getMessageClass(MSG_ERROR).'">'. $e->getMessage().'</div>';
		}
		
		include(CORE_INCLUDE_DIR.'footer.php');
	} // if no page in db, return 404 error 
	else {
		include(CORE_DIR_DEPTH.'404.php');
	}
}
