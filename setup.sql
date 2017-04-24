-- Project Manager v0.3
-- (c) 2017 by David Refoua <David@Refoua>
-- https://github.com/DRSDavidSoft/Project-Manager
--------------------------------------------------
--
-- Initial Database setup file
-- https://www.phpmyadmin.net/
--
-- IMPORTANT NOTICE:
--    This is just a demo file to make sure you have
--    the same table structure as me. In the future,
--    I will create an automatic setup, which will 
--    remove this file.
--
-- Host: localhost
-- Generation Time: Apr 24, 2017 at 08:37 PM
-- Server version: 5.7.14-log
-- PHP Version: 7.0.8


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `todo_manager`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `progress_increase` double NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0',
  `time_productive` int(20) NOT NULL DEFAULT '0',
  `date_started` int(20) NOT NULL,
  `date_finished` int(20) NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `records` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `task_id`, `progress_increase`, `status`, `time_productive`, `date_started`, `date_finished`, `description`, `records`) VALUES
(21, 27, 0, 1, 0, 1482504468, 1482542835, 'This is an activity which is done on a task.', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `groups` varchar(100) DEFAULT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `groups`, `description`) VALUES
(1, 'Personal Stuff', NULL, 'Hobby and personal projects.'),
(2, 'Example Project', NULL, 'Stuff related to an example project.'),
(10, 'Another Project', NULL, 'Stuff related to another example project.'),
(11, 'Customer 1', NULL, 'Details about the first customer'),
(25, 'Customer 2', NULL, 'Details about the customer #2'),
(33, 'Customer 3', NULL, 'Details about the customer #3'),
(35, 'Customer 4', NULL, 'Details about the customer #4'),
(44, 'Customer 5', NULL, 'Details about the customer #5'),
(45, 'Customer 6', NULL, 'Details about the customer #6'),
(46, 'Customer 7', NULL, 'Details about the customer #7'),
(48, 'Customer 8', NULL, 'Details about the customer #8'),
(72, 'Customer 9', NULL, 'Details about the customer #9'),
(75, 'Customer 10', NULL, 'Details about the customer #10'),
(190, 'Customer 11', NULL, 'Details about the customer #11'),
(201, 'Customer 12', NULL, 'Details about the customer #12'),
(202, 'Customer 13', NULL, 'Details about the customer #13'),
(205, 'Customer 14', NULL, 'Details about the customer #14'),
(206, 'Customer 15', NULL, 'Details about the customer #15'),
(280, 'Customer 16', NULL, 'Details about the customer #16'),
(281, 'Customer 17', NULL, 'Details about the customer #17'),
(290, 'Life and Family', NULL, 'IRL stuff');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `priority` double DEFAULT '0',
  `category` varchar(100) DEFAULT NULL,
  `status` int(20) UNSIGNED NOT NULL DEFAULT '0',
  `parent_id` bigint(20) DEFAULT NULL,
  `project_id` bigint(20) DEFAULT NULL,
  `projects_linked` varchar(100) DEFAULT NULL,
  `progress_total` double NOT NULL DEFAULT '0',
  `date_created` bigint(20) UNSIGNED DEFAULT NULL,
  `date_deadline` int(20) UNSIGNED DEFAULT NULL,
  `date_lastactivity` int(20) UNSIGNED DEFAULT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `name`, `type`, `priority`, `category`, `status`, `parent_id`, `project_id`, `projects_linked`, `progress_total`, `date_created`, `date_deadline`, `date_lastactivity`, `description`) VALUES
(12, 'Fix Automount Drive', 'todo', 0.8, 'software, network', 6, NULL, 11, '11', 1, 1471196659, NULL, 1471369459, 'These are some "lorem ipsum"less description you\ve got there'),
(13, 'Complete Database.php File', 'todo', 0.9, 'programming, web', 1, NULL, 2, '11, 2', 0.75, 1471472839, NULL, 1481624239, 'Do you wanna build an snowman?'),
(14, 'Linux Account Password', 'todo', 0.2, 'software, network', 1, NULL, 11, '11', 0.05, 1471196659, NULL, NULL, 'Stuff which will buried in the database'),
(15, 'cURL Force using HTTPS', 'todo', 0.8, 'programming, web', 6, NULL, 2, '2', 1, 1468401439, NULL, 1471079839, 'Force HTTPS on cURL_exec() with Regex or Host Match'),
(16, 'Task Manager Stuff', 'todo', 0.4, 'programming', 1, NULL, 1, '1', 0.08, 1482164700, 1482337500, 1482234379, 'TODO: add Delete function\r\nTODO: add CSRF protection (dynamic fetch with Ajax)'),
(18, 'Some example Title', 'todo', 0.4, 'programming,javascript,web', 1, NULL, 1, NULL, 0.1, 1438529107, NULL, 1461774483, 'Write an adblocker\r\nWrite to the forum\r\n\r\nAds Removed v2.0 -> actually use jQuery\'s .remove() option on ads and also make sure we\'re indeed removing the actual ADS, not els containing that word.\r\nfunction removeObj(el) el.parentElement.removeChild(el);'),
(19, 'Be friends with Bill Gates', 'todo', 0.2, 'design,graphics', 9, NULL, 44, NULL, 0.5, 1464363077, 1478017877, 1472225477, 'This is a crucial phase for you'),
(20, 'Get rich and purchase apple', 'todo', 0.2, NULL, 9, NULL, 48, NULL, 0.05, 1473867722, NULL, 1482425025, 'So you can do more stuff'),
(21, 'With apple, buy Google (ABC)', 'todo', 0.6, 'programming,web,design', 33, NULL, 48, NULL, 0.5, 1437666328, NULL, 1479401128, 'I know you expected the other way around'),
(22, 'Then get more rich', 'todo', 0.4, 'programming,software,electronics,design', 41, NULL, 46, NULL, 0.2, 1479314912, NULL, 1481388512, 'Also a crucial part'),
(23, 'Buy Microsoft', 'todo', 0.8, 'electronics,design,programming', 33, NULL, 1, '1, 11, 35, 46, 48, 201, 202', 0.8, 1447347038, NULL, 1481129438, 'Open source the f-ing Windows'),
(24, 'Hire people to merge similar projects', 'todo', 0.4, 'programming,web,gaming,software,design', 41, NULL, 25, NULL, 0.85, 1398096180, NULL, 1463241601, 'For example Google Docs and OneDrive will be one thing'),
(25, 'Cheange the company name to come up with a cool name for the company', 'todo', 0.6, 'programming,web', 9, NULL, 33, '1, 33, 72', 0.85, 1463674075, NULL, 1480007412, 'BNL looks good'),
(26, 'With BNL, be the president', 'todo', 0.2, 'web,design,programming', 41, NULL, 201, NULL, 0.3, 1421946754, NULL, 1436890354, 'Better than that orange guy'),
(27, 'Send an spaceship to the space in the far future and clean the earth', 'todo', 0.8, 'programming,web', 33, NULL, 11, NULL, 0.83, 1481727208, 1482694200, 1482504886, 'The spaceship will be called Axiom.');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;
--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=291;
--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
