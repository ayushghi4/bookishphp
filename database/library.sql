SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `tblusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `UserId` varchar(100) DEFAULT NULL,
  `FullName` varchar(120) DEFAULT NULL,
  `EmailId` varchar(120) DEFAULT NULL,
  `Password` varchar(120) DEFAULT NULL,
  `Status` int(1) DEFAULT 1,
  `RegDate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `EmailId` (`EmailId`),
  UNIQUE KEY `UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tblauthors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `AuthorName` varchar(159) DEFAULT NULL,
  `creationDate` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tblcategory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `CategoryName` varchar(150) NOT NULL,
  `Status` int(1) DEFAULT 1,
  `CreationDate` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tblbooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `BookName` varchar(255) DEFAULT NULL,
  `CatId` int(11) DEFAULT NULL,
  `AuthorId` int(11) DEFAULT NULL,
  `ISBNNumber` varchar(25) DEFAULT NULL,
  `BookPrice` decimal(10,2) DEFAULT NULL,
  `bookImage` varchar(250) NOT NULL,
  `epub_file_path` varchar(255) DEFAULT NULL,
  `RegDate` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tblreadinghistory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `UserId` int(11) NOT NULL,
  `BookId` int(11) NOT NULL,
  `LastPage` varchar(255) NOT NULL,
  `ReadDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` varchar(50) NOT NULL DEFAULT 'reading',
  PRIMARY KEY (`id`),
  KEY `UserId` (`UserId`),
  KEY `BookId` (`BookId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tblbookmarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `BookId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `Location` varchar(255) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `BookId` (`BookId`),
  KEY `UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tblratings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `BookId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `Rating` int(1) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_book_rating` (`BookId`,`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tblreviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `BookId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `Review` text NOT NULL,
  `Status` int(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `BookId` (`BookId`),
  KEY `UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `tblbooks`
  ADD CONSTRAINT `tblbooks_ibfk_1` FOREIGN KEY (`CatId`) REFERENCES `tblcategory` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tblbooks_ibfk_2` FOREIGN KEY (`AuthorId`) REFERENCES `tblauthors` (`id`) ON DELETE SET NULL;

ALTER TABLE `tblbookmarks`
  ADD CONSTRAINT `tblbookmarks_ibfk_1` FOREIGN KEY (`BookId`) REFERENCES `tblbooks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tblbookmarks_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `tblusers` (`id`) ON DELETE CASCADE;

ALTER TABLE `tblreadinghistory`
  ADD CONSTRAINT `tblreadinghistory_ibfk_1` FOREIGN KEY (`BookId`) REFERENCES `tblbooks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tblreadinghistory_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `tblusers` (`id`) ON DELETE CASCADE;

ALTER TABLE `tblratings`
  ADD CONSTRAINT `tblratings_ibfk_1` FOREIGN KEY (`BookId`) REFERENCES `tblbooks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tblratings_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `tblusers` (`id`) ON DELETE CASCADE;

ALTER TABLE `tblreviews`
  ADD CONSTRAINT `tblreviews_ibfk_1` FOREIGN KEY (`BookId`) REFERENCES `tblbooks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tblreviews_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `tblusers` (`id`) ON DELETE CASCADE;
