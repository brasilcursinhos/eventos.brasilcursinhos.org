-- drop database if exists eventosbc;
-- create database if not exists eventosbc;
-- use eventosbc;

-- Sanvando estados das variáveis do sistema antes da criação das tabelas
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='-03:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Define o charset padrão para UTF-8 de 4 bytes
/*!40101 SET character_set_client = utf8mb4 */;

-- Excluindo tabelas
DROP TABLE IF EXISTS `SOCIAL_DISCOUNT_REQUESTS`;
DROP TABLE IF EXISTS `TRANSACTION_BENEFICIARIES`;
DROP TABLE IF EXISTS `FINANCIAL_TRANSACTIONS`;
DROP TABLE IF EXISTS `REGISTRATION_SESSIONS`;
DROP TABLE IF EXISTS `REGISTRATION_DATES`;
DROP TABLE IF EXISTS `EVENT_REGISTRATIONS`;
DROP TABLE IF EXISTS `TICKET_ALLOWED_DATES`;
DROP TABLE IF EXISTS `EVENT_TICKETS`;
DROP TABLE IF EXISTS `ACTIVITY_SESSIONS`;
DROP TABLE IF EXISTS `EVENT_SCHEDULES`;
DROP TABLE IF EXISTS `EVENT_DATES`;
DROP TABLE IF EXISTS `EVENTS`;
DROP TABLE IF EXISTS `VERIFICATION_CODES`;
DROP TABLE IF EXISTS `PERSONAL_DATA`;
DROP TABLE IF EXISTS `USER_ROLES`;
DROP TABLE IF EXISTS `USERS`;
DROP TABLE IF EXISTS `FILES`;

-- Criação das tabelas
CREATE TABLE IF NOT EXISTS `FILES` (
	`idFile` INT NOT NULL AUTO_INCREMENT,
	`originalName` VARCHAR(256) NOT NULL,
    `storedName` VARCHAR(64) NOT NULL,
    `mimeType` VARCHAR(128) NOT NULL,
    `size` INT NOT NULL,
    `createdAt` DATETIME NOT NULL,
	PRIMARY KEY (`idFile`),
    UNIQUE INDEX `idxFilesStoredName` (`storedName` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `USERS` (
    `idUser` INT NOT NULL AUTO_INCREMENT,
    `cpfHash` VARBINARY(64) NOT NULL,
    `username` VARBINARY(128) NULL DEFAULT NULL,
    `usernameHash` VARBINARY(64) NULL DEFAULT NULL,
    `passwordHash` VARCHAR(256) NULL DEFAULT NULL,
    `type` INT NOT NULL,
    `status` INT NOT NULL,
    `loginAttempts` INT NOT NULL DEFAULT 0,
    `tokenVersion` INT NOT NULL DEFAULT 1,
    `mfaSecret` VARBINARY(128) NULL DEFAULT NULL,
    `createdAt` DATETIME NOT NULL,
    `updatedAt` DATETIME NOT NULL,
    PRIMARY KEY (`idUser`),
    UNIQUE INDEX `idxUserCpfHash` (`cpfHash`),
    UNIQUE INDEX `idxUserUsernameHash` (`usernameHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `USER_ROLES` (
	`idUserRole` INT NOT NULL AUTO_INCREMENT,
	`role` INT NOT NULL,
    `idUser` INT NOT NULL,
    `createdAt` DATETIME NOT NULL,
	PRIMARY KEY (`idUserRole`),
    CONSTRAINT `fkUserRolesUsers` FOREIGN KEY (`idUser`) REFERENCES `USERS`(`idUser`),
    UNIQUE INDEX `idxUserRolesUserRole` (`idUser`, `role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PERSONAL_DATA` (
	`idPersonalData` INT NOT NULL AUTO_INCREMENT,
	`fullName` VARBINARY(256) NOT NULL,
	`useSocialName` BOOLEAN NOT NULL DEFAULT FALSE,
    `socialName` VARBINARY(256) NULL DEFAULT NULL,
    `nickname` VARBINARY(128) NOT NULL,
    `pronouns` BLOB NOT NULL,
    `genderIdentity` VARBINARY(64) NOT NULL,
    `ethnicity` VARBINARY(64) NOT NULL,
    `cpf` VARBINARY(64) NOT NULL,
    `birthDate` VARBINARY(64) NOT NULL,
    `email` VARBINARY(256) NOT NULL,
    `emailHash` VARBINARY(64) NOT NULL,
    `phone` VARBINARY(64) NOT NULL,
    `phoneHash` VARBINARY(64) NOT NULL,
    `emergencyData` BLOB NOT NULL,
    `address` BLOB NULL DEFAULT NULL,
    `idUser` INT NOT NULL,
    `createdAt` DATETIME NOT NULL,
    `updatedAt` DATETIME NOT NULL,
	PRIMARY KEY (`idPersonalData`),
    CONSTRAINT `fkPersonalDataUsers` FOREIGN KEY (`idUser`) REFERENCES `USERS`(`idUser`),
    UNIQUE INDEX `idxPersonalDataUser` (`idUser`),
    UNIQUE INDEX `idxPersonalDataEmailHash` (`emailHash`),
    UNIQUE INDEX `idxPersonalDataPhoneHash` (`phoneHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `VERIFICATION_CODES` (
    `idVerificationCode` INT NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(128) NOT NULL,
    `sentAt` DATETIME NOT NULL,
    `confirmedAt` DATETIME NULL DEFAULT NULL,
    `idUser` INT NOT NULL,
    PRIMARY KEY (`idVerificationCode`),
    CONSTRAINT `fkVerificationCodesUsers` FOREIGN KEY (`idUser`) REFERENCES `USERS`(`idUser`),
    UNIQUE INDEX `idxVerificationCodesCode` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `EVENTS` (
    `idEvent` INT NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(64) NOT NULL,
    `edition` INT NOT NULL,
    `year` YEAR NOT NULL,
    `location` VARCHAR(64) NOT NULL,
    `modality` INT NOT NULL,
    `type` INT NOT NULL,
    `status` INT NOT NULL,
	`registrationOpenAt` DATETIME NOT NULL,
    `registrationCloseAt` DATETIME NOT NULL,
    `createdAt` DATETIME NOT NULL,
    `updatedAt` DATETIME NOT NULL,
    PRIMARY KEY (`idEvent`),
    UNIQUE INDEX `idxEventsTypeYearEdition` (`type` ASC, `year` DESC, `edition` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `EVENTS`(`idEvent`, `title`, `edition`, `year`, `location`, `modality`, `type`, `status`, `registrationOpenAt`, `registrationCloseAt`, `createdAt`, `updatedAt`) VALUES 
(1, 'ENCUP 2026', 11, '2026', 'FGV - São Paulo/SP', 1, 2, 2, '2026-07-10 20:00:00', '2026-08-05 23:59:59', NOW(), NOW());

CREATE TABLE IF NOT EXISTS `EVENT_DATES` (
    `idEventDate` INT NOT NULL AUTO_INCREMENT,
    `date` DATE NOT NULL,
    `capacity` INT NOT NULL,
    `idEvent` INT NOT NULL,
    `createdAt` DATETIME NOT NULL,
    `updatedAt` DATETIME NOT NULL,
    PRIMARY KEY (`idEventDate`),
    CONSTRAINT `fkEventDatesEvents` FOREIGN KEY (`idEvent`) REFERENCES `EVENTS`(`idEvent`),
    UNIQUE INDEX `idxEventDatesEventDate` (`idEvent` DESC, `date` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `EVENT_DATES`(`idEventDate`, `date`, `capacity`, `idEvent`, `createdAt`, `updatedAt`) VALUES 
(1, '2026-08-15', 300, 1, NOW(), NOW()),
(2, '2026-08-16', 300, 1, NOW(), NOW());

CREATE TABLE IF NOT EXISTS `EVENT_SCHEDULES` (
    `idEventSchedule` INT NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(64) NOT NULL,
    `startTime` TIME NOT NULL,
    `endTime` TIME NOT NULL,
    `requireSelection` BOOLEAN NOT NULL DEFAULT FALSE,
    `registrationOpenAt` DATETIME NULL DEFAULT NULL,
    `registrationCloseAt` DATETIME NULL DEFAULT NULL,
    `idEventDate` INT NOT NULL,
    `createdAt` DATETIME NOT NULL,
    `updatedAt` DATETIME NOT NULL,
    PRIMARY KEY (`idEventSchedule`),
    CONSTRAINT `fkEventSchedulesEventDates` FOREIGN KEY (`idEventDate`) REFERENCES `EVENT_DATES`(`idEventDate`),
    UNIQUE INDEX `idxEventSchedulesEventDateStartTime` (`idEventDate` DESC, `startTime` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ACTIVITY_SESSIONS` (
    `idActivitySession` INT NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(64) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `location` VARCHAR(64) NOT NULL,
    `capacity` INT NOT NULL,
    `idEventSchedule` INT NOT NULL,
    `createdAt` DATETIME NOT NULL,
    `updatedAt` DATETIME NOT NULL,
    PRIMARY KEY (`idActivitySession`),
    CONSTRAINT `fkActivitySessionsEventSchedules` FOREIGN KEY (`idEventSchedule`) REFERENCES `EVENT_SCHEDULES`(`idEventSchedule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `EVENT_TICKETS` (
    `idEventTicket` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(64) NOT NULL,
    `price` DECIMAL(6, 2) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `isActive` BOOLEAN NOT NULL DEFAULT TRUE,
    `idEvent` INT NOT NULL,
    `createdAt` DATETIME NOT NULL,
    `updatedAt` DATETIME NOT NULL,
    PRIMARY KEY (`idEventTicket`),
    CONSTRAINT `fkEventTicketsEvents` FOREIGN KEY (`idEvent`) REFERENCES `EVENTS`(`idEvent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `EVENT_TICKETS`(`idEventTicket`, `name`, `price`, `idEvent`, `createdAt`, `updatedAt`) VALUES 
(1, 'Ingresso 2 dias (sábado e domingo)', 75.00, 1, NOW(), NOW()),
(2, 'Ingresso 1 dia (sábado)', 55.00, 1, NOW(), NOW()),
(3, 'Ingresso 1 dia (domingo)', 55.00, 1, NOW(), NOW());

CREATE TABLE IF NOT EXISTS `TICKET_ALLOWED_DATES` (
    `idTicketAllowedDates` INT NOT NULL AUTO_INCREMENT,
    `idEventTicket` INT NOT NULL,
    `idEventDate` INT NOT NULL,
    `createdAt` DATETIME NOT NULL,
    PRIMARY KEY (`idTicketAllowedDates`),
    CONSTRAINT `fkTicketAllowedDatesEventTickets` FOREIGN KEY (`idEventDate`) REFERENCES `EVENT_DATES`(`idEventDate`),
    CONSTRAINT `fkTicketAllowedDatesEventDates` FOREIGN KEY (`idEventTicket`) REFERENCES `EVENT_TICKETS`(`idEventTicket`),
    UNIQUE INDEX `idxTicketAllowedDatesEventTicketEventDate` (`idEventTicket`, `idEventDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `TICKET_ALLOWED_DATES`(`idTicketAllowedDates`, `idEventTicket`, `idEventDate`, `createdAt`) VALUES 
(1, 1, 1, NOW()),
(2, 1, 2, NOW()),
(3, 2, 1, NOW()),
(4, 3, 2, NOW());

CREATE TABLE IF NOT EXISTS `EVENT_REGISTRATIONS` (
	`idEventRegistration` INT NOT NULL AUTO_INCREMENT,
	`registration` VARBINARY(64) NOT NULL,
	`registrationHash` VARBINARY(64) NOT NULL,
    `type` INT NOT NULL,
    `status` INT NOT NULL,
    `basePrice` DECIMAL(6, 2) NOT NULL,
    `amountDue` DECIMAL(6, 2) NOT NULL,
    `organizationName` VARCHAR(64) NOT NULL,
    `additionalData` JSON NULL DEFAULT NULL,
    `idEvent` INT NOT NULL,
    `idEventTicket` INT NOT NULL,
    `idUser` INT NOT NULL,
    `idProofAutorization` INT NULL DEFAULT NULL,
    `createdAt` DATETIME NOT NULL,
    `updatedAt` DATETIME NOT NULL,
	PRIMARY KEY (`idEventRegistration`),
    CONSTRAINT `fkEventRegistrationsEvents` FOREIGN KEY (`idEvent`) REFERENCES `EVENTS`(`idEvent`),
    CONSTRAINT `fkEventRegistrationsEventTickets` FOREIGN KEY (`idEventTicket`) REFERENCES `EVENT_TICKETS`(`idEventTicket`),
    CONSTRAINT `fkEventRegistrationsUsers` FOREIGN KEY (`idUser`) REFERENCES `USERS`(`idUser`),
    CONSTRAINT `fkEventRegistrationsFiles` FOREIGN KEY (`idProofAutorization`) REFERENCES `FILES`(`idFile`),
    UNIQUE INDEX `idxEventRegistrationsEventUser` (`idEvent` DESC, `idUser` ASC),
    UNIQUE INDEX `idxEventRegistrationsRegistrationHash` (`registrationHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `REGISTRATION_DATES` (
    `idRegistrationDate` INT NOT NULL AUTO_INCREMENT,
    `idEventRegistration` INT NOT NULL,
    `idEventDate` INT NOT NULL,
    `createdAt` DATETIME NOT NULL,
    PRIMARY KEY (`idRegistrationDate`),
    CONSTRAINT `fkRegistrationDatesEventRegistrations` FOREIGN KEY (`idEventRegistration`) REFERENCES `EVENT_REGISTRATIONS`(`idEventRegistration`),
    CONSTRAINT `fkRegistrationDatesEventDates` FOREIGN KEY (`idEventDate`) REFERENCES `EVENT_DATES`(`idEventDate`),
    UNIQUE INDEX `idxRegistrationDatesEventRegistrationEventDate` (`idEventRegistration`, `idEventDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `REGISTRATION_SESSIONS` (
    `idRegistrationSession` INT NOT NULL AUTO_INCREMENT,
    `idEventRegistration` INT NOT NULL,
    `idActivitySession` INT NOT NULL,
    `createdAt` DATETIME NOT NULL,
    PRIMARY KEY (`idRegistrationSession`),
    CONSTRAINT `fkRegistrationSessionsEventRegistrations` FOREIGN KEY (`idEventRegistration`) REFERENCES `EVENT_REGISTRATIONS`(`idEventRegistration`),
    CONSTRAINT `fkRegistrationSessionsActivitySession` FOREIGN KEY (`idActivitySession`) REFERENCES `ACTIVITY_SESSIONS`(`idActivitySession`),
    UNIQUE INDEX `idxRegistrationSessionsEventRegistrationEventDate` (`idEventRegistration`, `idActivitySession`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `FINANCIAL_TRANSACTIONS` (
	`idFinancialTransaction` INT NOT NULL AUTO_INCREMENT,
    `status` INT NOT NULL,
    `justification` TEXT NULL DEFAULT NULL,
    `totalAmount` DECIMAL(8, 2) NOT NULL,
    `paymentMethod` INT NOT NULL,
    `providerTransactionId` VARBINARY(128) NULL DEFAULT NULL,
	`providerTransactionIdHash` VARBINARY(64) NULL DEFAULT NULL,
    `idProofTransaction` INT NULL DEFAULT NULL,
    `idUser` INT NOT NULL,
    `idEvent` INT NOT NULL,
    `idEventRegistration` INT NULL DEFAULT NULL,
    `createdAt` DATETIME NOT NULL,
    `updatedAt` DATETIME NOT NULL,
	PRIMARY KEY (`idFinancialTransaction`),
    CONSTRAINT `fkFinancialTransactionsFiles` FOREIGN KEY (`idProofTransaction`) REFERENCES `FILES`(`idFile`),
    CONSTRAINT `fkFinancialTransactionsUsers` FOREIGN KEY (`idUser`) REFERENCES `USERS`(`idUser`),
    CONSTRAINT `fkFinancialTransactionsEvents` FOREIGN KEY (`idEvent`) REFERENCES `EVENTS`(`idEvent`),
    CONSTRAINT `fkFinancialTransactionsEventRegistrations` FOREIGN KEY (`idEventRegistration`) REFERENCES `EVENT_REGISTRATIONS`(`idEventRegistration`),
    UNIQUE INDEX `idxFinancialTransactionsProviderTransactionIdHash` (`providerTransactionIdHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `TRANSACTION_BENEFICIARIES` (
	`idTransactionBeneficiary` INT NOT NULL AUTO_INCREMENT,
    `idFinancialTransaction` INT NULL DEFAULT NULL,
    `idEventTicket` INT NOT NULL,
	`cpfHash` VARBINARY(64) NULL DEFAULT NULL,
    `createdAt` DATETIME NOT NULL,
	PRIMARY KEY (`idTransactionBeneficiary`),
    CONSTRAINT `fkTransactionBeneficiariesFinancialTransactions` FOREIGN KEY (`idFinancialTransaction`) REFERENCES `FINANCIAL_TRANSACTIONS`(`idFinancialTransaction`),
    CONSTRAINT `fkTransactionBeneficiariesEventTickets` FOREIGN KEY (`idEventTicket`) REFERENCES `EVENT_TICKETS`(`idEventTicket`),
    UNIQUE INDEX `idxTransactionBeneficiariesdFinancialTransactionCpfHash` (`idFinancialTransaction`, `cpfHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `SOCIAL_DISCOUNT_REQUESTS` (
	`idSocialDiscountRequest` INT NOT NULL AUTO_INCREMENT,
    `status` INT NOT NULL,
    `justification` TEXT NULL DEFAULT NULL,
    `grantedDiscountPercentage` DECIMAL(5, 2) NULL DEFAULT NULL,
    `idProofRequest` INT NOT NULL,
    `idEventTicket` INT NOT NULL,
    `idEventRegistration` INT NOT NULL,
    `createdAt` DATETIME NOT NULL,
    `updatedAt` DATETIME NOT NULL,
	PRIMARY KEY (`idSocialDiscountRequest`),
    CONSTRAINT `fkSocialDiscountRequestsEventTickets` FOREIGN KEY (`idEventTicket`) REFERENCES `EVENT_TICKETS`(`idEventTicket`),
    CONSTRAINT `fkSocialDiscountRequestsEventRegistrations` FOREIGN KEY (`idEventRegistration`) REFERENCES `EVENT_REGISTRATIONS`(`idEventRegistration`),
    CONSTRAINT `fkSocialDiscountRequestsFiles` FOREIGN KEY (`idProofRequest`) REFERENCES `FILES`(`idFile`),
    UNIQUE INDEX `idxSocialDiscountRequestsEventRegistration` (`idEventRegistration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restaura as variáveis originais do sistema
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;