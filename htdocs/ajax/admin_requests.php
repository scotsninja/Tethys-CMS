<?php
/* Copyright 20xx Productions */

require_once('../src/common.php');

$action = (User::isAdmin()) ? $_POST['action'] : null;

if ($action == 'blog-save-post') {
	if (is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$tempBlog = Blog::getById($_POST['id']);
	}
	
	if ($tempBlog) {
		if (is_numeric($_POST['params']['pid']) && $_POST['params']['pid'] > 0) {
			$tempPost = BlogPost::getById($_POST['params']['pid']);
		}
		
		if ($tempPost) {
			if ($tempBlog->editPost(&$tempPost, $_POST['params']['value'], $_POST['params']['title'], $_POST['params']['tags'], $_POST['params']['comments'], $_POST['params']['excerpt'], $_POST['params']['url'], $_POST['params']['date'])) {
				$ret['success'] = 'Blog post successfully updated';
				$ret['id'] = $tempPost->id;
			} else {
				$ret = array('error' => 'Unable to save all fields');
			}
		} else if ($id = $tempBlog->addPost($_POST['params']['value'], $_POST['params']['title'], $_POST['params']['tags'], $_POST['params']['comments'], $_POST['params']['excerpt'], $_POST['params']['url'], $_POST['params']['date'])) {
			$ret['success'] = 'Blog post saved';
			$ret['id'] = $id;
		}
	} else {
		$ret['error'] = 'Invalid Blog ID';
	}
} else if ($action == 'blog-save-photos') {
	if (is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$tempBlog = Blog::getById($_POST['id']);
	}
	
	if ($tempBlog) {
		if (is_array($_POST['params']['ids']) && count($_POST['params']['ids']) > 0) {
			$total = count($_POST['params']['ids']);
			
			for ($i = 0; $i < $total; $i++) {
				$tempObj = BlogPhoto::getById($_POST['params']['ids'][$i]);
				
				if ($tempObj) {
					$pass[] = true;
					if ($tempObj->title != $_POST['params']['titles'][$i]) {
						$pass[] = $tempObj->setTitle($_POST['params']['titles'][$i]);
					}
					if ($tempObj->tags != $_POST['params']['tags'][$i]) {
						$pass[] = $tempObj->setTags($_POST['params']['tags'][$i]);
					}
					if ($tempObj->caption != $_POST['params']['captions'][$i]) {
						$pass[] = $tempObj->setCaption($_POST['params']['captions'][$i]);
					}
					if ($tempObj->datePosted != $_POST['params']['date'][$i]) {
						$pass[] = $tempObj->setDatePosted($_POST['params']['dates'][$i]);
					}
					
					if (is_array($pass) && !in_array(false, $pass)) {
						$sucArr[] = array('image' => $_POST['params']['ids'][$i], 'msg' => 'Image info saved successfully');
					} else {
						$ret = array('error' => 'Unable to save all fields');
					}
				} else {
					$errArr[] = array('image' => $_POST['images'][$i], 'msg' => 'Error saving image info');
				}
			}
			
			$ret['success'] = $sucArr;
			$ret['error'] = $errArr;
		} else {
			$ret['error'] = 'No valid images';
		}
	} else {
		$ret['error'] = 'Invalid Blog ID';
	}
} else if ($action == 'blog-post-delete') {
	if (is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$tempBlog = Blog::getById($_POST['id']);
	}
	
	if ($tempBlog) {
		if (is_numeric($_POST['post']) && $_POST['post'] > 0) {
			$tempPost = BlogPost::getById($_POST['post']);
		}

		if ($tempPost && $tempBlog->removePost($tempPost)) {
			$ret['success'] = 'Blog Post removed';
		} else {
			$ret['error'] = 'Invalid Blog Post';
		}
	} else {
		$ret['error'] = 'Invalid Blog ID';
	}
} else if ($action == 'blog-photo-delete') {
	if (is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$tempBlog = Blog::getById($_POST['id']);
	}
	
	if ($tempBlog) {
		if (is_numeric($_POST['photo']) && $_POST['photo'] > 0) {
			$tempPhoto = BlogPhoto::getById($_POST['photo']);
		}

		if ($tempPhoto && $tempBlog->removePhoto($tempPhoto)) {
			$ret['success'] = 'Blog photo removed';
		} else {
			$ret['error'] = 'Invalid Blog photo';
		}
	} else {
		$ret['error'] = 'Invalid Blog ID';
	}
} else if ($action == 'blog-save-settings') {
	if (is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$tempBlog = Blog::getById($_POST['id']);
	}
	
	$default = ($_POST['params']['default'] == 'true') ? 1 : 0;
	$active = ($_POST['params']['active'] == 'true') ? 1 : 0;
	
	if ($tempBlog) {
		$pass[] = true;
		if ($tempBlog->name != $_POST['params']['name']) {
			$pass[] = $tempBlog->setName($_POST['params']['name']);
		}
		if ($tempBlog->categories != $_POST['params']['categories']) {
			$pass[] = $tempBlog->setCategories($_POST['params']['categories']);
		}
		if ($tempBlog->description != $_POST['params']['description']) {
			$pass[] = $tempBlog->setDescription($_POST['params']['description']);
		}
		if ($tempBlog->url != $_POST['params']['url']) {
			$pass[] = $tempBlog->setUrl($_POST['params']['url']);
		}
		if ($tempBlog->default != $default) {
			$pass[] = $tempBlog->setDefault($default);
		}
		if ($tempBlog->active != $active) {
			$pass[] = $tempBlog->setActive($active);
		}
		
		if (is_array($pass) && !in_array(false, $pass)) {
			$ret = array('success' => 'Blog successfully updated');
		} else {
			$ret = array('error' => 'Unable to save all fields');
		}
	} else {
		$blogId = Blog::add($_POST['params']['name'], $_POST['params']['type'], $_POST['params']['categories'], $_POST['params']['description'], $_POST['params']['url'], $default, $active);
		
		if ($blogId) {
			$ret = array('id' => $blogId, 'name' => $_POST['params']['name'], 'success' => 'Blog successfully created');
		} else {
			$ret = array('error' => 'Error saving blog');
		}
	}
} else if ($action == 'blog-photo-add') {
	if (is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$tempBlog = Blog::getById($_POST['id']);
	}
		
	if ($tempBlog) {
		if (is_array($_POST['files']) && count($_POST['files']) > 0) {
			foreach ($_POST['files'] as $f) {
				if ($tempBlog->addPhoto($f)) {
					$sucArr[] = array('file' => $f, 'msg' => 'File transfered successfully');
				} else {
					$errArr[] = array('file' => $f, 'msg' => 'Error transferring file');
				}
				
				$ret['success'] = $sucArr;
				$ret['error'] = $errArr;
			}
		} else {
			$ret['error'] = 'No uploaded files';
		}
	} else {
		$ret['error'] = 'Invalid Blog ID';
	}
} else if ($action == 'blog-img-add') {
	if (is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$tempBlog = Blog::getById($_POST['id']);
	}
		
	if ($tempBlog) {
		if (is_array($_POST['files']) && count($_POST['files']) > 0) {
			if ($tempBlog->setImage($_POST['files'][0])) {
				$ret['success'] = 'File transfered successfully';
			} else {
				$ret['error'] = 'Error transferring file';
			}
		} else {
			$ret['error'] = 'No uploaded files';
		}
	} else {
		$ret['error'] = 'Invalid Blog ID';
	}
} else if ($action == 'page-preview-save') {
	if ($_POST['value'] != '') {
		$id = Page::saveTemp($_POST['value'], $_POST['css'], $_POST['title'], $_POST['comments'], $_POST['width'], $_POST['includePage'], $_POST['includePosition']);
		
		if ($id) {
			$ret['success'] = 'Temp page saved.';
			$ret['result'] = $id;
		} else {
			$ret['error'] = 'Error saving temporary page';
		}
	} else {
		$ret['error'] = 'Page content is null';
	}
} else {
	$ret = array('error' => 'Unknown action.');
}

if ($ret['error'] == '') {
	$ret['error'] = '';
}
if ($ret['success'] == '') {
	$ret['success'] = '';
}

echo json_encode($ret);

?>