<?php
/* Copyright 20xx Productions */

require_once('../src/common.php');

User::requireLogin('admin', '../login.php', 'You do not have permission to view that page.');

// do page processing

include(CORE_DIR_DEPTH.CORE_INCLUDE_DIR.'admin_header.php');

SystemMessage::output();

// page output

include(CORE_DIR_DEPTH.CORE_INCLUDE_DIR.'admin_footer.php');
?>