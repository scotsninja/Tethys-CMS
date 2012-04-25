<?php
/* Copyright 20xx Productions */

require_once('../src/common.php');

User::requireLogin('admin', '../login.php', 'You do not have permission to view that page.');

$view = ($_GET['v'] == 'details') ? 'details' : 'list';
$pageTitle = ($view == 'details') ? 'Blog Manage' : 'Blog Search';

if ($view == 'details') {
	switch($_GET['p']) {
		case 'posts':
			if ($_GET['pid'] > 0) {
				$panel = 'posts-details';
				$post = BlogPost::getById($_GET['pid']);
			} else {
				$panel = 'posts-search';
			}
		break;
		case 'settings':
		default:
			$panel = 'settings';
		break;
	}
}

// if viewing blog details
if ($view == 'details') {
	if (is_numeric($_GET['id'])) {
		$tempBlog = Blog::getById($_GET['id']);
		
		if ($tempBlog && $post && $post->blog != $tempBlog->id) {
			unset($post);
		}
	}
	
	if ($tempBlog) {
		$pageTitle .= ': '.$tempBlog->name;
	}
} // else, view list of blogs
else {
	if ($_GET['t'] == 'delete') {
		$del = Blog::getById($_GET['id']);
		
		if ($del) {
			if ($del->delete()) {
				SystemMessage::save(MSG_SUCCESS, 'Blog successfully deleted.');
			} else {
				SystemMessage::save(MSG_ERROR, 'Error deleting blog.');
			}
		} else {
			SystemMessage::save(MSG_WARNING, 'Could not delete blog because it does not exist.');
		}
		
		header('Location: admin_blogs.php?v=list');
		exit();
	}
	
	// build search array
	$search = array();
	
	$perPage = (is_numeric($_GET['perpage']) && $_GET['perpage'] > 0) ? $_GET['perpage'] : 20;
	$pageNum = (is_numeric($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
	$sort = ($_GET['sort'] != '') ? $_GET['sort'] : 'blogName ASC';
	
	$blogs = Blog::search($search, $perPage, $pageNum, $totalResults, $sort);
}

$includes['css'] .= '<style type="text/css">@import url('.CORE_DIR_DEPTH.'plupload/jquery.plupload.queue/css/jquery.plupload.queue.css);</style>';
$includes['css'] .= '<style type="text/css">
	#blogManager {
		width:100%;
		border: 1px solid #66c;
		border-top:none;
		margin-top:0px;
		position:relative;
		top:-17px;
		padding: 10px 6px;
	}
	
	#blogNav {
		margin-bottom:0px;
	}
	
	#blogNav ul {
		text-align:left;
		margin: 1em 0em;
		font-weight:bold;
		border-bottom: 1px solid #66c;
		list-style-type:none;
		padding: 3px 10px;
	}
	
	#blogNav ul li {
		display:inline;
		background-color: #60C9F0;
		padding: 3px 4px;
		border: 1px solid #66c;
		margin-right:0px;
		border-bottom:none;
	}

	#blogNav ul li a {
		color: #666;
		text-decoration:none;
		z-index:100;
	}
	
	#blogNav ul li:hover {
		background-color:#ccc;
	}

	#blogNav li.selected {
		border-bottom: 1px solid #fff;
		background-color: #fff;
	}
	
	#imgSave {
		float:right;
		position:relative;
		top:-60px;
	}
	
	#msg {
		float:right;
		position:relative;
		right:50px;
		top:-55px;
	}
	
	#imgGallery ul {
		list-style-type:none;
		text-align:left;
		margin: 1em 0em;
		padding: 3px 10px;
	}

	#imgGallery ul li {
		float:left;
		padding: 4px;
		border:1px solid #444;
		margin:4px;
	}
	
	#imgGallery ul li.evenCell {
		background-color:#ddd;
	}

	#imgGallery ul li.oddCell {
		background-color:#999;
	}
</style>';

$includes['js'] = '<script type="text/javascript" src="'.CORE_DIR_DEPTH.'ckeditor/ckeditor.js"></script>';
$includes['js'] .= '<script type="text/javascript" src="'.CORE_DIR_DEPTH.'ckeditor/adapters/jquery.js"></script>';
$includes['js'] .= '<script type="text/javascript" src="'.CORE_DIR_DEPTH.CORE_JS_DIR.'jquery-ui-timepicker-addon.js"></script>';
$includes['js'] .= '<script type="text/javascript" src="'.CORE_DIR_DEPTH.CORE_JS_DIR.'admin.js"></script>';
$includes['js'] .= '<script type="text/javascript" src="'.CORE_DIR_DEPTH.'plupload/plupload.full.js"></script>';
$includes['js'] .= '<script type="text/javascript" src="'.CORE_DIR_DEPTH.'plupload/jquery.plupload.queue/jquery.plupload.queue.js"></script>';

$includes['js'] .= '<script type="text/javascript">
	function initUploader() {
		$("#uploader").pluploadQueue({
			runtimes : "gears,flash,silverlight,html5",
			url : "/upload.php",
			max_file_size : "10mb",
			chunk_size : "1mb",
			unique_names : true,
			resize : {width : 400, height: 400, quality : 90},
			filters : [
				{title : "Image files", extensions : "jpg,gif,png"}
			],
			flash_swf_url : "/plupload/plupload.flash.swf",
			silverlight_xap_url : "/plupload/plupload.silverlight.xap"
		});

		var uploader = $("#uploader").pluploadQueue();
		
		uploader.bind("UploadComplete", function(up, files) {
			transferToBlog(files);
		});
		
		// Client side form validation
		$("form").submit(function(e) {
			var uploader = $("#uploader").pluploadQueue();

			// Files in queue upload them first
			if (uploader.files.length > 0) {
				// When all files are uploaded submit form
				uploader.bind("StateChanged", function() {
					if (uploader.files.length === (uploader.total.uploaded + uploader.total.failed)) {
						$("form")[0].submit();
					}
				});
					
				uploader.start();
			} else {
				alert("You must queue at least one file.");
			}

			return false;
		});
	}
	
	function transferToBlog(files) {
		var blogId = $("#blogId").val();
		var panel = $("#blogPanel").val();
		
		if (blogId < 1) {
			return false;
		}
		
		if (files.length > 0) {
			var url	= "/ajax/admin_requests.php";
			var dataArr, action = (panel == "photos-upload") ? "blog-photo-add" : "blog-img-add", fileArr = new Array();
			
			for (var i = 0; i < files.length; i++) {
				fileArr.push(files[i].target_name);
			}
			
			dataArr = {
				"action": action,
				"id": blogId, 
				"files": fileArr
			};

			$.ajax({
				url: url,
				cache: false,
				data: dataArr,
				type: "POST",
				error: function(xhr, status, error) {
					alert("Error processing images: "+error);
				},
				success: function(data, status, xhr) {
					var jsonResponse = JSON.parse(data);
					
					if (jsonResponse.error == "" || jsonResponse.error == null) {
						if (panel == "photos-upload") {
							toggleBlogPanel("photos-search");
						} else {
							toggleBlogPanel("settings");
						}
						setMessage("msg", "Image uploaded successfully", "message-success");
					} else {
						setMessage("msg", "Error uploading 1 or more images", "message-error");
					}
				}
			});
		}
	}
	
	function loadPanel(panel, params) {
		var blogId = $("#blogId").val();

		var url = "/ajax/admin_output.php";
		var action, dataArr;

		if (panel == "posts-search") {
			action = "blog-posts-search";
			$("#postId").val("");
		} else if (panel == "posts-details") {
			action = "blog-posts-details";
			
			if (CKEDITOR.instances["ck_txtValue"]) {
				CKEDITOR.remove(CKEDITOR.instances["ck_txtValue"]);
			}
		} else if (panel == "photos-upload") {
			action = "blog-photos-upload";
			$("#postId").val("");
		} else if (panel == "photos-search") {
			action = "blog-photos-search";
			$("#postId").val("");
		} else if (panel == "settings") {
			action = "blog-settings";
			$("#postId").val("");
		} else {
			action = "";
			$("#postId").val("");
		}
		
		var postId = $("#postId").val();
		
		dataArr = {
			"action": action,
			"id": blogId,
			"pid": postId,
			"params": params
		};

		$.ajax({
			url: url,
			cache: false,
			data: dataArr,
			type: "POST",
			error: function(xhr, status, error) {
				alert("Error retrieving panel: "+error);
			},
			success: function(data, status, xhr) {
				$("#blogManager").html(data);
				initTooltips();

				if ((panel == "settings" || panel == "photos-upload") && blogId > 0) {
					initUploader();
				} else if (panel == "photos-search") {
					// set height of imgGallery
					var rows = 1, itemHeight = 0, items = 0;
					
					$("#imgGallery ul li").each(function(index, element) {
						if ($(element).height() > itemHeight) {
							itemHeight = $(element).height();
						}
						
						items++;
					});
					
					itemHeight += 20;
					rows = (items > 0) ? Math.ceil(items/3) : 1;
					
					$("#imgGallery").height(itemHeight*rows);
				}
			}
		});
	}
	
	function toggleBlogPanel(view) {
		if (view == "posts-search" || view == "posts-details" || view == "photos-search") {
			$("#tabPosts").addClass("selected");
			$("#tabUpload").removeClass("selected");
			$("#tabSettings").removeClass("selected");
		} else if (view == "photos-upload") {
			$("#tabUpload").addClass("selected");
			$("#tabPosts").removeClass("selected");
			$("#tabSettings").removeClass("selected");
		} else {
			$("#tabSettings").addClass("selected");
			$("#tabUpload").removeClass("selected");
			$("#tabPosts").removeClass("selected");
		}

		loadPanel(view);
	}
	
	function refreshResults() {
		var params;
		
		params = {
			"perpage": $("#perpage").val(),
			"page": $("#pagenum").val()
		};
		
		loadPanel("posts-search", params);
	}
	
	function successPostSearch(json, status, xhr) {
	}
	
	function successPhotoSave(json, status, xhr) {
		// display message
		if (json.error != "") {
			setMessage("msg", json.error, "message-error");
		} else if (json.success != "") {
			setMessage("msg", "Image(s) updated", "message-success");
		} else {
			setMessage("msg", "Operation complete", "message-notice");
		}
		
		// reload setting panel
		loadPanel("photos-search");
	}
	
	function successPostSave(json, status, xhr) {
		// display message
		if (json.error != "") {
			setMessage("msg", json.error, "message-error");
		} else if (json.success != "") {
			setMessage("msg", json.success, "message-success");
			$("#postId").val("");
		} else {
			setMessage("msg", "Operation complete", "message-notice");
			$("#postId").val("");
		}
		
		// reload setting panel
		if (json.id > 0) {
			loadPanel("posts-search");
		}
	}
	
	function successSettings(json, status, xhr) {
		// display message
		if (json.error != "") {
			setMessage("msg", json.error, "message-error");
		} else if (json.success != "") {
			setMessage("msg", json.success, "message-success");
		} else {
			setMessage("msg", "Operation complete", "message-notice");
		}
		
		// reload setting panel
		if (json.id > 0) {
			$("h1").html("Blog Manage: "+json.name);
			$("#blogId").val(json.id);
		
			loadPanel("settings");
		}
	}
	
	function successGeneric(json, status, xhr) {
		// display message
		if (json.error != "") {
			setMessage("msg", json.error, "message-error");
		} else if (json.success != "") {
			setMessage("msg", json.success, "message-success");
		} else {
			setMessage("msg", "Operation complete", "message-notice");
		}
	}
	
	function loadPost(id) {
		if (id < 1) {
			return false;
		}

		$("#postId").val(id);
		
		loadPanel("posts-details");
	}
	
	function deletePhoto(id) {
		if (id < 1) {
			return false;
		}
		
		var blogId = $("#blogId").val();
		var url = "/ajax/admin_requests.php";
		var dataArr, action = "blog-photo-delete";
			
		dataArr = {
			"action": action,
			"id": blogId,
			"photo": id
		};

		$.ajax({
			url: url,
			cache: false,
			data: dataArr,
			type: "POST",
			error: function(xhr, status, error) {
				setMessage("msg", "Unexected error: " +error+"\nPlease try again.", "message-error");
			},
			success: function(data, status, xhr) {
				var jsonResponse = JSON.parse(data);
				
				// display message
				if (jsonResponse.error != "") {
					setMessage("msg", jsonResponse.error, "message-error");
				} else if (jsonResponse.success != "") {
					setMessage("msg", jsonResponse.success, "message-success");
					
					// refresh list
					loadPanel("photos-search");
				} else {
					setMessage("msg", "Operation complete", "message-notice");
				}
			}
		});
	}
	
	function deletePost(id) {
		if (id < 1) {
			return false;
		}
		
		var blogId = $("#blogId").val();
		var url = "/ajax/admin_requests.php";
		var dataArr, action = "blog-post-delete";
			
		dataArr = {
			"action": action,
			"id": blogId,
			"post": id
		};

		$.ajax({
			url: url,
			cache: false,
			data: dataArr,
			type: "POST",
			error: function(xhr, status, error) {
				setMessage("msg", "Unexected error: " +error+"\nPlease try again.", "message-error");
			},
			success: function(data, status, xhr) {
				var jsonResponse = JSON.parse(data);
				
				// display message
				if (jsonResponse.error != "") {
					setMessage("msg", jsonResponse.error, "message-error");
				} else if (jsonResponse.success != "") {
					setMessage("msg", jsonResponse.success, "message-success");
					
					// refresh list
					loadPanel("posts-search");
				} else {
					setMessage("msg", "Operation complete", "message-notice");
				}
			}
		});
	}
	
	function updateUrl() {
		$("#txtUrl").val($("#txtTitle").val().split(" ").join("-").toLowerCase());

	}
	
	$(document).ready(function() {
		toggleBlogPanel("'.$panel.'");
		
		$("#imgSave").click(function(e) {
			var blogId = $("#blogId").val();
			var view = $("#blogPanel").val();
			var action, callback, checkBlogId = true;
			var params;
			
			if (view == "posts-search") {
				action = "";
				callback = "successPostSearch";
			} else if (view == "posts-details") {
				action = "blog-save-post";
				callback = "successPostSave";
				params = {"pid":"","title":"","value":"","tags":"","excerpt":"","comments":"","url":"","date":""};
				
				// get post id
				params.pid = $("#postId").val();
				
				// get value
				if ($("#ck_txtValue").val() != "") {
					params.value = $("#ck_txtValue").val();
				} else {
					alert("Please enter the post content.");
					return false;
				}
				
				// get title
				params.title = $("#txtTitle").val();
				
				// get tags
				params.tags = $("#txtTags").val();
				
				// get excerpt
				params.excerpt = $("#txtExcerpt").val();
				
				// get comments
				params.comments = $("#selComments").val();
				
				// get url
				params.url = $("#txtUrl").val();
				
				// get date
				params.date = $("#dtPost").val();
			} else if (view == "photos-search") {
				action = "blog-save-photos";
				callback = "successPhotoSave";
				params = {"ids":"","titles":"","captions":"","tags":"","dates":""};
				var imageArr = new Array(), titleArr = new Array(), captionArr = new Array(), tagArr = new Array(), dateArr = new Array();
				
				// get image Ids
				$(".imgId").each(function(index, element) {
					imageArr.push($(this).val());
				});
				
				// get titles
				$(".imgTitle").each(function(index, element) {
					titleArr.push($(this).val());
				});
				
				// get captions
				$(".imgCaption").each(function(index, element) {
					captionArr.push($(this).val());
				});
				
				// get tags
				$(".imgTags").each(function(index, element) {
					tagArr.push($(this).val());
				});
				
				// get dates
				$(".imgDate").each(function(index, element) {
					dateArr.push($(this).val());
				});
				
				params.ids = imageArr;
				params.titles = titleArr;
				params.captions = captionArr;
				params.tags = tagArr;
				params.dates = dateArr;
			} else if (view == "settings") {
				action = "blog-save-settings";
				callback = "successSettings";
				params = {"name":"","type":"","categories":"","description":"","url":"","default":"","active":""};
				checkBlogId = false;
				
				// get name
				if ($("#txtName").val() != "") {
					params.name = $("#txtName").val();
				} else {
					alert("Please enter a blog name");
					return false;
				}
				
				// get type
				if ($("#selType").val() != "") {
					params.type = $("#selType").val();
				} else {
					alert("Please select a valid blog type");
					return false;
				}
				
				// get categories
				params.categories = $("#txtCategories").val();
				
				// get description
				params.description = $("#txtDescription").val();
				
				// get url
				params.url = $("#txtUrl").val();
				
				// get default
				params.default = $("#chkDefault").is(":checked");
				
				// get active
				params.active = $("#chkActive").is(":checked");
			} else {
				setMessage("msg", "Invalid view", "message-notice");
				return false;
			}
			
			if (checkBlogId && blogId < 1) {
				return false;
			}
			
			var url = "/ajax/admin_requests.php";
			var dataArr;
			
			dataArr = {
				"action": action,
				"id": blogId,
				"params": params
			};

			$.ajax({
				url: url,
				cache: false,
				data: dataArr,
				type: "POST",
				error: function(xhr, status, error) {
					setMessage("msg", "Unexected error: " +error+"\nPlease try again.", "message-error");
				},
				success: function(data, status, xhr) {
					var jsonResponse = JSON.parse(data);
					
					if (callback == "successSettings") {
						successSettings(jsonResponse, status, xhr);
					} else if (callback == "successPostSearch") {
						successPostSearch(jsonResponse, status, xhr);
					} else if (callback == "successPhotoSave") {
						successPhotoSave(jsonResponse, status, xhr);
					} else if (callback == "successPostSave") {
						successPostSave(jsonResponse, status, xhr);
					} else {
						successGeneric(jsonResponse, status, xhr);
					}
				}
			});
		});
	});
</script>';

include(CORE_DIR_DEPTH.CORE_INCLUDE_DIR.'admin_header.php'); ?>

<h1><?php echo $pageTitle; ?></h1>
<?php SystemMessage::output(); ?>
<?php if ($view == 'details') { ?>
	<div class="twelvecol last">
		<div class="box">
			<div id="blogNav">
				<ul>
				<?php if ($tempBlog) {
					if ($tempBlog->type == 'photo') { ?>
						<li class="tab" id="tabPosts"><a href="javascript:void(0);" onclick="toggleBlogPanel('photos-search');">Photos</a></li>
						<li class="tab" id="tabUpload"><a href="javascript:void(0);" onclick="toggleBlogPanel('photos-upload');">Upload</a></li>
					<?php } else { ?>
						<li class="tab" id="tabPosts"><a href="javascript:void(0);" onclick="toggleBlogPanel('posts-search');">Posts</a></li>
					<?php } ?>
				<?php } ?>
					<li class="tab selected" id="tabSettings"><a href="javascript:void(0);" onclick="toggleBlogPanel('settings');">Settings</a></li>
				</ul>
			</div>
			<div id="imgSave"><a href="javascript:void(0);" class="button button-blue">Save</a></div>
			<div id="msg" class="message"><span id="msg-content"></span><a href="javascript:void(0);" onclick="hideMessage('msg');"><img src="/img/icons/cancel.png" alt="Close" /></a></div>
			<div id="blogManager"></div>
		</div>
	</div>
	<div style="display:hidden"><input type="hidden" id="blogId" value="<?php echo ($tempBlog) ? $tempBlog->id : 0; ?>" /><input type="hidden" id="postId" value="<?php echo ($post) ? $post->id : null; ?>" /></div>
<?php } else { ?>
	<div><a href="admin_blogs.php?v=details&t=add" class="button button-blue">Add New Blog</a></div><br />
	<form method="GET" action="admin_blogs.php">
	<div class="box">
		<table class="tableNav">
			<tr>
				<td width="1%" nowrap="nowrap"><strong>Total:</strong> <?php echo number_format($totalResults, 0); ?> Blogs</td>
				<td><?php $url = 'admin_blogs.php?';
					echo outputPagingLinks($url, $pageNum, $perPage, $totalResults); ?>
				</td>
				<td width="1%" nowrap="nowrap"><strong>Per Page:</strong> 
					<select name="perpage">
					<?php $i = 20;
					do { ?>
						<option value="<?php echo $i; ?>"<?php if ($perPage == $i) { ?> selected="selected"<?php } ?>><?php echo $i; ?></option>
					<?php $i += 20;
					} while ($i <= (100)); ?>
					</select>
				</td>
				<td width="1%" nowrap="nowrap"><input type="submit" value="Go" /></td>
			</tr>
		</table>
		</form>
		<?php if ($blogs) { ?>
			<table class="tableResults">
				<tr>
					<th width="1%">Edit</th>
					<th>&nbsp;</th>
					<th>Name</th>
					<th>Url</th>
					<th width="5%">Type</th>
					<th width="5%">Default</th>
					<th>Description</th>
					<th width="1%">Delete</th>
				</tr>
			<?php for ($i = 0; $i < count($blogs); $i++) {
				$allPosts = $blogs[$i]->getNumPosts(true);
				$viewPosts = $blogs[$i]->getNumPosts();
				
				$infoText = '<strong>Categories:</strong> '.$blos[$i]->categories.'<br />';
				$infoText .= '<strong># Posts:</strong> '.$viewPosts.' ('.($allPosts-$viewPosts).')<br />';
				$infoText .= '<strong>Last Post:</strong> '.$GLOBALS['dtObj']->format($blogs[$i]->getLastPostDate()).'<br />';
				
				$trClass = ($i%2==0) ? 'oddCell' : 'evenCell'; ?>
				<tr class="<?php echo $trClass; ?>">
					<td nowrap="nowrap" class="tdCenter"><a href="admin_blogs.php?v=details&t=edit&p=posts&id=<?php echo $blogs[$i]->id; ?>"><img class="tdIcon" src="/img/icons/config.png" alt="Edit" /></a></td>
					<td><?php echo Tooltip::outputInfo($infoText); ?></td>
					<td><?php echo $blogs[$i]->name; ?></td>
					<td><a href="<?php echo $blogs[$i]->fullUrl; ?>" target="_blank"><?php echo $blogs[$i]->fullUrl; ?></a></td>
					<td nowrap="nowrap"><?php echo $blogs[$i]->type; ?></td>
					<td nowrap="nowrap"><?php echo ($blogs[$i]->default) ? 'Yes' : 'No'; ?></td>
					<td><?php echo $blogs[$i]->description; ?></td>
					<td nowrap="nowrap" class="tdCenter"><a href="admin_blogs.php?v=list&t=delete&id=<?php echo $blogs[$i]->id; ?>" onclick="return confirm('This will permanently delete the blog.  Are you sure you wish to continue?');"><img class="tdIcon" src="/img/icons/cancel.png" alt="Delete" /></a></td>
				</tr>
			<?php } ?>
			</table>
		<?php } else { ?>
			<div class="<?php echo SystemMessage::getMessageClass(MSG_WARNING); ?>">No results returned.</div>
		<?php } ?>
	</div>
<?php } ?>

<?php include(CORE_DIR_DEPTH.CORE_INCLUDE_DIR.'admin_footer.php'); ?>