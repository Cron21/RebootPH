-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 21, 2025 at 12:00 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u569378998_rebootph`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_us_content`
--

CREATE TABLE `about_us_content` (
  `id` int(11) NOT NULL,
  `type` enum('mission','vision') NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_us_content`
--

INSERT INTO `about_us_content` (`id`, `type`, `title`, `subtitle`, `updated_at`) VALUES
(1, 'mission', 'Our Mission', 'Just and Democratic Transition to 100% Renewable Energy in the Philippines', '2025-12-17 20:43:26'),
(2, 'vision', 'Our Vision', 'Energy Leadership through Transdisciplinary', '2025-12-17 20:43:39'),
(3, 'vision', 'Our Vision', 'Energy Leadership through Transdisciplinary', '2025-12-20 10:30:54'),
(4, 'mission', 'Our Mission', 'Just and Democratic Transition to 100% Renewable Energy in the Philippines', '2025-12-20 12:55:01'),
(5, 'mission', 'Our Mission', 'Just and Democratic Transition to 100% Renewable Energy in the Philippines', '2025-12-20 12:55:02'),
(6, 'mission', 'Our Mission', 'Just and Democratic Transition to 100% Renewable Energy in the Philippines', '2025-12-20 16:30:30'),
(7, 'mission', 'Our Mission', 'Just and Democratic Transition to 100% Renewable Energy in the Philippines', '2025-12-20 16:30:36');

-- --------------------------------------------------------

--
-- Table structure for table `about_us_points`
--

CREATE TABLE `about_us_points` (
  `point_id` int(11) NOT NULL,
  `content_type` enum('mission','vision') NOT NULL,
  `point_text` text NOT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_us_points`
--

INSERT INTO `about_us_points` (`point_id`, `content_type`, `point_text`, `display_order`) VALUES
(17, 'vision', 'A country heading towards the just and democratic energy transition as a response to the climate crisis, where youth are empowered to play an active role in its fruition.', 1),
(18, 'vision', 'An established, dynamic, and multi-sectoral community of renewable energy advocates and leaders in the Philippines that drives nationwide progress in energy access, security, and sustainability.', 2),
(19, 'vision', 'A Filipino citizenry that firmly understands the urgency of climate change, demands the acceleration of the energy transition, and is empowered to implement agency over its energy systems.', 3),
(33, 'mission', 'To catalyze the Philippines’ just and democratic energy transition through active involvement in political, economic, social, technological, and environmental spheres.', 1),
(34, 'mission', 'To encourage knowledge creation and collective model-building towards the energy transition through developing the value-creation competencies of organizations and individuals.', 2),
(35, 'mission', 'To empower Filipinos by enabling channels for the public understanding of climate, energy, and development issues.', 3),
(36, 'mission', 'To ensure safe and open spaces for youth leadership and participation in the Philippines’ journey towards the transition to full renewable energy.', 4);

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `AnnouncementID` int(20) NOT NULL,
  `ProposalID` int(11) NOT NULL,
  `IsPriority` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedByAdminID` int(20) NOT NULL,
  `LastModifiedBy` int(20) NOT NULL,
  `LastModifiedDate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement`
--

INSERT INTO `announcement` (`AnnouncementID`, `ProposalID`, `IsPriority`, `CreatedByAdminID`, `LastModifiedBy`, `LastModifiedDate`) VALUES
(59, 44, 0, 1, 1, '2025-12-21 08:47:12'),
(60, 46, 0, 1, 1, '2025-12-21 09:37:10');

-- --------------------------------------------------------

--
-- Table structure for table `application`
--

CREATE TABLE `application` (
  `ApplicationID` int(50) UNSIGNED NOT NULL,
  `FName` varchar(200) NOT NULL,
  `MName` varchar(50) DEFAULT NULL,
  `LName` varchar(50) NOT NULL,
  `ExtensionName` varchar(3) DEFAULT NULL,
  `Gender` varchar(100) NOT NULL,
  `specify_gender` varchar(100) DEFAULT NULL,
  `MarginalizedSector` varchar(100) NOT NULL,
  `specify_marginalizedsector` varchar(100) DEFAULT NULL,
  `BirthDate` date NOT NULL,
  `ApplicantEmail` varchar(150) NOT NULL,
  `PasswordHash` char(60) DEFAULT NULL,
  `SubmissionDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `ApplicationStatus` int(1) NOT NULL DEFAULT 0,
  `ReviewDate` datetime DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application`
--

INSERT INTO `application` (`ApplicationID`, `FName`, `MName`, `LName`, `ExtensionName`, `Gender`, `specify_gender`, `MarginalizedSector`, `specify_marginalizedsector`, `BirthDate`, `ApplicantEmail`, `PasswordHash`, `SubmissionDate`, `ApplicationStatus`, `ReviewDate`, `Phone`) VALUES
(1, 'Super', 'Admin', 'Admin', '', '', '', '', '', '2003-02-21', 'Admin@rebootph.com', '$2y$10$2rQ.Caeo4cdjtFCOXNde8ub9mdizGi3IB2GCHCMnumBu1sdZPOcFu', '2025-12-04 20:30:25', 1, NULL, NULL),
(22, 'Tonic', '', 'Madulid', '', '', '', '', '', '2000-12-12', 'tonicmadulid@gmail.com', '$2y$10$t0qlJelRzowRfKcbggspN.qX/ehFQWZS0J1dAIa76BCzSx/aoEXmC', '2025-12-18 07:48:14', 1, '2025-12-18 07:50:17', NULL),
(23, 'Angelyn', '', 'Sager', '', '', '', '', '', '2000-11-11', 'angelynsager@gmail.com', '$2y$10$3aZraZG1ufpP2WFWQ9YuCe4QiqmkpRYhmIyFoGmhwtVFP6L4coxEm', '2025-12-18 07:49:35', 1, '2025-12-18 07:50:25', NULL),
(24, 'Leo', '', 'Jaminola', '', '', '', '', '', '2000-10-10', 'leojaminola@gmail.com', '$2y$10$I/44K0/u9uNKmBqDAMhJyupa3hKzSO07dBsap0RrT/vKIV0EXmzru', '2025-12-18 07:50:57', 1, '2025-12-18 08:27:46', NULL),
(25, 'Anthony', '', 'Jacob', '', '', '', '', '', '2000-09-09', 'anthonyjacob@gmail.com', '$2y$10$eVjdMotNXzqJ1wUd/i7MmO1dXj0aEoeQ8Q0/DKobAJsYuHKmsoVha', '2025-12-18 07:52:13', 1, '2025-12-18 08:27:49', NULL),
(26, 'Randy', '', 'Naing', '', '', '', '', '', '2000-08-08', 'randynaing@gmail.com', '$2y$10$9KuXimj6nDLsp2PWiqOzYO8Q1Btq0nzXFagG59tjRSm4S3yS4M0Ma', '2025-12-18 07:54:09', 1, '2025-12-18 08:27:52', NULL),
(27, 'Adonis', '', 'Salen', '', '', '', '', '', '2000-07-07', 'adonissalen@gmail.com', '$2y$10$/ndnXYNa1okbODYHyH2dlutt8FGUkt4xTBj4ohncs2g9gGDn4WJ.S', '2025-12-18 07:56:34', 1, '2025-12-18 08:27:56', NULL),
(28, 'Christian Jay', '', 'Marmol', '', '', '', '', '', '2000-06-06', 'christianjaymarmol@gmail.com', '$2y$10$yBFZyctbCEuwaRWjIS1Ul.RiZ3esgMngtoghk2v1uGthIcgW6Duqq', '2025-12-18 07:57:26', 1, '2025-12-18 08:28:07', NULL),
(35, 'Mary Janelle', 'Caro', 'Caparros', '', 'Female', NULL, 'Not Applicable', NULL, '2005-06-16', 'caparrosmaryjanellec@gmail.com', '$2y$10$IagxzppXdU4RXR.rozbop.197JEEnFviqBMpcDohpx0YhnIO.AxE2', '2025-12-20 10:05:44', 1, '2025-12-20 10:22:29', NULL),
(36, 'S', 'LAYUG', 'P', '', 'Bigender', NULL, 'Chronic Disease', NULL, '2025-12-20', 'adonis.salen2@gmail.com', NULL, '2025-12-20 12:22:02', 2, '2025-12-21 09:20:31', NULL),
(38, 'Mary Janelle', 'Caro', 'Caparros', '', 'Female', NULL, 'Not Applicable', NULL, '2005-06-16', 'maryyyjanellee0616@gmail.com', NULL, '2025-12-20 13:01:21', 0, NULL, NULL),
(42, 'Byron Anjelu', 'Aguila', 'Calajate', '', 'Male', NULL, 'Youth', NULL, '2003-02-21', 'byron.anjelu0221@gmail.com', '$2y$10$xp4ejaten9drDuQwCOOP6uaJbKqpN6g.16FNxdVys8HiPrUPqCRVO', '2025-12-21 09:16:44', 1, '2025-12-21 09:16:56', NULL);

--
-- Triggers `application`
--
DELIMITER $$
CREATE TRIGGER `insert_member_on_approval` AFTER UPDATE ON `application` FOR EACH ROW BEGIN
  IF (NEW.ApplicationStatus = 1 AND (OLD.ApplicationStatus IS NULL OR OLD.ApplicationStatus = 0)) THEN
    INSERT INTO member (ApplicationID, Role, isActive, JoinDate) 
    VALUES (NEW.ApplicationID, 'Member', 1, NOW());
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `applicationanswer`
--

CREATE TABLE `applicationanswer` (
  `AnswerID` int(10) UNSIGNED NOT NULL,
  `ApplicationID` int(10) UNSIGNED NOT NULL,
  `QuestionID` int(10) UNSIGNED NOT NULL,
  `AnswerText` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicationanswer`
--

INSERT INTO `applicationanswer` (`AnswerID`, `ApplicationID`, `QuestionID`, `AnswerText`) VALUES
(41, 22, 1, 'awezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascx'),
(42, 22, 6, 'awezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascx'),
(43, 23, 1, 'awezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascx'),
(44, 23, 6, 'awezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascx'),
(45, 24, 1, 'awezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascx'),
(46, 24, 6, 'awezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascx'),
(47, 25, 1, 'awezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascx'),
(48, 25, 6, 'awezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascx'),
(49, 26, 1, 'awezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascx'),
(50, 26, 6, 'awezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascx'),
(51, 27, 1, 'awezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascx'),
(52, 27, 6, 'awezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascx'),
(53, 28, 1, 'awezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascx'),
(54, 28, 6, 'awezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascxawezrxdtygvhbunijmkfnjhdbdascx'),
(67, 35, 1, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'),
(68, 35, 6, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'),
(69, 36, 1, 'dasdasdasdadadadasdasdasdasdasdasdasdasdasdasdasdasdasdasddadasdasdasdasdasdasdasdasdsdasdasdasdasdasda'),
(70, 36, 6, 'asdasdasdasdasdasdasdasdasdasdasdasdasdasdasdsadasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasa'),
(73, 38, 1, 'ssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssss'),
(74, 38, 6, 'sssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssss'),
(81, 42, 1, 'I want to join Reboot PH because I am deeply committed to the transition toward a sustainable and energy-secure future for the Philippines. As a student, I’ve seen how our country is disproportionately affected by the climate crisis, and I believe that grassroots youth movements are the most effective way to drive systemic change. I am eager to learn from a community of like-minded advocates and contribute my energy to campaigns that prioritize renewable energy over fossil fuels. Joining Reboot PH is an opportunity for me to turn my academic knowledge into tangible climate action while growing as a young leader.'),
(82, 42, 6, 'In my past experiences as a campus volunteer, I have organized local awareness drives about waste management and sustainability, which taught me the importance of community mobilization. My long-term goal is to work in the field of environmental policy, and I see Reboot PH’s mission of advocating for a just energy transition as the perfect alignment for this career path.');

-- --------------------------------------------------------

--
-- Table structure for table `applicationquestionnaire`
--

CREATE TABLE `applicationquestionnaire` (
  `QuestionID` int(20) UNSIGNED NOT NULL,
  `QuestionText` text NOT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `UpdatedByMemberID` int(10) NOT NULL,
  `DateCreated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `LastDateUpdated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicationquestionnaire`
--

INSERT INTO `applicationquestionnaire` (`QuestionID`, `QuestionText`, `IsActive`, `UpdatedByMemberID`, `DateCreated`, `LastDateUpdated`) VALUES
(1, 'How do you see your past experiences and future goals aligning with Reboot PH\'s mission, and what specific contribution do you hope to make as a member?', 1, 1, '2025-12-07 08:20:41', '2025-12-04 22:24:13'),
(6, 'Why do you want to join this organization?', 1, 1, '2025-12-07 08:20:41', '2025-12-05 00:17:58');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `CategoryID` int(11) NOT NULL,
  `Type` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`CategoryID`, `Type`) VALUES
(1, 'YES (Youth Empowered Spaces)'),
(3, 'JETx* (Just Energy Transition x*)'),
(4, 'SEAL (Skills in Energy & Environment Advocacy & Leadership)'),
(5, 'CEL (Climate and Energy Literacy)');

-- --------------------------------------------------------

--
-- Table structure for table `core_values`
--

CREATE TABLE `core_values` (
  `value_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_class` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `core_values`
--

INSERT INTO `core_values` (`value_id`, `title`, `description`, `icon_class`, `display_order`, `is_active`) VALUES
(1, 'Innovation', 'We embrace creative solutions and new technologies to address environmental challenges.', 'bi-lightbulb', 1, 1),
(2, 'Collaboration', 'We believe in the power of partnerships and collective action for sustainable change.', 'bi-people', 2, 1),
(3, 'Education', 'We prioritize knowledge sharing and capacity building for long-term impact.', 'bi-award', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `EventID` int(20) NOT NULL,
  `ProposalID` int(11) DEFAULT NULL,
  `CreatedByAdminID` int(20) NOT NULL,
  `RegistrationDeadline` datetime NOT NULL,
  `SerialNumber` int(50) NOT NULL,
  `QRCode` blob NOT NULL,
  `LastModifiedBy` int(20) NOT NULL,
  `LastModifiedDate` date NOT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`EventID`, `ProposalID`, `CreatedByAdminID`, `RegistrationDeadline`, `SerialNumber`, `QRCode`, `LastModifiedBy`, `LastModifiedDate`, `status`) VALUES
(46, 44, 1, '2024-02-06 00:00:00', 641575, 0x4556454e542d34342d363431353735, 1, '2025-12-21', NULL),
(47, 46, 1, '2025-12-20 00:00:00', 423421, 0x4556454e542d34362d343233343231, 1, '2025-12-21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `eventattendance`
--

CREATE TABLE `eventattendance` (
  `AttendanceID` int(20) NOT NULL,
  `RegistrationID` int(11) NOT NULL,
  `AttendanceTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `ScanType` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `FeedbackID` int(11) NOT NULL,
  `AttendanceID` int(10) NOT NULL,
  `SubmissionDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Comments` text NOT NULL,
  `Rating` enum('1','2','3','4','5') NOT NULL,
  `OverallExperience` enum('Excellent','Good','Average','Poor') DEFAULT NULL,
  `KnowledgeGained` tinyint(1) DEFAULT NULL,
  `IsAnonymous` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hero_section`
--

CREATE TABLE `hero_section` (
  `heroID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `PublishDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `CreateByAdminID` int(10) DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 0,
  `LastModifiedBy` int(10) DEFAULT NULL,
  `LastModifiedDate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hero_section`
--

INSERT INTO `hero_section` (`heroID`, `Title`, `description`, `PublishDate`, `CreateByAdminID`, `isActive`, `LastModifiedBy`, `LastModifiedDate`) VALUES
(8, 'Lead the Charge for a Greener Philippines.', 'Welcome, future leader! Join the community of youth-led energy advocates transitioning the nation to a sustainable future.', '2025-12-21 09:51:53', 1, 1, 1, '2025-12-21 09:51:53');

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

CREATE TABLE `images` (
  `ImageID` int(50) NOT NULL,
  `InitiativeID` int(50) UNSIGNED DEFAULT NULL,
  `NewsletterID` bigint(20) UNSIGNED DEFAULT NULL,
  `ImagePath` text NOT NULL,
  `DateAdded` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `images`
--

INSERT INTO `images` (`ImageID`, `InitiativeID`, `NewsletterID`, `ImagePath`, `DateAdded`) VALUES
(21, 4, NULL, 'assets/image/img_1765442946_8c868e8d8d.jpg', '2025-12-11 08:49:41'),
(22, 4, NULL, 'assets/image/img_1765442948_f83bbe6b8e.jpg', '2025-12-11 08:49:41'),
(23, 4, NULL, 'assets/image/img_1765442950_ef5fad357c.jpg', '2025-12-11 08:49:41'),
(24, 4, NULL, 'assets/image/img_1765442951_0f66d687cc.jpg', '2025-12-11 08:49:41'),
(25, 4, NULL, 'assets/image/img_1765442953_3ce0285d85.jpg', '2025-12-11 08:49:41'),
(26, 4, NULL, 'assets/image/img_1765442954_6567814404.jpg', '2025-12-11 08:49:41'),
(27, 4, NULL, 'assets/image/img_1765442956_c5e876c431.jpg', '2025-12-11 08:49:41'),
(28, 4, NULL, 'assets/image/img_1765442957_e760438f88.jpg', '2025-12-11 08:49:41'),
(29, 4, NULL, 'assets/image/img_1765442959_33bfec5b48.jpg', '2025-12-11 08:49:41'),
(30, 4, NULL, 'assets/image/img_1765442960_c33fe8318d.jpg', '2025-12-11 08:49:41'),
(31, 4, NULL, 'assets/image/img_1765442961_2876613460.jpg', '2025-12-11 08:49:41'),
(32, 4, NULL, 'assets/image/img_1765442962_d5c1a0a63b.jpg', '2025-12-11 08:49:41'),
(33, 4, NULL, 'assets/image/img_1765442964_1d658c3bfe.jpg', '2025-12-11 08:49:41'),
(34, 4, NULL, 'assets/image/img_1765442965_b21177201f.jpg', '2025-12-11 08:49:41'),
(35, 5, NULL, 'assets/image/img_1765444157_17f21c23dd.jpg', '2025-12-11 09:10:06'),
(36, 5, NULL, 'assets/image/img_1765444159_0ea583e1a8.jpg', '2025-12-11 09:10:06'),
(37, 5, NULL, 'assets/image/img_1765444160_7d95d8dc91.jpg', '2025-12-11 09:10:06'),
(38, 5, NULL, 'assets/image/img_1765444162_d02404869a.jpg', '2025-12-11 09:10:06'),
(39, 5, NULL, 'assets/image/img_1765444163_f602b11213.jpg', '2025-12-11 09:10:06'),
(40, 5, NULL, 'assets/image/img_1765444164_1537f48092.jpg', '2025-12-11 09:10:06'),
(41, 5, NULL, 'assets/image/img_1765444166_aa786b9b92.jpg', '2025-12-11 09:10:06'),
(42, 5, NULL, 'assets/image/img_1765444167_b032e93730.jpg', '2025-12-11 09:10:06'),
(43, 5, NULL, 'assets/image/img_1765444169_230bfbcdaa.jpg', '2025-12-11 09:10:06'),
(44, 5, NULL, 'assets/image/img_1765444171_800543b47d.jpg', '2025-12-11 09:10:06'),
(45, 5, NULL, 'assets/image/img_1765444172_5444f78ecd.jpg', '2025-12-11 09:10:06'),
(46, 5, NULL, 'assets/image/img_1765444173_55e1d881d8.jpg', '2025-12-11 09:10:06'),
(47, 5, NULL, 'assets/image/img_1765444175_567d6b9c1d.jpg', '2025-12-11 09:10:06'),
(53, 7, NULL, 'assets/image/img_1766044660_7b6eb0b357.jpg', '2025-12-18 07:59:00'),
(54, 7, NULL, 'assets/image/img_1766044662_fd96a2d1c5.jpg', '2025-12-18 07:59:00'),
(55, 7, NULL, 'assets/image/img_1766044664_1f39b61485.jpg', '2025-12-18 07:59:00'),
(56, 7, NULL, 'assets/image/img_1766044665_0e62c6df48.jpg', '2025-12-18 07:59:00'),
(57, 7, NULL, 'assets/image/img_1766044667_096eb9be82.jpg', '2025-12-18 07:59:00'),
(58, 7, NULL, 'assets/image/img_1766044669_a7ece04653.jpg', '2025-12-18 07:59:00'),
(59, 7, NULL, 'assets/image/img_1766044672_ee72664ec7.jpg', '2025-12-18 07:59:00'),
(60, 7, NULL, 'assets/image/img_1766044674_9d8f0afc96.jpg', '2025-12-18 07:59:00'),
(61, 7, NULL, 'assets/image/img_1766044680_23f1abbf92.jpg', '2025-12-18 07:59:00'),
(62, 7, NULL, 'assets/image/img_1766044683_0e3cacb742.jpg', '2025-12-18 07:59:00'),
(63, 7, NULL, 'assets/image/img_1766044684_acb1069cd4.jpg', '2025-12-18 07:59:00'),
(64, 8, NULL, 'assets/image/img_1766045039_c8a59df37f.jpg', '2025-12-18 08:05:37'),
(65, 8, NULL, 'assets/image/img_1766045048_ed9644bc02.jpg', '2025-12-18 08:05:37'),
(66, 8, NULL, 'assets/image/img_1766045052_8c813efa4b.jpg', '2025-12-18 08:05:37'),
(67, 8, NULL, 'assets/image/img_1766045056_f148b0e56d.jpg', '2025-12-18 08:05:37'),
(68, 8, NULL, 'assets/image/img_1766045062_d4c61abe14.jpg', '2025-12-18 08:05:37'),
(69, 8, NULL, 'assets/image/img_1766045068_11e49d6ee1.jpg', '2025-12-18 08:05:37'),
(70, 8, NULL, 'assets/image/img_1766045071_c525acff5d.jpg', '2025-12-18 08:05:37'),
(71, 8, NULL, 'assets/image/img_1766045075_c91bf5d410.jpg', '2025-12-18 08:05:37'),
(73, NULL, 17, 'assets/image/img_1766047308_d5d365c50e.jpg', '2025-12-18 08:41:51'),
(74, 9, NULL, 'assets/image/img_1766049649_c200fe564d.jpg', '2025-12-18 09:21:26'),
(75, 9, NULL, 'assets/image/img_1766049661_cec4b56cb7.jpg', '2025-12-18 09:21:26'),
(76, 9, NULL, 'assets/image/img_1766049673_0782dafabf.jpg', '2025-12-18 09:21:26'),
(77, 9, NULL, 'assets/image/img_1766049680_f9df318f27.jpg', '2025-12-18 09:21:26');

-- --------------------------------------------------------

--
-- Table structure for table `initiatives`
--

CREATE TABLE `initiatives` (
  `InitiativeID` int(50) UNSIGNED NOT NULL,
  `CategoryID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` text NOT NULL,
  `isHighlighted` tinyint(1) NOT NULL DEFAULT 0,
  `PublishDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `CreateByAdminID` int(10) NOT NULL,
  `LastModifiedBy` int(10) DEFAULT NULL,
  `LastModifiedDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `initiatives`
--

INSERT INTO `initiatives` (`InitiativeID`, `CategoryID`, `Title`, `Description`, `isHighlighted`, `PublishDate`, `CreateByAdminID`, `LastModifiedBy`, `LastModifiedDate`) VALUES
(4, 3, 'Diocessan on Integral Ecology 2025', 'In Camarines Norte, we are slowly shaping a greener and more just future together🌱\nAt the Diocesan Congress on Integral Ecology 2025, held last November 27, 2025, in Daet, Camarines Norte, communities and advocates gathered under the leadership of the Diocese of Daet as convenor, with Reboot Philippines serving as facilitators and speakers.  \nStakeholders from the Provincial Government of Camarines Norte (PLGU-CN), various MLGUs, government agencies, civil society organizations, and schools and institutions also joined to deepen the conversation on ecological realities and the future of our province.\nTogether, we witnessed the signing of the Green Covenant—a collective commitment to pursue a Just Transition toward clean and renewable energy in Camarines Norte. \nThe discussion emphasized the need to institutionalize the People’s Council and the JET Council, ensuring that our shift to renewable energy remains people-led, transparent, and rooted in participatory governance. \nA powerful conversation on Intergenerational Justice also took center stage—reminding us that every ecological decision we make today is a moral choice shaping the world we hand to future generations. 🌏👶🏻🧓🏻\n\n———————————————\n\nThis initiative forms part of our broader movement to embed climate justice and people-powered governance in Camarines Norte, creating pathways where communities become co-authors of a greener, more just tomorrow.\n\nPhotos by Camarines Norte PIO', 0, '2025-12-18 09:25:00', 1, 1, '2025-12-18 09:25:00'),
(5, 4, '1st Baseco Youth Parliament', '🌱 Reboot Philippines is proud to support the 1st Baseco Youth Parliament, a pioneering initiative highlighting how the climate crisis intersects with urgent community issues like water scarcity, high electricity costs, education challenges, and public health.\nAs a coastal and riverside community, Baseco faces frequent flooding even during light rain due to high tides and rising sea levels. This initiative emphasizes bottom-up planning and decision-making, ensuring solutions are shaped by the voices and experiences of those that are affected.\nThrough this effort, RebootPH continues its commitment to train and empower young leaders to advocate for just and sustainable development for their communities.', 0, '2025-12-18 09:24:57', 1, 1, '2025-12-18 09:24:57'),
(7, 1, 'San Joaquin, SEALed! ⚡🌱', 'VISAYAS | Reboot Philippines traveled from Bacolod all the way to the province of Iloilo to join “Youthnify: Rise for Resilience— DRRM and Climate Action Forum” led by Sidlak Youth - San Joaquin at San Joaquin, Iloilo last December 13-14, 2025. \nThe local team started with a Climate 101 discussion, followed by a panel discussion with Reboot’s resident volunteers, Andrew Gelera, Benz Solis, and Reboot Philippines Executive Director Tonic Madulid. \nTo cap off the forum, Coleen Awit, Local Coordinator for Visayas, held a campaign building workshop for the youth of San Joaquin. \n#YouthSEALed was also launched with the aim of gathering the young people of Visayas in one online open platform for accessible resource sharing, as an effort to strengthen the campaign for climate education. \nUnifying the youth for Climate Justice, through open and insightful discussions, paving the way for Just Energy Transition across the whole Visayas.\n—————\nSEAL the Future is a a component of the Building One ASEAN of Sustainability and Solidarity with a Partnership between Youth, CSOs, and the Member States (A-SASSY) project, supported by Bröt Für die Welt, with co-funding from the European Union.\nThis in line with Reboot Philippines’ campaign on climate change education, highlighting its importance in building local ecosystems of support for youth-led initiatives on climate action.\n#ResistReclaimReboot\n#SEAL\n#RebootPhilippines', 1, '2025-12-18 09:26:30', 1, 1, '2025-12-18 09:26:30'),
(8, 4, 'Renewable Congress in Iloilo City', 'Reboot Philippines joined the Renewable Congress in Iloilo City last December 11-12, 2025.\nIn the pursuit of advancing just energy transition in the Philippines, members of Reboot Philippines attended and shared their insights on renewable energy investment, localizing just energy transition, and green jobs creation🌱✊🏻\nThrough our network, we aim to forge a path towards renewable energy that is built on coordination, collaboration, and mutual support🌏🤝\n———\nThis event was organized by the The CENTRE Philippines, Province of Iloilo, Institute for Climate and Sustainable Cities (ICSC), and Central Philippine University - Iloilo City. \nThis was supported by Reboot Philippines, Oxfam Pilipinas, FES Philippines, Galing Pook, Forum for the Future, Center for Renewable Energy and Sustainable Technology, CP II, WISE, and Renewable Energy Initiative. \n#ResistReclaimReboot\n#RebootPH\n#RenewableEnergyNow', 1, '2025-12-18 09:26:18', 1, 1, '2025-12-18 09:26:18'),
(9, 4, 'Climate Action Network', 'Reboot Philippines joined Climate Action Nework-South East Asia’s UNPACKING OF COP30 OUTCOMES FOR SOUTHEAST ASIA last December 13, 2025.\n\nThis session unraveled the outcomes of the recent Conference of Parties in Belem, Brazil and what is at state for countries in the ASEAN region🌏🌱\n\nThe session on key opportunities from COP 30 in the ASEAN context was moderated by Reboot Philippines’ Organizational Communications Specialist, Ghillean Pranz Fegidero✨\n\n#ResistReclaimReboot\n#RebootPh', 1, '2025-12-18 09:26:37', 8, 1, '2025-12-18 09:26:37');

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

CREATE TABLE `member` (
  `MemberID` int(20) NOT NULL,
  `ApplicationID` int(50) UNSIGNED DEFAULT NULL,
  `ProfileImage` varchar(255) DEFAULT NULL,
  `Role` enum('Executive Director','Program Officer','Regional Convenor','Local Coordinator','Finance Officer','Meal Officer','Member Staff') NOT NULL DEFAULT 'Member Staff',
  `isActive` tinyint(1) NOT NULL DEFAULT 0,
  `JoinDate` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member`
--

INSERT INTO `member` (`MemberID`, `ApplicationID`, `ProfileImage`, `Role`, `isActive`, `JoinDate`) VALUES
(1, 1, 'uploads/profile-images/img_1765978763_3750a61023.png', 'Executive Director', 1, '2025-12-05'),
(11, 23, NULL, 'Finance Officer', 1, '2025-12-18'),
(12, 22, NULL, 'Executive Director', 1, '2025-12-18'),
(14, 24, NULL, 'Meal Officer', 1, '2025-12-18'),
(15, 25, NULL, 'Program Officer', 1, '2025-12-18'),
(16, 26, NULL, 'Regional Convenor', 1, '2025-12-18'),
(17, 27, NULL, 'Local Coordinator', 1, '2025-12-18'),
(18, 28, NULL, 'Member Staff', 1, '2025-12-18'),
(50, 35, NULL, 'Member Staff', 1, '2025-12-20'),
(51, NULL, NULL, 'Member Staff', 0, '2025-12-20'),
(52, NULL, NULL, 'Member Staff', 0, '2025-12-20'),
(53, NULL, NULL, 'Member Staff', 0, '2025-12-20'),
(54, NULL, NULL, 'Member Staff', 0, '2025-12-20'),
(55, NULL, NULL, 'Member Staff', 0, '2025-12-20'),
(56, NULL, NULL, 'Member Staff', 0, '2025-12-20'),
(57, NULL, NULL, 'Member Staff', 0, '2025-12-20'),
(58, NULL, NULL, 'Member Staff', 0, '2025-12-20'),
(59, NULL, NULL, 'Member Staff', 0, '2025-12-20'),
(60, NULL, NULL, 'Member Staff', 0, '2025-12-20'),
(61, NULL, NULL, 'Member Staff', 0, '2025-12-20'),
(62, NULL, NULL, 'Member Staff', 0, '2025-12-20'),
(63, NULL, NULL, 'Member Staff', 0, '2025-12-20'),
(65, NULL, NULL, 'Member Staff', 0, '2025-12-20'),
(68, 42, NULL, '', 1, '2025-12-21');

-- --------------------------------------------------------

--
-- Table structure for table `member_benefits`
--

CREATE TABLE `member_benefits` (
  `BenefitsID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` text NOT NULL,
  `IconClass` varchar(500) NOT NULL,
  `Order` int(11) NOT NULL,
  `isActive` tinyint(4) NOT NULL,
  `CreateByAdminID` int(11) NOT NULL,
  `CreateDate` date NOT NULL,
  `LastModifiedBy` int(11) DEFAULT NULL,
  `LastModifiedDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_benefits`
--

INSERT INTO `member_benefits` (`BenefitsID`, `Title`, `Description`, `IconClass`, `Order`, `isActive`, `CreateByAdminID`, `CreateDate`, `LastModifiedBy`, `LastModifiedDate`) VALUES
(2, 'Training, Workshops & Seminars', 'Gain access to exclusive learning sessions on climate action, renewable energy, and advocacy.', '', 0, 0, 1, '2025-12-20', NULL, NULL),
(3, 'Local & National Initiatives', 'Participate in campaigns and projects that create real impact in communities across the Philippines.', '', 1, 0, 1, '2025-12-20', NULL, NULL),
(4, 'Skill-Building', 'Develop leadership, communication, technical, and sustainability skills beyond the classroom.', '', 0, 0, 1, '2025-12-20', NULL, NULL),
(5, 'Networking Opportunities', 'Connect with fellow youth advocates, mentors, and partner organizations.', '', 0, 0, 1, '2025-12-20', NULL, NULL),
(6, 'Certificates &Recognition', 'Earn certificates and get recognized for your contributions and leadership.', '', 5, 0, 1, '2025-12-20', NULL, NULL),
(7, 'Leadership Opportunities', 'Take part in planning and leading projects, events, and campaigns.', '', 0, 1, 1, '2025-12-20', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `newsletter`
--

CREATE TABLE `newsletter` (
  `NewsletterID` bigint(20) UNSIGNED NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Content` text NOT NULL,
  `PublishDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `CreatedByAdminID` int(10) NOT NULL,
  `LastModifiedBy` int(10) DEFAULT NULL,
  `LastModifiedDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `newsletter`
--

INSERT INTO `newsletter` (`NewsletterID`, `Title`, `Content`, `PublishDate`, `CreatedByAdminID`, `LastModifiedBy`, `LastModifiedDate`) VALUES
(17, 'Philippines ranked as one of the most dangerous places for climate activists and environmental defenders', 'As we commemorate yesterday’s International Human Rights Day with the theme, “Human Rights, Our Everyday Essentials”, we reaffirm and highlight our fundamental rights amidst all forms of oppression✊🏻\nLet’s continue protecting the rights of those who protect us, our climate, and our environment🌱\nAt the end the of the International Human Rights Day, we continue to defend environmental defenders. The work of climate justice champions continue✨\n#InternationalHumanRightsDay\n#ResistReclaimReboot \n#RebootPh', '2025-12-18 08:23:45', 1, 1, '2025-12-18 08:41:51');

-- --------------------------------------------------------

--
-- Table structure for table `org_team`
--

CREATE TABLE `org_team` (
  `member_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `position` varchar(100) NOT NULL,
  `photo_path` varchar(255) DEFAULT 'assets/image/default-avatar.png',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `org_team`
--

INSERT INTO `org_team` (`member_id`, `name`, `position`, `photo_path`, `display_order`, `is_active`) VALUES
(51, 'Tonic Madulid', 'Executive Director', 'assets/image/team/tonic_madulid_1766228197.jpg', 0, 1),
(58, 'Angelyn Sager', 'Finance Officer', 'assets/image/team/angelyn_sager_1766228291.jpg', 0, 1),
(59, 'Leo Jaminola', 'MEAL Officer', 'assets/image/team/leo_jaminola_1766228470.jpg', 0, 1),
(60, 'Anthony Jacob', 'Program Officer', 'assets/image/team/anthony_jacob_1766228609.jpg', 0, 1),
(61, 'Randy Naing', 'Regional Convenor', 'assets/image/team/randy_naing_1766228660.jpg', 0, 1),
(62, 'Adonis Salen', 'Local Coordinator', 'assets/image/team/adonis_salen_1766228770.jpg', 0, 1),
(63, 'Christian Jay Marmol', 'Member Staff', 'assets/image/team/christian_jay_marmol_1766228916.jpg', 0, 1),
(65, 'Byron Anjelu Calajate', 'Member Staff', 'assets/image/team/byron_anjelu_calajate_1766248203.jpg', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `proposal`
--

CREATE TABLE `proposal` (
  `ProposalID` int(20) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `ProposedDate` date DEFAULT NULL,
  `StartTime` time DEFAULT NULL,
  `EndTime` time DEFAULT NULL,
  `Venue` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `TargetParticipants` int(11) DEFAULT NULL,
  `EventType` varchar(100) DEFAULT NULL,
  `BudgetEstimate` decimal(10,2) DEFAULT NULL,
  `StaffRequired` int(11) DEFAULT NULL,
  `EquipmentNeeded` text DEFAULT NULL,
  `Objectives` text DEFAULT NULL,
  `PartnersSponsor` text DEFAULT NULL,
  `SubmittedByMemberID` int(11) NOT NULL,
  `SubmissionDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` enum('Approved','Pending','Rejected','Postponed') NOT NULL DEFAULT 'Pending',
  `ReviewByAdminID` int(11) DEFAULT NULL,
  `ReviewDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposal`
--

INSERT INTO `proposal` (`ProposalID`, `Title`, `ProposedDate`, `StartTime`, `EndTime`, `Venue`, `Description`, `TargetParticipants`, `EventType`, `BudgetEstimate`, `StaffRequired`, `EquipmentNeeded`, `Objectives`, `PartnersSponsor`, `SubmittedByMemberID`, `SubmissionDate`, `Status`, `ReviewByAdminID`, `ReviewDate`) VALUES
(44, 'Training', '2024-02-07', '08:00:00', '17:00:00', 'CCMS Lab 1', 'training', 15, 'workshop', 1500.00, 5, 'laptop', 'testing', '', 1, '2025-12-18 09:59:17', 'Approved', 1, '2025-12-21 08:47:12'),
(45, 'BAN PRIME WATER', '2025-12-21', '08:32:00', '20:31:00', 'IN FRONT OF PRIME WATER', 'RALLY PARA SA MAMAMAYAN', 1000, 'other', 5.00, 2, 'NONE', '1.\n2.\n3.\n4.', 'N/A', 1, '2025-12-20 12:33:37', 'Rejected', 1, '2025-12-21 11:10:07'),
(46, 'BAN PRIME WATER', '2025-12-21', '08:32:00', '20:31:00', 'IN FRONT OF PRIME WATER', 'RALLY PARA SA MAMAMAYAN', 1000, 'other', 5.00, 2, 'NONE', '1.\n2.\n3.\n4.', 'N/A', 1, '2025-12-20 12:33:37', 'Postponed', 1, '2025-12-21 09:37:10'),
(47, 'test', '2026-02-23', '04:44:00', '16:44:00', 'test', 'test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test', 50, 'YES (Youth Empowered Spaces)', 14998.00, 15, 'test', 'test', 'Daet Municipality', 1, '2025-12-21 08:46:04', 'Rejected', 1, '2025-12-21 11:10:08'),
(48, 'test', '2025-12-23', '17:27:00', '23:33:00', 'sample', 'test', 100, 'YES (Youth Empowered Spaces)', 1000.00, 5, 'test', 'test', '', 1, '2025-12-21 09:34:48', 'Rejected', 1, '2025-12-21 11:10:10');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `RegistrationID` int(20) NOT NULL,
  `MemberID` int(11) NOT NULL,
  `EventID` int(11) NOT NULL,
  `RegistrationDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `SettingID` int(11) NOT NULL,
  `SettingKey` varchar(100) NOT NULL,
  `SettingValue` varchar(255) DEFAULT NULL,
  `DataType` varchar(50) DEFAULT NULL,
  `LastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`SettingID`, `SettingKey`, `SettingValue`, `DataType`, `LastUpdated`) VALUES
(1, 'auto_approve_members', '0', 'boolean', '2025-12-21 10:19:30'),
(8, 'enable_member_registration', '1', 'boolean', '2025-12-21 10:19:20'),
(9, 'max_auto_approved_members', '1000', 'integer', '2025-12-06 10:43:04'),
(10, 'social_facebook', 'https://www.facebook.com/rebootphilippines', 'string', '2025-12-21 09:57:31'),
(11, 'social_instagram', 'https://www.instagram.com/rebootphinstitute/', 'string', '2025-12-21 07:38:19'),
(12, 'social_linkedin', 'https://www.linkedin.com/company/reboot-philippines/', 'string', '2025-12-21 07:38:42'),
(13, 'social_email', 'mailto:rebootphinstitute@gmail.com', 'string', '2025-12-21 07:39:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_us_content`
--
ALTER TABLE `about_us_content`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `about_us_points`
--
ALTER TABLE `about_us_points`
  ADD PRIMARY KEY (`point_id`);

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`AnnouncementID`) USING BTREE,
  ADD KEY `CreatedByAdminID` (`CreatedByAdminID`) USING BTREE,
  ADD KEY `LastModifiedBy` (`LastModifiedBy`) USING BTREE,
  ADD KEY `ProposalID` (`ProposalID`) USING BTREE;

--
-- Indexes for table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`ApplicationID`),
  ADD UNIQUE KEY `ApplicantEmail` (`ApplicantEmail`);

--
-- Indexes for table `applicationanswer`
--
ALTER TABLE `applicationanswer`
  ADD PRIMARY KEY (`AnswerID`),
  ADD KEY `ApplicationID` (`ApplicationID`),
  ADD KEY `QuestionID` (`QuestionID`);

--
-- Indexes for table `applicationquestionnaire`
--
ALTER TABLE `applicationquestionnaire`
  ADD PRIMARY KEY (`QuestionID`),
  ADD KEY `UpdatedByMemberID` (`UpdatedByMemberID`) USING BTREE;

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`CategoryID`);

--
-- Indexes for table `core_values`
--
ALTER TABLE `core_values`
  ADD PRIMARY KEY (`value_id`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`EventID`),
  ADD KEY `LastModifiedBy` (`LastModifiedBy`) USING BTREE,
  ADD KEY `ProposalID` (`ProposalID`) USING BTREE,
  ADD KEY `event_ibfk_2` (`CreatedByAdminID`);

--
-- Indexes for table `eventattendance`
--
ALTER TABLE `eventattendance`
  ADD PRIMARY KEY (`AttendanceID`),
  ADD UNIQUE KEY `RegistrationID` (`RegistrationID`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`FeedbackID`),
  ADD UNIQUE KEY `AttendanceID` (`AttendanceID`) USING BTREE;

--
-- Indexes for table `hero_section`
--
ALTER TABLE `hero_section`
  ADD PRIMARY KEY (`heroID`),
  ADD KEY `CreateByAdminID` (`CreateByAdminID`),
  ADD KEY `LastModifiedBy` (`LastModifiedBy`);

--
-- Indexes for table `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`ImageID`),
  ADD KEY `InitiativeID` (`InitiativeID`),
  ADD KEY `NewsletterID` (`NewsletterID`);

--
-- Indexes for table `initiatives`
--
ALTER TABLE `initiatives`
  ADD PRIMARY KEY (`InitiativeID`),
  ADD KEY `CategoryID` (`CategoryID`);

--
-- Indexes for table `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`MemberID`),
  ADD UNIQUE KEY `uk_application_id` (`ApplicationID`),
  ADD KEY `ApplicantID` (`ApplicationID`);

--
-- Indexes for table `member_benefits`
--
ALTER TABLE `member_benefits`
  ADD PRIMARY KEY (`BenefitsID`),
  ADD UNIQUE KEY `title` (`Title`),
  ADD KEY `CreatedByAdminID` (`CreateByAdminID`,`LastModifiedBy`),
  ADD KEY `LastModifiedBy` (`LastModifiedBy`);

--
-- Indexes for table `newsletter`
--
ALTER TABLE `newsletter`
  ADD PRIMARY KEY (`NewsletterID`),
  ADD KEY `CreatedByAdminID` (`CreatedByAdminID`) USING BTREE,
  ADD KEY `LastModifiedBy` (`LastModifiedBy`) USING BTREE;

--
-- Indexes for table `org_team`
--
ALTER TABLE `org_team`
  ADD PRIMARY KEY (`member_id`);

--
-- Indexes for table `proposal`
--
ALTER TABLE `proposal`
  ADD PRIMARY KEY (`ProposalID`),
  ADD KEY `ReviewByAdminID` (`ReviewByAdminID`) USING BTREE,
  ADD KEY `SubmittedByMemberID` (`SubmittedByMemberID`) USING BTREE;

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`RegistrationID`),
  ADD UNIQUE KEY `MemberID` (`MemberID`,`EventID`),
  ADD KEY `EventID` (`EventID`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`SettingID`),
  ADD UNIQUE KEY `SettingKey` (`SettingKey`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_us_content`
--
ALTER TABLE `about_us_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `about_us_points`
--
ALTER TABLE `about_us_points`
  MODIFY `point_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `AnnouncementID` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `application`
--
ALTER TABLE `application`
  MODIFY `ApplicationID` int(50) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `applicationanswer`
--
ALTER TABLE `applicationanswer`
  MODIFY `AnswerID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `applicationquestionnaire`
--
ALTER TABLE `applicationquestionnaire`
  MODIFY `QuestionID` int(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `core_values`
--
ALTER TABLE `core_values`
  MODIFY `value_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `event`
--
ALTER TABLE `event`
  MODIFY `EventID` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `eventattendance`
--
ALTER TABLE `eventattendance`
  MODIFY `AttendanceID` int(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hero_section`
--
ALTER TABLE `hero_section`
  MODIFY `heroID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `images`
--
ALTER TABLE `images`
  MODIFY `ImageID` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `initiatives`
--
ALTER TABLE `initiatives`
  MODIFY `InitiativeID` int(50) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `MemberID` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `member_benefits`
--
ALTER TABLE `member_benefits`
  MODIFY `BenefitsID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `newsletter`
--
ALTER TABLE `newsletter`
  MODIFY `NewsletterID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `org_team`
--
ALTER TABLE `org_team`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `proposal`
--
ALTER TABLE `proposal`
  MODIFY `ProposalID` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `RegistrationID` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `SettingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcement`
--
ALTER TABLE `announcement`
  ADD CONSTRAINT `announcement_ibfk_4` FOREIGN KEY (`ProposalID`) REFERENCES `proposal` (`ProposalID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `announcement_ibfk_5` FOREIGN KEY (`CreatedByAdminID`) REFERENCES `member` (`MemberID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `announcement_ibfk_6` FOREIGN KEY (`LastModifiedBy`) REFERENCES `member` (`MemberID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `applicationanswer`
--
ALTER TABLE `applicationanswer`
  ADD CONSTRAINT `applicationanswer_ibfk_1` FOREIGN KEY (`ApplicationID`) REFERENCES `application` (`ApplicationID`),
  ADD CONSTRAINT `applicationanswer_ibfk_2` FOREIGN KEY (`QuestionID`) REFERENCES `applicationquestionnaire` (`QuestionID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `applicationquestionnaire`
--
ALTER TABLE `applicationquestionnaire`
  ADD CONSTRAINT `applicationquestionnaire_ibfk_1` FOREIGN KEY (`UpdatedByMemberID`) REFERENCES `member` (`MemberID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_2` FOREIGN KEY (`CreatedByAdminID`) REFERENCES `member` (`MemberID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `event_ibfk_5` FOREIGN KEY (`ProposalID`) REFERENCES `proposal` (`ProposalID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `eventattendance`
--
ALTER TABLE `eventattendance`
  ADD CONSTRAINT `eventattendance_ibfk_1` FOREIGN KEY (`RegistrationID`) REFERENCES `registration` (`RegistrationID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`AttendanceID`) REFERENCES `eventattendance` (`AttendanceID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `hero_section`
--
ALTER TABLE `hero_section`
  ADD CONSTRAINT `hero_section_ibfk_1` FOREIGN KEY (`CreateByAdminID`) REFERENCES `member` (`MemberID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `hero_section_ibfk_2` FOREIGN KEY (`LastModifiedBy`) REFERENCES `member` (`MemberID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `images_ibfk_1` FOREIGN KEY (`InitiativeID`) REFERENCES `initiatives` (`InitiativeID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `images_ibfk_2` FOREIGN KEY (`NewsletterID`) REFERENCES `newsletter` (`NewsletterID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `initiatives`
--
ALTER TABLE `initiatives`
  ADD CONSTRAINT `initiatives_ibfk_1` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `member`
--
ALTER TABLE `member`
  ADD CONSTRAINT `member_ibfk_1` FOREIGN KEY (`ApplicationID`) REFERENCES `application` (`ApplicationID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `member_benefits`
--
ALTER TABLE `member_benefits`
  ADD CONSTRAINT `member_benefits_ibfk_1` FOREIGN KEY (`CreateByAdminID`) REFERENCES `member` (`MemberID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `member_benefits_ibfk_2` FOREIGN KEY (`LastModifiedBy`) REFERENCES `member` (`MemberID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `newsletter`
--
ALTER TABLE `newsletter`
  ADD CONSTRAINT `newsletter_ibfk_1` FOREIGN KEY (`CreatedByAdminID`) REFERENCES `member` (`MemberID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `newsletter_ibfk_2` FOREIGN KEY (`LastModifiedBy`) REFERENCES `member` (`MemberID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `org_team`
--
ALTER TABLE `org_team`
  ADD CONSTRAINT `org_team_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`MemberID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `proposal`
--
ALTER TABLE `proposal`
  ADD CONSTRAINT `proposal_ibfk_1` FOREIGN KEY (`SubmittedByMemberID`) REFERENCES `member` (`MemberID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `proposal_ibfk_2` FOREIGN KEY (`ReviewByAdminID`) REFERENCES `member` (`MemberID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `registration`
--
ALTER TABLE `registration`
  ADD CONSTRAINT `registration_ibfk_1` FOREIGN KEY (`MemberID`) REFERENCES `member` (`MemberID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `registration_ibfk_2` FOREIGN KEY (`EventID`) REFERENCES `event` (`EventID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
