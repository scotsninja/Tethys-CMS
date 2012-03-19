<?php
/* Copyright 20xx Productions */

require_once('../src/common.php');

$action = (User::isAdmin()) ? $_POST['action'] : null;

if ($action == 'blog-posts-search') {
	if (is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$tempBlog = Blog::getById($_POST['id']);
	}
	
	if ($tempBlog) {
		// build search array
		$search = array('future' => true);
		
		$perPage = (is_numeric($_POST['params']['perpage']) && $_POST['params']['perpage'] > 0) ? $_POST['params']['perpage'] : 20;
		$pageNum = (is_numeric($_POST['params']['page']) && $_POST['params']['page'] > 0) ? $_POST['params']['page'] : 1;
		$sort = ($_POST['params']['sort'] != '') ? $_POST['params']['sort'] : 'bpDatePosted DESC';
		
		$posts = $tempBlog->getPosts($search, $perPage, $pageNum, $totalResults, $sort);
		
		$ret = '<div><a href="javascript:void(0);" onclick="loadPanel(\'posts-details\');" class="button button-blue">Add New Post</a></div><br />';
	
		// nav table
		$ret .= '<table class="tableNav">
			<tr>
				<td width="1%" nowrap="nowrap"><strong>Total:</strong> '.number_format($totalResults, 0).' Posts</td>
				<td>'. outputAjaxPagingLinks('refreshResults', $pageNum, $perPage, $totalResults).'</td>
				<td width="1%" nowrap="nowrap"><strong>Per Page:</strong> 
					<select name="perpage" id="perpage">';
					
					$i = 10;
					do {
						$ret .= '<option value="'. $i .'"';
						if ($perPage == $i) { $ret .= ' selected="selected"'; }
						$ret .= '>'.$i.'</option>';
						
						$i += 10;
					} while ($i <= (100));
					
					$ret .= '</select>
				</td>
				<td width="1%" nowrap="nowrap"><input type="submit" value="Go" onclick="refreshResults();" /></td>
			</tr>
		</table>';
		
		if ($posts) {
			$ret .= '<table class="tableResults">
				<tr>
					<th width="1%">Edit</th>
					<th width="1%">Date</th>
					<th>Title</th>
					<th>Url</th>
					<th>Tags</th>
					<th width="1%">Comments</th>
					<th width="1%">Delete</th>
				</tr>';
			
			for ($i = 0; $i < count($posts); $i++) {
				$trClass = ($i%2==0) ? 'oddCell' : 'evenCell';
				
				$postDt = new DateTime($posts[$i]->datePosted, new DateTimeZone(DATE_DEFAULT_TIMEZONE));
				
				$ret .= '<tr class="'. $trClass.'">
					<td nowrap="nowrap" class="tdCenter"><a href="javascript:void(0);" onclick="loadPost('.$posts[$i]->id.');"><img class="tdIcon" src="/img/icons/config.png" alt="Edit" /></a></td>
					<td>'.$postDt->format('m/d/Y <b\r />H:i e').'</td>
					<td>'.$posts[$i]->title.'</td>
					<td><a href="'.$posts[$i]->fullUrl.'" target="_blank">'.(($posts[$i]->url != '') ? $posts[$i]->url : 'View').'</a></td>
					<td>'.$posts[$i]->tags.'</td>
					<td>'.(($posts[$i]->comments == 'open') ? 'Yes' : 'No').'</td>
					<td nowrap="nowrap" class="tdCenter"><a href="javascript:void(0);" onclick="if (confirm(\'This will permanently delete the blog.  Are you sure you wish to continue?\')) { deletePost('.$posts[$i]->id.'); }"><img class="tdIcon" src="/img/icons/cancel.png" alt="Delete" /></a></td>
				</tr>';
			}
			
			$ret .= '</table>';
		} else {
			$ret .= '<div class="'. SystemMessage::getMessageClass(MSG_WARNING).'">No results returned.</div>';
		}
	} else {
		$ret .= '<div class="'.SystemMessage::getMessageClass(MSG_ERROR).'">Invalid Blog Id</div>';
	}
	
	$ret .= '<div style="display:none;"><input type="hidden" id="blogPanel" value="posts-search" /></div>';
} else if ($action == 'blog-photos-search') {
	if (is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$tempBlog = Blog::getById($_POST['id']);
	}
		
	if ($tempBlog) {
		$perRow = 3;
		
		$photos = $tempBlog->getPhotos(array('future' => true), ($perRow*5), 1, $totalImages);
	
		$numImages = count($photos);
		$numRows = ceil(($numImages/$perRow));
		
		if (is_array($photos)) {
			$ret .= '<div id="imgGallery"><ul class="sortable">';

			for ($i = 0; $i < $numImages; $i++) {
				$tdClass = ($i%2==0) ? 'evenCell' : 'oddCell';

				$ret .= '<li class="'. $tdClass .'" style="width:30%;">
					<div style="position:relative;">
						<div><div class="imgWrapper imgWrapper150"><img src="'. $photos[$i]->imagePath .'" alt="'. $photos[$i]->title .'" /></div></div>
						<div style="position:absolute;top:0px;right:0px;"><a href="javascript:void(0);" onclick="if (confirm(\'Are you sure you wish to delete this image?\')) { deletePhoto('.$photos[$i]->id.');}"><img src="/img/icons/cancel.png" width="20" alt="Delete Image" /></a></div>
						<div><table width="100%">
							<tr>
								<td class="tdHeader" width="1%" nowrap="nowrap">Title</td>
								<td><input type="text" class="imgTitle" size="28" value="'. $photos[$i]->title .'" />
							</tr>
							</tr>
								<td class="tdHeader" nowrap="nowrap">Caption</td>
								<td><textarea class="imgCaption" rows="3" cols="22">'. $photos[$i]->caption .'</textarea></td>
							</tr>
							<tr>
								<td class="tdHeader" nowrap="nowrap">Tags</td>
								<td><input type="text" class="imgTags" size="28" value="'. $photos[$i]->tags .'" />
							</tr>
							<tr>
								<td class="tdHeader" nowrap="nowrap">Date</td>
								<td><input type="text" class="imgDate" size="28" value="'. $photos[$i]->datePosted .'" />
							</tr>
						</table>
						<input type="hidden" class="imgId" value="'.$photos[$i]->id.'" />
						</div>
					</div>
				</li>';
			}
			
			$ret .= '</ul></div>';
			
			$ret .= '<script type="text/javascript">
				$(function() {
					$(".imgDate").datetimepicker({
						showButtonPanel: true,
						dateFormat: "yy-mm-dd",
						changeMonth: true,
						changeYear: true,
						maxDate: "+10y",
						showOtherMonths: true,
						timeFormat: "hh:mm:ss"
					});
				});
			</script>';
		} else {
			$error = array('type' => MSG_WARNING, 'message' => 'No images uploaded');
		}
	} else {
		$error = array('type' => MSG_ERROR, 'message' => 'Invalid Blog Id');
	}
	
	$ret .= '<div style="display:none;"><input type="hidden" id="blogPanel" value="photos-search" /></div>';
} else if ($action == 'blog-photos-upload') {
	if (is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$tempBlog = Blog::getById($_POST['id']);
	}
	
	if ($tempBlog) {
		$ret .= '<div id="uploader">
			<div id="filelist">No runtime found.</div>
			<br />
			<a id="pickfiles" href="javascript:;">[Select files]</a> 
			<a id="uploadfiles" href="javascript:;">[Upload files]</a>
		</div>';
	} else {
		$ret .= '<div class="'.SystemMessage::getMessageClass(MSG_ERROR).'">Invalid Blog Id</div>';
	}
	
	$ret .= '<div style="display:none;"><input type="hidden" id="blogPanel" value="photos-upload" /></div>';
} else if ($action == 'blog-posts-details') {
	if (is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$tempBlog = Blog::getById($_POST['id']);
	}
	
	if (is_numeric($_POST['pid']) && $_POST['pid'] > 0) {
		$tempPost = $tempBlog->getPostById($_POST['pid']);
	}
	
	if ($tempPost) {
		$title = $tempPost->title;
		$excerpt = $tempPost->excerpt;
		$tags = $tempPost->tags;
		$comments = $tempPost->comments;
		$url = $tempPost->url;
		$dtPost = $tempPost->datePosted;
		$value = $tempPost->value;
	} else {
		$title = null;
		$excerpt = null;
		$tags = null;
		$comments = 'open';
		$url = null;
		$dtPost = null;
		$value = null;
	}

	$ret = '<table class="tableForm">
		<tr>
			<td class="tdHeader" width="10%">Title:</td>
			<td><input type="text" name="txtTitle" id="txtTitle" size="50" value="'. $title .'" /></td>
			<td rowspan="5" width="35%">';
			if ($tempPost) {
				$ret .= '<img src="'.$tempPost->imagePath.'" alt="" style="width:100%;" />';
				
				if (false) {
					$ret .= '<div><em>Delete<em> <input type="checkbox" name="chkDelete" value="1" /></div>';
				}
			}
			$ret .= '&nbsp;</td>
		</tr>
		<tr>
			<td class="tdHeader">Tags:</td>
			<td><input type="text" name="txtTags" id="txtTags" size="60" value="'. $tags .'" /></td>
		</tr>
		<tr>
			<td class="tdHeader">URL:</td>
			<td><input type="text" name="txtUrl" id="txtUrl" size="40" value="'. $url.'" /> '. Tooltip::outputInfo('Enter a friendly url to access the blog posts from blogs/blog_url/url') .'</td>
		</tr>
		<tr>
			<td class="tdHeader">Post Date:</td>
			<td><input type="text" id="dtPost" name="dtPost" value="'.$dtPost.'" /> '. Tooltip::outputInfo('Choose the posting date for this post.').'</td>
		</tr>
		<tr>
			<td class="tdHeader">Comments:</td>
			<td><select name="selComments" id="selComments">
					<option value="">- Select -</option>
					<option value="closed"';
					if ($comments == 'closed') { $ret .= ' selected="selected"'; }
					$ret .= '>Off</option>
					<option value="open"';
					if ($comments == 'open') { $ret .= ' selected="selected"'; }
					$ret .= '>On</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="tdHeader">Excerpt:</td>
			<td><textarea name="txtExcerpt" id="txtExcerpt" rows="4" cols="50">'. $excerpt .'</textarea> '. Tooltip::outputInfo('A brief summary of the post\'s content.  If left blank, defaults to the first 200 characters of the post.') .'</td>
		</tr>
		<tr>
			<td colspan="3"><div>'. outputCkEditor('txtValue', $value, 'Full').'</div></td>
		</tr>
	</div>
	</table>';
	
	$ret .= '<script type="text/javascript">
		$(function() {
			$("#dtPost").datetimepicker({
				showButtonPanel: true,
				dateFormat: "yy-mm-dd",
				changeMonth: true,
				changeYear: true,
				maxDate: "+10y",
				showOtherMonths: true,
				timeFormat: "hh:mm:ss"
			});
			
			$("textarea.ckeditor").ckeditor(function() { },
				{toolbar: "Full"
			});
		});
	</script>';
	
	$ret .= '<div style="display:none;"><input type="hidden" id="blogPanel" value="posts-details" /></div>';
} else if ($action == 'blog-settings') {
	if (is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$tempBlog = Blog::getById($_POST['id']);
	}
	
	if ($tempBlog) {
		$name = $tempBlog->name;
		$type = $tempBlog->type;
		$categories = $tempBlog->categories;
		$description = $tempBlog->description;
		$url = $tempBlog->url;
		$default = $tempBlog->default;
		$active = $tempBlog->active;
	} else {
		$name = null;
		$type = 'post';
		$categories = null;
		$description = null;
		$url = null;
		$default = 0;
		$active = 1;
	}
	
	$ret = '<table class="tableForm">
		<tr>
			<td class="tdHeader" width="10%">Name:</td>
			<td><input type="text" name="txtName" id="txtName" size="50" value="'. $name .'" required="required" /></td>
			<td rowspan="8" width="35%">';
			if ($tempBlog) {
				$ret .= '<img src="'.$tempBlog->imagePath.'" alt="" style="width:100%;" />';
			}
			$ret .= '</td>
		</tr>
		<tr>
			<td class="tdHeader">Type:</td>
			<td><select name="selType" id="selType">
					<option value="">- Select -</option>
					<option value="photo"';
					if ($type == 'photo') { $ret .= ' selected="selected"'; }
					$ret .= '>Photo</option>
					<option value="post"';
					if ($type == 'post') { $ret .= ' selected="selected"'; }
					$ret .= '>Post</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="tdHeader">Categories:</td>
			<td><input type="text" name="txtCategories" id="txtCategories" size="60" value="'. $categories .'" /></td>
		</tr>
		<tr>
			<td class="tdHeader">Description:</td>
			<td><textarea name="txtDescription" id="txtDescription" rows="8" cols="50">'. $description .'</textarea></td>
		</tr>
		<tr>
			<td class="tdHeader">URL:</td>
			<td><input type="text" name="txtUrl" id="txtUrl" size="40" value="'. $url.'" /> '. Tooltip::outputInfo('Enter a friendly url to access the blog from blogs/url') .'</td>
		</tr>
		<tr>
			<td class="tdHeader">Default:</td>
			<td><input type="checkbox" name="chkDefault" id="chkDefault" value="1"';
			if ($default) { $ret .= ' checked="checked"'; }
			$ret .= '/> '. Tooltip::outputInfo('Check this box to make this the primary site blog.').'</td>
		</tr>
		<tr>
			<td class="tdHeader">Active:</td>
			<td><input type="checkbox" name="chkActive" id="chkActive" value="1"';
			if ($active) { $ret .= ' checked="checked"'; }
			$ret .= '/> '. Tooltip::outputInfo('Check this box so anyone can view the blog.').'</td>
		</tr>
	</table>';
	
	if ($tempBlog) {
		$ret .= '<br />';
		$ret .= '<h2>Blog Icon</h2>';
		$ret .= '<div id="uploader">
			<div id="filelist">No runtime found.</div>
			<br />
			<a id="pickfiles" href="javascript:;">[Select files]</a> 
			<a id="uploadfiles" href="javascript:;">[Upload files]</a>
		</div>';
	}
	
	$ret .= '<div style="display:none;"><input type="hidden" id="blogPanel" value="settings" /></div>';
} else if ($action == 'page-preview-view') {
	$ret = '<iframe src="../base-page.php?url=temp-page&id='.$_POST['id'].'" scrolling="auto" style="width:100%;height:100%;"><p>Your browser does not support iframes.</p></iframe>';
} else {
	$error = array('type' => MSG_ERROR, 'message' => 'Unknown action');
}

if ($error) {
	echo '<div class="'.SystemMessage::getMessageClass($error['type']).'">'.$error['message'].'</div>';
} else {
	echo $ret;
}

?>