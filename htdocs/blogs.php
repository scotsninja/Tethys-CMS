<?php
/* Copyright 20xx Productions */

require_once('src/common.php');

$urlArr = explode('/', $getParams['url']);
$blogUrl = $urlArr[1];
$postUrl = $urlArr[2];

$blog = (is_numeric($blogUrl)) ? Blog::getById($blogUrl) : Blog::getByUrl($blogUrl);

if (!$blog) {
	$blog = Blog::getDefault();
}

if ($blog && $postUrl != '') {
	$post = (is_numeric($postUrl)) ? BlogPost::getById($postUrl) : BlogPost::getByUrl($postUrl);
	
	if (!($post && ($post->blog == $blog->id))) {
		$post = null;
	}
}

/* meta tags */
if ($post) {
	$metaTitle = $blog->name . ' - ' . $post->title;
	$metaDescription = $post->getBlurb(100, false);
	$metaKeywords = $post->tags;
} else if ($blog) {
	$metaTitle = $blog->name;
	$metaDescription = $blog->description;
	$metaKeywords = $blog->categories;
}

$includes['headers'] = '<link rel="alternate" type="application/rss+xml" title="'.$blog->name.'" href="'.CORE_DOMAIN.CORE_RSS_DIR.$blog->getRssFile().'" />';

$includes['css'] = '<style type="text/css">
	#blog-header {
		margin:10px 0px;
		padding:10px;
		background-color:#777;
		color:white;
	}
	#blog-header h1 a {
		color:white;
		text-decoration:none;
	}
	#blog-header h4 {
		font-style:italic;
		color:#ccc;
	}
	
	.blog-nav {
		height:46px;
		background-color:#888;
		-webkit-border-radius: 16px;
		-moz-border-radius: 16px;
		border-radius:16px;
	}
	.blog-nav table {
		width:100%;
		height:46px;
	}
	.blog-nav td {
		width:50%;
		padding:10px;
	}
	
	.post {
		-webkit-border-radius: 16px;
		-moz-border-radius: 16px;
		border-radius:16px;
		margin:10px 0px;
		padding:10px;
	}

	.post h4 {
		font-size:1.4em;
	}
	
	.post h4 a, .post h4 a:visited  {
		text-decoration:none;
		color:#222;
	}
	
	.post h4 a:hover  {
		color:#666;
	}
	
	.post a, .post a:visited {
		color:#36c;
	}
	
	.post a:hover {
		color:#69e;
	}

	.post .excerpt {
		margin:8px 0px;
	}
	.post .tags {
		margin:6px 6px 10px 6px;
		background-color: #6af;
		border: 2px solid #48d;
		border-radius:10px;
		-moz-border-radius:10px;
		-webkit-border-radius:10px;
		padding:4px;
		font-weight:bold;
		color:#039;
	}
	
	.post .tags a, .post .tags a:visited {
		color:#039;
		text-decoration:none;
	}
	
	.post .tags a:hover {
		color:#36b;
	}
	
	.post .date {
		font-style:italic;
		font-size:0.9em;
	}
	
	.post ul {
		margin-left:18px;
	}
	
	.post ul li {
		margin: 8px 0px;
	}
	
	.post .heading {
		background-color:#999;
		font-size:1.1em;
		font-weight:normal;
		padding:6px;
	}
	
	#blog-list ul, #blog-archives ul {
		list-style-type:none;
	}
	
	#blog-archives li {
		font-weight:bold;
		padding:2px;
	}
	#blog-archives li a, #blog-archives li a:visited {
		text-decoration:none;
		color:#444;
	}
	
	#blog-archives li a:hover {
		color:#888;
	}
	
	.tagcloud a, .tagcloud a:visited {
		text-decoration:none;
		color:#444;
	}
	
	.tagcloud a:hover {
		color:#888;
		text-decoration:underline;
	}
	
	.tagcloud .smallest {font-size:x-small;}
	.tagcloud .small {font-size:small;}
	.tagcloud .medium {font-size:medium;}
	.tagcloud .large {font-size:large;}
	.tagcloud .largest {font-size:larger;font-weight:bold;}
	
	#feed {
		text-align:right;
	}
	
	#feed a, #feed:visited {
		text-decoration:none;
		font-size:1.1em;
		color:#444;
	}
	
	#bpGallery {
		background-color:#999;
		border:2px solid #666;
		border-radius:16px;
		-webkit-border-radius: 16px;
		-moz-border-radius: 16px;
		margin:10px 0px;
		padding:10px;
		height:450px;
	}
	
	#bpGallery #list {
		height:100%;
		float:left;
		width:25%;
		border:1px solid #666;
		margin-right:10px;
		padding:8px;
	}
	
	#bpGallery #list ul {
		list-style-type:none;
		text-align:left;
	}

	#bpGallery #list ul li {
		float:left;
	}

	#bpGallery #main {
		border:1px solid green;
	}
	
	#bpGallery #details {
		border:1px solid green;
	}
</style>';

// do page processing

include(CORE_INCLUDE_DIR.'header.php');

SystemMessage::output();

if ($post) {
	try {
		$blog->outputPost($post);
	} catch (Exception $e) { ?>
		<div class="<?php echo SystemMessage::getMessageClass(MSG_ERROR); ?>"><?php echo $e->getMessage(); ?></div>
	<?php }
} else if ($blog) {
	try {
		$blog->output();
	} catch (Exception $e) { ?>
		<div class="<?php echo SystemMessage::getMessageClass(MSG_ERROR); ?>"><?php echo $e->getMessage(); ?></div>
	<?php }
}

include(CORE_INCLUDE_DIR.'footer.php'); ?>