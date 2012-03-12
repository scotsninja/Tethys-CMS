-- phpMyAdmin SQL Dump
-- version 3.4.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 16, 2012 at 10:30 PM
-- Server version: 5.5.16
-- PHP Version: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `website_20xx`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_keys`
--

CREATE TABLE IF NOT EXISTS `access_keys` (
  `acId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `acValue` varchar(255) NOT NULL,
  `acDateUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `acDateStart` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `acDateEnd` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `acActive` int(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`acId`),
  UNIQUE KEY `acValue` (`acValue`),
  KEY `acDateEnd` (`acDateEnd`,`acDateStart`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;


-- --------------------------------------------------------

--
-- Table structure for table `banned`
--

CREATE TABLE IF NOT EXISTS `banned` (
  `banId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `banType` enum('ip','user','email') DEFAULT 'user',
  `banValue` varchar(255) DEFAULT NULL,
  `banAdminId` int(11) unsigned NOT NULL,
  `banNotes` varchar(255) DEFAULT NULL,
  `banDateUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `banDateBanned` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `banDateExpires` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`banId`),
  KEY `banType` (`banType`),
  KEY `banValue` (`banValue`),
  KEY `banDateBanned` (`banDateBanned`,`banDateExpires`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `benchmarking`
--

CREATE TABLE IF NOT EXISTS `benchmarking` (
  `bmId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bmPageId` varchar(255) NOT NULL,
  `bmPage` varchar(255) DEFAULT NULL,
  `bmVars` varchar(255) DEFAULT NULL,
  `bmAction` varchar(255) NOT NULL,
  `bmExecTime` double(20,5) unsigned DEFAULT '0.00000',
  `bmNotes` text,
  `bmScriptEnd` tinyint(3) unsigned DEFAULT '1',
  `bmDateUpdated` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `bmDate` timestamp NULL DEFAULT NULL,
  `bmTimeStart` double(20,5) unsigned DEFAULT '0.00000',
  `bmTimeEnd` double(20,5) unsigned DEFAULT '0.00000',
  PRIMARY KEY (`bmId`),
  KEY `bmPage` (`bmPage`),
  KEY `bmDate` (`bmDate`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- ---------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE IF NOT EXISTS `blogs` (
  `blogId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `blogName` varchar(255) NOT NULL,
  `blogType` enum('post','photo') NOT NULL DEFAULT 'post',
  `blogCategories` varchar(255) DEFAULT NULL,
  `blogDescription` varchar(255) DEFAULT NULL,
  `blogUrl` varchar(255) DEFAULT NULL,
  `blogImage` varchar(255) DEFAULT NULL,
  `blogDateUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `blogDateAdded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `blogDefault` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `blogActive` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`blogId`),
  KEY `blogUrl` (`blogUrl`),
  KEY `blogDefault` (`blogDefault`),
  KEY `blogActive` (`blogActive`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `blog_photos`
--

CREATE TABLE IF NOT EXISTS `blog_photos` (
  `bpId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bpBlogId` int(11) unsigned NOT NULL,
  `bpOrder` int(11) unsigned NOT NULL,
  `bpTitle` varchar(255) DEFAULT NULL,
  `bpCaption` text,
  `bpFile` varchar(255) DEFAULT NULL,
  `bpDateUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `bpDatePosted` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `bpDateAdded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`bpId`),
  KEY `bpBlogId` (`bpBlogId`,`bpOrder`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `blog_photo_tags`
--

CREATE TABLE IF NOT EXISTS `blog_photo_tags` (
  `bptId` int(11) unsigned NOT NULL,
  `bptValue` varchar(255) NOT NULL,
  UNIQUE KEY `bptId` (`bptId`,`bptValue`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `bpId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bpBlogId` int(11) unsigned NOT NULL,
  `bpTitle` varchar(255) DEFAULT NULL,
  `bpExcerpt` varchar(255) DEFAULT NULL,
  `bpValue` text,
  `bpComments` enum('open','closed','registered','open-validate') NOT NULL DEFAULT 'open',
  `bpUrl` varchar(255) DEFAULT NULL,
  `bpImage` varchar(255) DEFAULT NULL,
  `bpDateUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `bpDatePosted` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `bpDateCreated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`bpId`),
  KEY `bpBlogId` (`bpBlogId`),
  KEY `bpDatePosted` (`bpDatePosted`),
  KEY `bpUrl` (`bpUrl`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- ---------------------------------------------------

--
-- Table structure for table `blog_post_tags`
--

CREATE TABLE IF NOT EXISTS `blog_post_tags` (
  `bptId` int(11) unsigned NOT NULL,
  `bptValue` varchar(255) NOT NULL,
  UNIQUE KEY `bptId` (`bptId`,`bptValue`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------

--
-- Table structure for table `message_log`
--

CREATE TABLE IF NOT EXISTS `message_log` (
  `mlId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mlType` int(11) NOT NULL,
  `mlValue` text NOT NULL,
  `mlDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mlBacktrace` text,
  `mlSession` text,
  `mlVars` text,
  PRIMARY KEY (`mlId`),
  KEY `mlType` (`mlType`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE IF NOT EXISTS `pages` (
  `pageId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pageUserId` int(11) unsigned NOT NULL,
  `pageName` varchar(100) NOT NULL,
  `pageValue` text,
  `pageTitle` varchar(255) DEFAULT NULL,
  `pageDescription` varchar(255) DEFAULT NULL,
  `pageKeywords` varchar(255) DEFAULT NULL,
  `pageAuthor` varchar(255) DEFAULT NULL,
  `pageUrl` varchar(255) DEFAULT NULL,
  `pageFile` varchar(255) DEFAULT NULL,
  `pageProtected` tinyint(3) NOT NULL DEFAULT '0',
  `pageUserLevel` enum('none','user','admin') DEFAULT 'none',
  `pageComments` enum('none','bottom','left','right') DEFAULT 'none',
  `pageWidth` tinyint(3) unsigned NOT NULL DEFAULT '12',
  `pageIncludePage` int(11) unsigned DEFAULT NULL,
  `pageIncludePosition` enum('left','right') DEFAULT 'right',
  `pageCSS` text,
  `pageDateUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `pageDateCreated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `pageActive` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pageId`),
  KEY `pageUrl` (`pageUrl`),
  KEY `pageActive` (`pageActive`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `page_views`
--

CREATE TABLE IF NOT EXISTS `page_views` (
  `pvId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pvUserId` int(11) DEFAULT NULL,
  `pvIPAddress` varchar(25) DEFAULT NULL,
  `pvPage` varchar(255) DEFAULT NULL,
  `pvReferringPage` varchar(255) DEFAULT NULL,
  `pvClient` varchar(255) DEFAULT NULL,
  `pvDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pvId`),
  KEY `uaUserId` (`pvUserId`),
  KEY `uaIPAddress` (`pvIPAddress`),
  KEY `pvDate` (`pvDate`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE IF NOT EXISTS `profiles` (
  `profileId` int(11) unsigned NOT NULL,
  `profileFirstName` varchar(55) DEFAULT NULL,
  `profileMiddleName` varchar(55) DEFAULT NULL,
  `profileLastName` varchar(55) DEFAULT NULL,
  `profileLocation` varchar(100) DEFAULT NULL,
  `profileBio` text,
  `profileTalents` text,
  `profileAvatar` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`profileId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE IF NOT EXISTS `settings` (
  `settingId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `settingCategory` varchar(25) NOT NULL,
  `settingName` varchar(255) NOT NULL,
  `settingDescription` varchar(255) DEFAULT NULL,
  `settingType` enum('string','integer','boolean','email','html','path') DEFAULT NULL,
  `settingValue` varchar(255) NOT NULL,
  `settingDefault` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`settingId`),
  UNIQUE KEY `settingCategory` (`settingCategory`,`settingName`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `temp_pages`
--

CREATE TABLE IF NOT EXISTS `temp_pages` (
  `tpId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tpTitle` varchar(255) DEFAULT NULL,
  `tpValue` text,
  `tpCSS` text,
  `tpComments` enum('none','bottom','left','right') DEFAULT 'none',
  `tpWidth` tinyint(3) unsigned NOT NULL DEFAULT '12',
  `tpIncludePage` int(11) unsigned DEFAULT NULL,
  `tpIncludePosition` enum('left','right') DEFAULT 'right',
  `tpDateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tpId`),
  KEY `tpDateCreated` (`tpDateCreated`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `userId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userEmail` varchar(255) NOT NULL,
  `userPassword` varchar(255) DEFAULT NULL,
  `userLevel` enum('user','moderator','admin','super-admin') NOT NULL DEFAULT 'user',
  `userFacebookId` int(11) unsigned DEFAULT NULL,
  `userDateUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userDateJoined` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `userAccessKey` varchar(255) DEFAULT NULL,
  `userResetCode` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`userId`),
  UNIQUE KEY `userFacebookId` (`userFacebookId`),
  UNIQUE KEY `userResetCode` (`userResetCode`),
  KEY `userEmail` (`userEmail`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_activity`
--

CREATE TABLE IF NOT EXISTS `user_activity` (
  `uaUserId` int(11) DEFAULT NULL,
  `uaUsername` varchar(255) DEFAULT NULL,
  `uaIPAddress` varchar(25) DEFAULT NULL,
  `uaClient` varchar(255) DEFAULT NULL,
  `uaDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uaAction` enum('success','failure','logout') NOT NULL DEFAULT 'failure',
  KEY `uaUserId` (`uaUserId`),
  KEY `uaIPAddress` (`uaIPAddress`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
