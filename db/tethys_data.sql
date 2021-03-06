--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`settingCategory`, `settingName`, `settingDescription`, `settingType`, `settingValue`, `settingDefault`) VALUES
('Core', 'Benchmark Level', 'Set to 0 to disable logging.  1 to enable simple logging.  And, 2 to enable verbose logging.', 'integer', '1', '0'),
('Core', 'CSS Dir', '', 'path', 'css/', 'css/'),
('Core', 'Development', 'Set to true if the site is in development mode', 'boolean', 'true', 'false'),
('Core', 'Domain', NULL, 'string', NULL, NULL),
('Core', 'Error Log', '', 'path', 'src/error.log', 'src/error.log'),
('Core', 'Icon Path', NULL, 'path', 'img/icons/', 'img/icons/'),
('Core', 'Include Dir', '', 'path', 'src/includes/', 'src/includes/'),
('Core', 'JS Dir', '', 'path', 'js/', 'js/'),
('Core', 'Max Upload Size', NULL, 'integer', '33554432', '33554432 '),
('Core', 'Require Access Key', 'Flag to require a valid access key to register for the site', 'boolean', 'true', 'false'),
('Core', 'RSS Dir', '', 'path', 'rss/', 'rss/'),
('Core', 'Template Dir', '', 'path', 'src/templates/', 'src/templates/'),
('Core', 'Version', 'Release version of the Tethys CMS', 'string', '0.9.8', '0.8.0'),
('Core', 'Webmaster', 'Address to notify when fatal error occurs', 'email', NULL, 'kyle.k@20xxproductions.com'),
('Date', 'Default Timezone', 'Timezone to convert all stored dates to', 'string', 'UTC', 'UTC'),
('Date', 'Display Format Date', 'Format for date strings', 'string', 'M j, Y', 'M j, Y'),
('Date', 'Display Format Time', 'Format for time strings', 'string', 'h:i A', 'h:i A'),
('Date', 'Display Format Datetime', 'Format for date and time strings', 'string', 'M j, Y \a\t H:i e', 'M j, Y \a\t H:i e'),
('Date', 'SQL Format', 'Format for datetimes stored in database', 'string', 'Y-m-d H:i:s', 'Y-m-d H:i:s'),
('Disqus', 'Shortname', '', 'string', '', ''),
('Google', 'Analytics Id', NULL , 'string', NULL, NULL),
('Google', 'Analytics Ignore List', 'A comma-separated list of IPs to not track in google analytics', 'string', NULL, NULL),
('Fb', 'App Id', 'This is the application ID provided by facebook', 'integer', '', ''),
('Fb', 'App Secret', 'This is the application secret provided by facebook', 'string', '', ''),
('Msg', 'Debug', 'The error code value of debug notifications', 'integer', '999', '999'),
('Msg', 'Error', 'The error code value of error notifications', 'integer', '2', '2'),
('Msg', 'Fatal', 'The error code value of fatal notifications', 'integer', '3', '3'),
('Msg', 'Success', 'The error code value of success notifications', 'integer', '0', '0'),
('Msg', 'Warning', 'The error code value of warning notifications', 'integer', '1', '1'),
('Site', 'Author', 'Sets the relevant meta-tag in the &lt;head>', 'string', '', ''),
('Site', 'Description', 'Sets the relevant meta-tag in the &lt;head>', 'string', '', ''),
('Site', 'Keywords', 'Sets the relevant meta-tag in the &lt;head>', 'string', '', ''),
('Site', 'Recaptcha Public Key', NULL, 'string', '', NULL),
('Site', 'Recaptcha Private Key', NULL, 'string', '', NULL),
('Site', 'Title', 'Sets the relevant meta-tag in the &lt;head>', 'string', '', '');
