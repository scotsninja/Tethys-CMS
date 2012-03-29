<?php
/* Copyright 20xx Productions */

require_once('../src/common.php');

User::requireLogin('admin', '../login.php', 'You do not have permission to view that page.');

$view = ($_GET['v'] == 'details') ? 'details' : 'list';
$pageTitle = ($view == 'details') ? 'Page Manage' : 'Page Search';

// if viewing page details
if ($view == 'details') {
	if (is_numeric($_GET['id'])) {
		$tempPage = Page::getById($_GET['id']);
	}
	
	// setup variables
	if (isset($_POST['hidSubmit'])) {
		$name = $_POST['txtName'];
		$value = $_POST['txtValue'];
		$css = $_POST['txtCSS'];
		$title = $_POST['txtTitle'];
		$description = $_POST['txtDescription'];
		$keywords = $_POST['txtKeywords'];
		$author = $_POST['txtAuthor'];
		$url = $_POST['txtUrl'];
		$file = $_POST['txtFile'];
		$width = $_POST['selWidth'];
		$userLevel = $_POST['selUserLevel'];
		$comments = $_POST['selComments'];
		$protected = ($_POST['chkProtected']) ?  1 : 0;
		$includePage = $_POST['selIncludePage'];
		$includePosition = $_POST['selIncludePosition'];
		$active = ($_POST['chkActive']) ?  1 : 0;
	} else if ($tempPage) {
		$name = $tempPage->name;
		$value = $tempPage->value;
		$css = $tempPage->css;
		$title = $tempPage->title;
		$description = $tempPage->description;
		$keywords = $tempPage->keywords;
		$author = $tempPage->author;
		$url = $tempPage->url;
		$file = $tempPage->file;
		$width = $tempPage->width;
		$userLevel = $tempPage->userLevel;
		$comments = $tempPage->comments;
		$includePage = $tempPage->includePage;
		$includePosition = $tempPage->includePosition;
		$protected = $tempPage->protected;
		$active = $tempPage->active;
	} else {
		$name = null;
		$value = null;
		$css = null;
		$title = null;
		$description = null;
		$keywords = null;
		$author = null;
		$url = null;
		$file = null;
		$width = 12;
		$userLevel = 'none';
		$comments = 'none';
		$protected = 0;
		$includePage = null;
		$includePosition = null;
		$active = 1;
	}
	
	// if the form was posted
	if (isset($_POST['hidSubmit'])) {
		if ($tempPage) {
			$pass[] = true;
			
			if ($tempPage->name != $name) {
				$pass[] = $tempPage->setName($name);
			}
			if ($tempPage->width != $width) {
				$pass[] = $tempPage->setWidth($width);
			}
			if ($tempPage->userLevel != $userLevel) {
				$pass[] = $tempPage->setUserLevel($userLevel);
			}
			if ($tempPage->comments != $comments) {
				$pass[] = $tempPage->setComments($comments);
			}
			if ($tempPage->protected != $protected) {
				$pass[] = $tempPage->setProtected($protected);
			}
			if ($tempPage->value != $value) {
				$pass[] = $tempPage->setValue($value);
			}
			if ($tempPage->css != $css) {
				$pass[] = $tempPage->setCSS($css);
			}
			if ($tempPage->title != $title) {
				$pass[] = $tempPage->setTitle($title);
			}
			if ($tempPage->author != $author) {
				$pass[] = $tempPage->setAuthor($author);
			}
			if ($tempPage->keywords != $keywords) {
				$pass[] = $tempPage->setKeywords($keywords);
			}
			if ($tempPage->description != $description) {
				$pass[] = $tempPage->setDescription($description);
			}
			if ($tempPage->url != $url) {
				$pass[] = $tempPage->setUrl($url);
			}
			if ($tempPage->includePage != $includePage) {
				$pass[] = $tempPage->setIncludePage($includePage);
			}
			if ($tempPage->includePosition != $includePosition) {
				$pass[] = $tempPage->setIncludePosition($includePosition);
			}
			if ($tempPage->active != $active) {
				$pass[] = $tempPage->setActive($active);
			}
			
			if (is_array($pass) && !in_array(false, $pass)) {
				$tempPage->refresh();
				SystemMessage::save(MSG_SUCCESS, 'Page successfully updated.');
				$loc  = 'admin_pages.php?v=list';
			} else {
				SystemMessage::save(MSG_WARNING, 'Unable to save all fields.');
				$loc = 'admin_pages.php?v=details&id='.$tempPage->id;
			}

			header('Location: ' . $loc);
			exit();
		} else {
			if (Page::add($name, $value, $css, $title, $description, $keywords, $author, $url, $file, $protected, $userLevel, $comments, $width, $includePage, $includePosition, $active)) {
				SystemMessage::save(MSG_SUCCESS, 'Page successfully saved.');
				$loc = 'admin_pages.php?v=list';
				
				header('Location: '.$loc);
				exit();
			} else {
				SystemMessage::save(MSG_ERROR, 'Error saving page.');
			}
		}
	}
	
	$includes['css'] = '<style type="text/css">
		#previewWindow {
			background-color:#ccc;
			position:absolute;
			top:100px;
			left:200px;
			height:700px;
			width:1100px;
			padding:10px 20px;
			display:none;
			z-index:9001;
		}
		
		#previewWindow div.container {
			overflow:auto;
			height:96%;
		}
		
		#previewWindow a {
			position:absolute;
			top:-8px;
			right:-8px;
		}
	</style>';
	
	$includes['js'] = '<script type="text/javascript" src="'.CORE_DIR_DEPTH.'ckeditor/ckeditor.js"></script>';
	$includes['js'] .= '<script type="text/javascript">
		$(document).ready(function() {
			$("#previewBtn").show();
			
			$("#previewBtn").click(function(e) {
				savePreview();
			});
		});
		
		function savePreview() {
			var url = "/ajax/admin_requests.php";
			var dataArr;
			
			dataArr = {
				"action": "page-preview-save",
				"title": $("#txtTitle").val(), 
				"value": CKEDITOR.instances["ck_txtValue"].getData(),
				"css": $("#txtCSS").val(), 
				"comments": $("#selComments").val(),
				"width": $("#selWidth").val(),
				"includePage": $("#selIncludePage").val(),
				"includePosition": $("#selIncludePosition").val()
			};
			
			$.ajax({
				url: url,
				cache: false,
				data: dataArr,
				type: "POST",
				error: function(xhr, status, error) {
					alert("Error generating preview");
				},
				success: function(data, status, xhr) {
					var jsonResponse = JSON.parse(data);
					
					if (jsonResponse.error == "" || jsonResponse.error == null) {
						loadPreview(jsonResponse.result);
					} else {
						alert(jsonResponse.error);
					}
				}
			});
		}
		
		function loadPreview(id) {
			if (id < 1) {
				return false;
			}
			
			// display form
			openMask();
			centerWindow("#previewWindow");
			
			$("#previewWindow").fadeIn();
			
			var url = "/ajax/admin_output.php";
			var dataArr;
			
			dataArr = {
				"action": "page-preview-view",
				"id": id
			};
			
			$.ajax({
				url: url,
				cache: false,
				data: dataArr,
				type: "POST",
				error: function(xhr, status, error) {
					alert("Error generating preview");
				},
				success: function(data, status, xhr) {
					$("#previewWindow div.container").html(data);
				}
			});
		}
		
		function closePreview() {
			$("#previewWindow").fadeOut();
			closeMask();
		}
	</script>';
	
	$pageList = Page::getList();
} // else, view list of pages
else {
	if ($_GET['t'] == 'delete') {
		$del = Page::getById($_GET['id']);
		
		if ($del) {
			if ($del->delete()) {
				SystemMessage::save(MSG_SUCCESS, 'Page successfully deleted.');
			} else {
				SystemMessage::save(MSG_ERROR, 'Error deleting page.');
			}
		} else {
			SystemMessage::save(MSG_WARNING, 'Could not delete page because it does not exist.');
		}
		
		header('Location: admin_pages.php?v=list');
		exit();
	}
	
	// build search array
	$search = array();
	
	$perPage = (is_numeric($_GET['perpage']) && $_GET['perpage'] > 0) ? $_GET['perpage'] : 20;
	$pageNum = (is_numeric($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
	$sort = ($_GET['sort'] != '') ? $_GET['sort'] : 'pageName ASC';
	
	$pages = Page::search($search, $perPage, $pageNum, $totalResults, $sort);
}

include(CORE_DIR_DEPTH.CORE_INCLUDE_DIR.'admin_header.php'); ?>

<h1><?php echo $pageTitle; ?></h1>
<?php SystemMessage::output(); ?>
<?php if ($view == 'details') { ?>
	<form method="POST" action="admin_pages.php?v=details<?php if ($tempPage) { echo '&id='.$tempPage->id; } ?>">
	<div class="sixcol">
		<div class="box">
			<div class="heading">Page Attributes</div>
			<table class="tableForm">
				<tr>
					<td class="tdHeader" width="20%">Name:</td>
					<td><input type="text" name="txtName" id="txtName" size="40" value="<?php echo $name; ?>" required="required" /><?php SystemMessage::output('name'); ?></td>
				</tr>
				<tr>
					<td class="tdHeader">Width:</td>
					<td><select name="selWidth" id="selWidth">
							<option value="">- Select -</option>
						<?php for ($i = 1; $i < 13; $i++) { ?>
							<option value="<?php echo $i; ?>"<?php if ($width == $i) { ?> selected="selected"<?php } ?>><?php echo $i; ?> column(s)</option>
						<?php } ?>
						</select>
						<?php echo Tooltip::outputInfo('The width (in columns) that the page will take up of the website.  The total width is 1140px, divided into 12 columns of 95px each.'); ?>
						<?php SystemMessage::output('width'); ?>
					</td>
				</tr>
				<tr>
					<td class="tdHeader">User Level:</td>
					<td><select name="selUserLevel" id="selUserLevel">
							<option value="">- Select -</option>
							<option value="none"<?php if ($userLevel == 'none') { ?> selected="selected"<?php } ?>>None</option>
							<option value="user"<?php if ($userLevel == 'user') { ?> selected="selected"<?php } ?>>User</option>
							<option value="admin"<?php if ($userLevel == 'admin') { ?> selected="selected"<?php } ?>>Admin</option>
						</select>
						<?php echo Tooltip::outputInfo('The minimum user level required to view the page'); ?>
						<?php SystemMessage::output('user_level'); ?>
					</td>
				</tr>
				<tr>
					<td class="tdHeader">Comments:</td>
					<td><select name="selComments" id="selComments">
							<option value="">- Select -</option>
							<option value="none"<?php if ($comments == 'none') { ?> selected="selected"<?php } ?>>None</option>
							<option value="bottom"<?php if ($comments == 'bottom') { ?> selected="selected"<?php } ?>>Bottom</option>
							<option value="left"<?php if ($comments == 'left') { ?> selected="selected"<?php } ?>>Left</option>
							<option value="right"<?php if ($comments == 'right') { ?> selected="selected"<?php } ?>>Right</option>
						</select>
						<?php echo Tooltip::outputInfo('Enable a comment box/feed on the page, and where it is positioned relative to the main content.'); ?>
						<?php SystemMessage::output('comments'); ?>
					</td>
				</tr>
				<tr>
					<td class="tdHeader">Protected:</td>
					<td><input type="checkbox" name="chkProtected" id="chkProtected" value="1"<?php if ($GLOBALS['userObj']->level != 'super-admin') { ?> disabled="disabled"<?php } ?><?php if ($protected) { ?> checked="checked"<?php } ?> /> <?php echo Tooltip::outputInfo('If this box is checked, the only super-admins can edit this page.'); ?><?php SystemMessage::output('protected'); ?></td>
				</tr>
				<tr>
					<td class="tdHeader">Active:</td>
					<td><input type="checkbox" name="chkActive" id="chkActive" value="1"<?php if ($active) { ?> checked="checked"<?php } ?> /> <?php echo Tooltip::outputInfo('Check this box so anyone can view the page.'); ?><?php SystemMessage::output('active'); ?></td>
				</tr>
			<?php if ($pageList) { ?>
				<tr>
					<td class="tdHeader">Include:</td>
					<td><select name="selIncludePage" id="selIncludePage">
							<option value="">- None -</option>
						<?php foreach ($pageList as $page) {
							if (!$tempPage || ($tempPage && $tempPage->id != $page['id'])) { ?>
								<option value="<?php echo $page['id']; ?>"<?php if ($includePage == $page['id']) { ?> selected="selected"<?php } ?>><?php echo $page['name']; ?></option>
							<?php } ?>
						<?php } ?>
						</select>
						<?php echo Tooltip::outputInfo('To include another page as a sidebar, select it from the list here.'); ?>
						<?php SystemMessage::output('include_page'); ?>
					</td>
				</tr>
				<tr>
					<td class="tdHeader" width="20%">Position:</td>
					<td><select name="selIncludePosition" id="selIncludePosition">
							<option value="">- Select -</option>
							<option value="left"<?php if ($includePosition == 'left') { ?> selected="selected"<?php } ?>>Left</option>
							<option value="right"<?php if ($includePosition == 'right') { ?> selected="selected"<?php } ?>>Right</option>
						</select>
						<?php echo Tooltip::outputInfo('Then, select the position of the sidebar relative to this page.'); ?>
						<?php SystemMessage::output('include_position'); ?>
					</td>
				</tr>
			<?php } ?>
				<tr>
					<td colspan="2">
						<div style="text-align:right">
							<span id="previewBtn" style="display:none;"><a href="javascript:void(0);" class="button button-blue">Preview</a></span>
							<input type="submit" value="Submit" name="btnSubmit" />
						</div>
					</td>
				</tr>
			</table>
		</div>
	</div>
	<div class="sixcol last">
		<div class="box">
			<div class="heading">SEO</div>
			<table class="tableForm">
				<tr>
					<td class="tdHeader" width="20%">Title:</td>
					<td><input type="text" name="txtTitle" id="txtTitle" size="40" value="<?php echo $title; ?>" /> <?php echo Tooltip::outputInfo('The page title: displayed in the browser bar/tab.'); ?><?php SystemMessage::output('title'); ?></td>
				</tr>
				<tr>
					<td class="tdHeader">Keywords:</td>
					<td><input type="text" name="txtKeywords" id="txtKeywords" size="40" value="<?php echo $keywords; ?>" /> <?php echo Tooltip::outputInfo('Keywords used by search engines for indexing a page'); ?><?php SystemMessage::output('keywords'); ?></td>
				</tr>
				<tr>
					<td class="tdHeader">Author:</td>
					<td><input type="text" name="txtAuthor" id="txtAuthor" size="40" value="<?php echo $author; ?>" /> <?php echo Tooltip::outputInfo('Author of this page.'); ?><?php SystemMessage::output('author'); ?></td>
				</tr>
				<tr>
					<td class="tdHeader">Description:<br /><?php echo Tooltip::outputInfo('Short description used by search engines for indexing and displayed in search results.'); ?></td>
					<td><textarea name="txtDescription" id="txtDescription" rows="8" cols="35"><?php echo $description; ?></textarea><?php SystemMessage::output('description'); ?></td>
				</tr>
				<tr>
					<td class="tdHeader">Url:</td>
					<td><input type="text" name="txtUrl" id="txtUrl" size="40" value="<?php echo $url; ?>" /> <?php echo Tooltip::outputInfo('Enter the desired url for this page.'); ?><?php SystemMessage::output('url'); ?></td>
				</tr>
				<tr>
					<td colspan="2"><div style="text-align:right"><input type="submit" value="Submit" name="btnSubmit" /></div></td>
				</tr>
			</table>
		</div>
	</div>
	<div class="twelvecol last">
		<div class="box">
			<div><?php echo outputCkEditor('txtValue', $value, 'Full'); ?><?php SystemMessage::output('value'); ?></div>
			<div style="text-align:right"><input type="submit" value="Submit" name="btnSubmit" /></div>
		</div>
	</div>
	<div class="twelvecol last">
		<div class="box">
			<div class="heading">CSS <?php echo Tooltip::outputInfo('Any additional CSS for the page.'); ?></div>
			<div><textarea name="txtCSS" id="txtCSS" rows="8" cols="100"><?php echo $css; ?></textarea><?php SystemMessage::output('css'); ?></div>
		</div>
	</div>
	<input type="hidden" name="hidSubmit" value="1" />
	</form>
	<div id="previewWindow">
		<a href="javascript:void(0);" onclick="closePreview();"><img class="tdIcon" src="/img/icons/cancel.png" alt="Delete" /></a>
		<div class="container"></div>
	</div>
<?php } else { ?>
	<div><a href="admin_pages.php?v=details&t=add" class="button button-blue">Add New Page</a></div><br />
	<form method="GET" action="admin_pages.php">
	<div class="box">
		<table class="tableNav">
			<tr>
				<td width="1%" nowrap="nowrap"><strong>Total:</strong> <?php echo number_format($totalResults, 0); ?> Pages</td>
				<td><?php $url = 'admin_pages.php?';
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
		<?php if ($pages) { ?>
			<table class="tableResults">
				<tr>
					<th width="1%">Edit</th>
					<th width="1%">&nbsp;</th>
					<th>Name</th>
					<th>URL</th>
					<th width="1%" nowrap="nowrap">Includes</th>
					<th width="1%" nowrap="nowrap">User Level</th>
					<th width="1%">Comments</th>
					<th width="1%">Protected</th>
					<th width="1%">Delete</th>
				</tr>
			<?php $i = 0;
			foreach ($pages as $p) {
				$infoText = '<strong>Title:</strong> '.$p->title.'<br />';
				$infoText .= '<strong>Keywords:</strong> '.$p->keywords.'<br />';
				$infoText .= '<strong>Description:</strong> '.$p->description.'<br />';
				$infoText .= '<strong>Author:</strong> '.$p->author.'<br />';
				$infoText .= '<strong>Width:</strong> '.$p->width.' cols<br />';
				
				if ($p->includePage) {
					$infoText .= '<strong>Include:</strong> '.Page::getNameById($p->includePage);
				}
				$class = ($i++%2==1) ? 'evenRow' : 'oddRow'; ?>
				<tr class="<?php echo $class; ?>">
					<td nowrap="nowrap" class="tdCenter">
					<?php if ($p->canEdit('setName')) { ?>
						<a href="admin_pages.php?v=details&t=edit&id=<?php echo $p->id; ?>"><img class="tdIcon" src="/img/icons/config.png" alt="Edit" /></a>
					<?php } else { ?>
						&nbsp;
					<?php } ?></td>
					<td><?php echo Tooltip::outputInfo($infoText); ?></td>
					<td><?php echo $p->name; ?></td>
					<td><?php if ($p->url != '') { ?><a href="/<?php echo $p->url; ?>" target="_blank"><?php echo $p->url; ?></a><?php } ?>&nbsp;</td>
					<td nowrap="nowrap"><?php echo ($p->includePage) ? Page::getNameById($p->includePage) : '&nbsp;'; ?></td>
					<td><?php echo $p->userLevel; ?></td>
					<td><?php echo $p->comments; ?></td>
					<td><?php echo ($p->protected) ? 'Yes' : 'No'; ?></td>
					<td nowrap="nowrap" class="tdCenter">
					<?php if ($p->canEdit('delete')) { ?>
						<a href="admin_pages.php?v=list&t=delete&id=<?php echo $p->id; ?>" onclick="return confirm('This will permanently delete the page.  Are you sure you wish to continue?');"><img class="tdIcon" src="/img/icons/cancel.png" alt="Delete" /></a>
					<?php } else { ?>
						&nbsp;
					<?php } ?></td>
				</tr>
			<?php } ?>
			</table>
		<?php } else { ?>
			<div class="<?php echo SystemMessage::getMessageClass(MSG_WARNING); ?>">No results returned.</div>
		<?php } ?>
	</div>
<?php } ?>

<?php include(CORE_DIR_DEPTH.CORE_INCLUDE_DIR.'admin_footer.php'); ?>