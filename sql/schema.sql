SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `site`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `site` ;

CREATE  TABLE IF NOT EXISTS `site` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NOT NULL ,
  `timezone` ENUM('Canada/Pacific','Canada/Mountain','Canada/Central','Canada/Eastern','Canada/Atlantic','Canada/Newfoundland') NOT NULL ,
  `operators_expected` INT UNSIGNED NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_name` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `participant`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `participant` ;

CREATE  TABLE IF NOT EXISTS `participant` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `first_name` VARCHAR(45) NOT NULL ,
  `last_name` VARCHAR(45) NOT NULL ,
  `language` ENUM('en','fr') NULL DEFAULT NULL ,
  `hin` VARCHAR(45) NULL DEFAULT NULL ,
  `status` ENUM('deceased', 'deaf', 'mentally unfit') NULL DEFAULT NULL ,
  `site_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'If not null then force all calls to this participant to the site.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  CONSTRAINT `fk_participant_site`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `user`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `user` ;

CREATE  TABLE IF NOT EXISTS `user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NOT NULL ,
  `active` TINYINT(1)  NOT NULL DEFAULT true ,
  `theme` VARCHAR(45) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_name` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `role`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `role` ;

CREATE  TABLE IF NOT EXISTS `role` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_name` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `qnaire`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `qnaire` ;

CREATE  TABLE IF NOT EXISTS `qnaire` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  `description` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `phase`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `phase` ;

CREATE  TABLE IF NOT EXISTS `phase` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `qnaire_id` INT UNSIGNED NOT NULL ,
  `sid` INT NOT NULL COMMENT 'limesurvey surveys.sid' ,
  `stage` SMALLINT UNSIGNED NOT NULL ,
  `repeated` TINYINT(1)  NOT NULL DEFAULT false ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_qnaire_id` (`qnaire_id` ASC) ,
  UNIQUE INDEX `uq_qnaire_id_stage` (`qnaire_id` ASC, `stage` ASC) ,
  CONSTRAINT `fk_phase_qnaire`
    FOREIGN KEY (`qnaire_id` )
    REFERENCES `qnaire` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'aka: qnaire_has_survey' ;


-- -----------------------------------------------------
-- Table `province`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `province` ;

CREATE  TABLE IF NOT EXISTS `province` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NOT NULL ,
  `abbreviation` VARCHAR(5) NOT NULL ,
  `site_id` INT UNSIGNED NULL COMMENT 'Which site manages participants.' ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_name` (`name` ASC) ,
  UNIQUE INDEX `uq_abbreviation` (`abbreviation` ASC) ,
  CONSTRAINT `fk_province_site`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `contact`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `contact` ;

CREATE  TABLE IF NOT EXISTS `contact` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `active` TINYINT(1)  NOT NULL DEFAULT true ,
  `rank` INT NOT NULL ,
  `type` ENUM('home','home2','work','work2','cell','cell2','other') NOT NULL DEFAULT 'home' ,
  `phone` VARCHAR(20) NULL DEFAULT NULL ,
  `address1` VARCHAR(512) NULL DEFAULT NULL ,
  `address2` VARCHAR(512) NULL DEFAULT NULL ,
  `city` VARCHAR(45) NULL DEFAULT NULL COMMENT 'If outside Canada, this should contain state and/or region as well.' ,
  `province_id` INT UNSIGNED NULL DEFAULT NULL ,
  `country` VARCHAR(20) NULL DEFAULT NULL ,
  `postcode` VARCHAR(20) NULL DEFAULT NULL ,
  `note` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  UNIQUE INDEX `uq_participant_id_active_rank` (`participant_id` ASC, `rank` ASC) ,
  INDEX `fk_province_id` (`province_id` ASC) ,
  CONSTRAINT `fk_contact_participant`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_contact_province`
    FOREIGN KEY (`province_id` )
    REFERENCES `province` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `interview`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `interview` ;

CREATE  TABLE IF NOT EXISTS `interview` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `phase_id` INT UNSIGNED NOT NULL COMMENT 'What phase is the interview currently in?' ,
  `require_supervisor` TINYINT(1)  NOT NULL DEFAULT false ,
  `completed` TINYINT(1)  NOT NULL DEFAULT false ,
  `duplicate_phase_id` INT UNSIGNED NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_phase_id` (`phase_id` ASC) ,
  INDEX `fk_duplicate_phase_id` (`duplicate_phase_id` ASC) ,
  CONSTRAINT `fk_interview_participant`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_phase_id`
    FOREIGN KEY (`phase_id` )
    REFERENCES `phase` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_duplicate_phase_id`
    FOREIGN KEY (`duplicate_phase_id` )
    REFERENCES `phase` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB, 
COMMENT = 'aka: qnaire_has_participant' ;


-- -----------------------------------------------------
-- Table `queue`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `queue` ;

CREATE  TABLE IF NOT EXISTS `queue` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NOT NULL ,
  `view` VARCHAR(45) NOT NULL ,
  `description` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_name` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `assignment`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `assignment` ;

CREATE  TABLE IF NOT EXISTS `assignment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL COMMENT 'The site from which the user was assigned.' ,
  `interview_id` INT UNSIGNED NOT NULL ,
  `queue_id` INT UNSIGNED NOT NULL COMMENT 'The queue that the assignment came from.' ,
  `start_time` DATETIME NOT NULL ,
  `end_time` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_interview_id` (`interview_id` ASC) ,
  INDEX `fk_queue_id` (`queue_id` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  CONSTRAINT `fk_assignment_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_interview`
    FOREIGN KEY (`interview_id` )
    REFERENCES `interview` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_queue`
    FOREIGN KEY (`queue_id` )
    REFERENCES `queue` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_site`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `appointment`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `appointment` ;

CREATE  TABLE IF NOT EXISTS `appointment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `contact_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Which contact to use.' ,
  `date` DATETIME NOT NULL ,
  `completed` TINYINT(1)  NOT NULL DEFAULT false COMMENT 'Whether the appointment has been met.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_contact_id` (`contact_id` ASC) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  CONSTRAINT `fk_appointment_contact`
    FOREIGN KEY (`contact_id` )
    REFERENCES `contact` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_appointment_participant`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `phone_call`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `phone_call` ;

CREATE  TABLE IF NOT EXISTS `phone_call` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `assignment_id` INT UNSIGNED NOT NULL ,
  `contact_id` INT UNSIGNED NOT NULL ,
  `appointment_id` INT UNSIGNED NULL DEFAULT NULL ,
  `start_time` DATETIME NOT NULL COMMENT 'The time the call started.' ,
  `end_time` DATETIME NULL DEFAULT NULL COMMENT 'The time the call endede.' ,
  `status` ENUM('contacted', 'busy','no answer','machine message','machine no message','fax','disconnected','wrong number','language') NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_contact_id` (`contact_id` ASC) ,
  INDEX `fk_assignment_id` (`assignment_id` ASC) ,
  INDEX `fk_appointment_id` (`appointment_id` ASC) ,
  CONSTRAINT `fk_phone_call_contact`
    FOREIGN KEY (`contact_id` )
    REFERENCES `contact` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_phone_call_assignment`
    FOREIGN KEY (`assignment_id` )
    REFERENCES `assignment` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_phone_call_appointment`
    FOREIGN KEY (`appointment_id` )
    REFERENCES `appointment` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `operation`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `operation` ;

CREATE  TABLE IF NOT EXISTS `operation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `type` ENUM('action','widget') NOT NULL ,
  `subject` VARCHAR(45) NOT NULL ,
  `name` VARCHAR(45) NOT NULL ,
  `restricted` TINYINT(1)  NOT NULL DEFAULT true ,
  `description` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_type_subject_name` (`type` ASC, `subject` ASC, `name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `role_has_operation`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `role_has_operation` ;

CREATE  TABLE IF NOT EXISTS `role_has_operation` (
  `role_id` INT UNSIGNED NOT NULL ,
  `operation_id` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`role_id`, `operation_id`) ,
  INDEX `fk_role_id` (`role_id` ASC) ,
  INDEX `fk_operation_id` (`operation_id` ASC) ,
  CONSTRAINT `fk_role_has_operation_role`
    FOREIGN KEY (`role_id` )
    REFERENCES `role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_role_has_operation_operation`
    FOREIGN KEY (`operation_id` )
    REFERENCES `operation` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `consent`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `consent` ;

CREATE  TABLE IF NOT EXISTS `consent` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `event` ENUM('verbal accept','verbal deny','written accept','written deny','retract','mail request','mail sent') NOT NULL ,
  `date` DATE NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  CONSTRAINT `fk_consent_participant`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sample`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sample` ;

CREATE  TABLE IF NOT EXISTS `sample` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  `qnaire_id` INT UNSIGNED NULL DEFAULT NULL ,
  `description` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_name` (`name` ASC) ,
  INDEX `fk_qnaire_id` (`qnaire_id` ASC) ,
  CONSTRAINT `fk_sample_qnaire`
    FOREIGN KEY (`qnaire_id` )
    REFERENCES `qnaire` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sample_has_participant`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sample_has_participant` ;

CREATE  TABLE IF NOT EXISTS `sample_has_participant` (
  `sample_id` INT UNSIGNED NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`sample_id`, `participant_id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_sample_id` (`sample_id` ASC) ,
  CONSTRAINT `fk_sample_has_participant_sample`
    FOREIGN KEY (`sample_id` )
    REFERENCES `sample` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_sample_has_participant_participant`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `availability`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `availability` ;

CREATE  TABLE IF NOT EXISTS `availability` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `monday` TINYINT(1)  NOT NULL DEFAULT false ,
  `tuesday` TINYINT(1)  NOT NULL DEFAULT false ,
  `wednesday` TINYINT(1)  NOT NULL DEFAULT false ,
  `thursday` TINYINT(1)  NOT NULL DEFAULT false ,
  `friday` TINYINT(1)  NOT NULL DEFAULT false ,
  `saturday` TINYINT(1)  NOT NULL DEFAULT false ,
  `sunday` TINYINT(1)  NOT NULL DEFAULT false ,
  `start_time` TIME NOT NULL ,
  `end_time` TIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  CONSTRAINT `fk_availability_participant`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `qnaire_note`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `qnaire_note` ;

CREATE  TABLE IF NOT EXISTS `qnaire_note` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `qnaire_id` INT UNSIGNED NOT NULL ,
  `sticky` TINYINT(1)  NOT NULL DEFAULT false ,
  `date` TIMESTAMP NOT NULL ,
  `note` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_qnaire_id` (`qnaire_id` ASC) ,
  CONSTRAINT `fk_qnaire_note_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_qnaire_note_qnaire`
    FOREIGN KEY (`qnaire_id` )
    REFERENCES `qnaire` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `shift`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `shift` ;

CREATE  TABLE IF NOT EXISTS `shift` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `site_id` INT UNSIGNED NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `date` DATE NOT NULL ,
  `start_time` TIME NOT NULL ,
  `end_time` TIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  CONSTRAINT `fk_shift_site`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_shift_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `access`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `access` ;

CREATE  TABLE IF NOT EXISTS `access` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `role_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  `date` TIMESTAMP NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_role_id` (`role_id` ASC) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  UNIQUE INDEX `uq_user_role_site` (`user_id` ASC, `role_id` ASC, `site_id` ASC) ,
  CONSTRAINT `fk_access_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_access_role`
    FOREIGN KEY (`role_id` )
    REFERENCES `role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_access_site`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `participant_note`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `participant_note` ;

CREATE  TABLE IF NOT EXISTS `participant_note` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `sticky` TINYINT(1)  NOT NULL DEFAULT false ,
  `date` TIMESTAMP NOT NULL ,
  `note` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  CONSTRAINT `fk_participant_note_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_participant_note_participant`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `interview_note`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `interview_note` ;

CREATE  TABLE IF NOT EXISTS `interview_note` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `interview_id` INT UNSIGNED NOT NULL ,
  `sticky` TINYINT(1)  NOT NULL DEFAULT false ,
  `date` TIMESTAMP NOT NULL ,
  `note` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_interview_id` (`interview_id` ASC) ,
  CONSTRAINT `fk_interview_note_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_interview_note_interview`
    FOREIGN KEY (`interview_id` )
    REFERENCES `interview` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `phone_call_note`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `phone_call_note` ;

CREATE  TABLE IF NOT EXISTS `phone_call_note` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `phone_call_id` INT UNSIGNED NOT NULL ,
  `sticky` TINYINT(1)  NOT NULL DEFAULT false ,
  `date` TIMESTAMP NOT NULL ,
  `note` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_phone_call_id` (`phone_call_id` ASC) ,
  CONSTRAINT `fk_phone_call_note_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_phone_call_note_phone_call`
    FOREIGN KEY (`phone_call_id` )
    REFERENCES `phone_call` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `activity`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `activity` ;

CREATE  TABLE IF NOT EXISTS `activity` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  `role_id` INT UNSIGNED NOT NULL ,
  `operation_id` INT UNSIGNED NOT NULL ,
  `query` VARCHAR(511) NOT NULL ,
  `elapsed_time` FLOAT NOT NULL DEFAULT 0 ,
  `date` TIMESTAMP NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_role_id` (`role_id` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `fk_operation_id` (`operation_id` ASC) ,
  CONSTRAINT `fk_activity_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_activity_role`
    FOREIGN KEY (`role_id` )
    REFERENCES `role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_activity_site`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_activity_operation`
    FOREIGN KEY (`operation_id` )
    REFERENCES `operation` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `setting`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `setting` ;

CREATE  TABLE IF NOT EXISTS `setting` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `category` VARCHAR(45) NOT NULL ,
  `name` VARCHAR(45) NOT NULL ,
  `value` VARCHAR(45) NOT NULL ,
  `description` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_category_name` (`category` ASC, `name` ASC) ,
  INDEX `category` (`category` ASC) ,
  INDEX `name` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `setting_value`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `setting_value` ;

CREATE  TABLE IF NOT EXISTS `setting_value` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `setting_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  `value` VARCHAR(45) NOT NULL ,
  INDEX `fk_setting_id1` (`setting_id` ASC) ,
  INDEX `fk_site_id1` (`site_id` ASC) ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_setting_id_site_id` (`setting_id` ASC, `site_id` ASC) ,
  CONSTRAINT `fk_setting_id1`
    FOREIGN KEY (`setting_id` )
    REFERENCES `setting` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_site_id1`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB, 
COMMENT = 'Site-specific setting overriding the default.' ;


-- -----------------------------------------------------
-- Placeholder table for view `participant_primary_location`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_primary_location` (`participant_id` INT, `contact_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `participant_current_consent`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_current_consent` (`participant_id` INT, `consent_id` INT, `has_consent` INT);

-- -----------------------------------------------------
-- Placeholder table for view `participant_last_assignment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_last_assignment` (`participant_id` INT, `assignment_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `user_last_activity`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_last_activity` (`activity_id` INT, `user_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `site_last_activity`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `site_last_activity` (`activity_id` INT, `site_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `role_last_activity`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_last_activity` (`activity_id` INT, `role_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_general`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_general` (`id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_fax`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_fax` (`id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_machine_message`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_machine_message` (`id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_no_answer`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_no_answer` (`id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_busy`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_busy` (`id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_available`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_available` (`id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_appointment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_appointment` (`id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_missed`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_missed` (`id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_machine_no_message`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_machine_no_message` (`id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `participant_for_queue`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_for_queue` (`id` INT, `first_name` INT, `last_name` INT, `language` INT, `hin` INT, `status` INT, `site_id` INT, `province_id` INT, `last_assignment_id` INT, `last_phone_call_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_general_available`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_general_available` (`id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `assignment_last_phone_call`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `assignment_last_phone_call` (`assignment_id` INT, `phone_call_id` INT);

-- -----------------------------------------------------
-- View `participant_primary_location`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `participant_primary_location` ;
DROP TABLE IF EXISTS `participant_primary_location`;
CREATE  OR REPLACE VIEW `participant_primary_location` AS
SELECT participant_id, id AS contact_id
FROM contact AS t1
WHERE t1.rank = (
  SELECT MIN( t2.rank )
  FROM contact AS t2
  WHERE t2.active
  AND t2.province_id IS NOT NULL
  AND t1.participant_id = t2.participant_id
  GROUP BY t2.participant_id );

-- -----------------------------------------------------
-- View `participant_current_consent`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `participant_current_consent` ;
DROP TABLE IF EXISTS `participant_current_consent`;
CREATE  OR REPLACE VIEW `participant_current_consent` AS
SELECT participant_id, id AS consent_id, event IN( 'verbal accept', 'written accept' ) AS has_consent
FROM consent AS t1
WHERE t1.date = (
  SELECT MAX( t2.date )
  FROM consent AS t2
  WHERE t2.event IN ( 'verbal accept','verbal deny','written accept','written deny','retract' )
  AND t1.participant_id = t2.participant_id
  GROUP BY t2.participant_id );

-- -----------------------------------------------------
-- View `participant_last_assignment`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `participant_last_assignment` ;
DROP TABLE IF EXISTS `participant_last_assignment`;
CREATE  OR REPLACE VIEW `participant_last_assignment` AS
SELECT interview_1.participant_id, assignment_1.id as assignment_id
FROM assignment AS assignment_1, interview AS interview_1
WHERE interview_1.id = assignment_1.interview_id
AND assignment_1.start_time = (
  SELECT MAX( assignment_2.start_time )
  FROM assignment AS assignment_2, interview AS interview_2
  WHERE interview_2.id = assignment_2.interview_id
  AND interview_1.participant_id = interview_2.participant_id
  AND assignment_2.end_time IS NOT NULL
  GROUP BY interview_2.participant_id );

-- -----------------------------------------------------
-- View `user_last_activity`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `user_last_activity` ;
DROP TABLE IF EXISTS `user_last_activity`;
CREATE  OR REPLACE VIEW `user_last_activity` AS
SELECT activity_1.id AS activity_id, user_1.id as user_id
FROM activity AS activity_1, user AS user_1
WHERE user_1.id = activity_1.user_id
AND activity_1.date = (
  SELECT MAX( activity_2.date )
  FROM activity AS activity_2, user AS user_2
  WHERE user_2.id = activity_2.user_id
  AND user_2.id = user_1.id
  GROUP BY user_2.id )
GROUP BY activity_1.date;

-- -----------------------------------------------------
-- View `site_last_activity`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `site_last_activity` ;
DROP TABLE IF EXISTS `site_last_activity`;
CREATE  OR REPLACE VIEW `site_last_activity` AS
SELECT activity_1.id AS activity_id, site_1.id as site_id
FROM activity AS activity_1, site AS site_1
WHERE site_1.id = activity_1.site_id
AND activity_1.date = (
  SELECT MAX( activity_2.date )
  FROM activity AS activity_2, site AS site_2
  WHERE site_2.id = activity_2.site_id
  AND site_2.id = site_1.id
  GROUP BY site_2.id )
GROUP BY activity_1.date;

-- -----------------------------------------------------
-- View `role_last_activity`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `role_last_activity` ;
DROP TABLE IF EXISTS `role_last_activity`;
CREATE  OR REPLACE VIEW `role_last_activity` AS
SELECT activity_1.id AS activity_id, role_1.id as role_id
FROM activity AS activity_1, role AS role_1
WHERE role_1.id = activity_1.role_id
AND activity_1.date = (
  SELECT MAX( activity_2.date )
  FROM activity AS activity_2, role AS role_2
  WHERE role_2.id = activity_2.role_id
  AND role_2.id = role_1.id
  GROUP BY role_2.id )
GROUP BY activity_1.date;

-- -----------------------------------------------------
-- View `queue_general`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_general` ;
DROP TABLE IF EXISTS `queue_general`;
CREATE  OR REPLACE VIEW `queue_general` AS
SELECT participant.*
FROM sample, sample_has_participant, participant_for_queue AS participant
WHERE sample.qnaire_id IS NOT NULL
AND sample.id = sample_has_participant.sample_id
AND sample_has_participant.participant_id = participant.id
AND participant.last_assignment_id IS NULL;

-- -----------------------------------------------------
-- View `queue_fax`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_fax` ;
DROP TABLE IF EXISTS `queue_fax`;
CREATE  OR REPLACE VIEW `queue_fax` AS
SELECT participant.*
FROM sample, sample_has_participant, participant_for_queue AS participant, phone_call, interview, assignment
WHERE sample.qnaire_id IS NOT NULL
AND sample.id = sample_has_participant.sample_id
AND sample_has_participant.participant_id = participant.id
AND participant.last_assignment_id = assignment.id
AND assignment.interview_id = interview.id
AND interview.completed = false
AND participant.last_phone_call_id = phone_call.id
AND phone_call.status = "fax"
AND NOW() >= phone_call.end_time + INTERVAL (
  SELECT value
  FROM setting
  WHERE category = "callback timing"
  AND name = "fax" ) MINUTE;

-- -----------------------------------------------------
-- View `queue_machine_message`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_machine_message` ;
DROP TABLE IF EXISTS `queue_machine_message`;
CREATE  OR REPLACE VIEW `queue_machine_message` AS
SELECT participant.*
FROM sample, sample_has_participant, participant_for_queue AS participant, phone_call, interview, assignment
WHERE sample.qnaire_id IS NOT NULL
AND sample.id = sample_has_participant.sample_id
AND sample_has_participant.participant_id = participant.id
AND participant.last_assignment_id = assignment.id
AND assignment.interview_id = interview.id
AND interview.completed = false
AND participant.last_phone_call_id = phone_call.id
AND phone_call.status = "machine message"
AND NOW() >= phone_call.end_time + INTERVAL (
  SELECT value
  FROM setting
  WHERE category = "callback timing"
  AND name = "machine message" ) MINUTE;

-- -----------------------------------------------------
-- View `queue_no_answer`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_no_answer` ;
DROP TABLE IF EXISTS `queue_no_answer`;
CREATE  OR REPLACE VIEW `queue_no_answer` AS
SELECT participant.*
FROM sample, sample_has_participant, participant_for_queue AS participant, phone_call, interview, assignment
WHERE sample.qnaire_id IS NOT NULL
AND sample.id = sample_has_participant.sample_id
AND sample_has_participant.participant_id = participant.id
AND participant.last_assignment_id = assignment.id
AND assignment.interview_id = interview.id
AND interview.completed = false
AND participant.last_phone_call_id = phone_call.id
AND phone_call.status = "no answer"
AND NOW() >= phone_call.end_time + INTERVAL (
  SELECT value
  FROM setting
  WHERE category = "callback timing"
  AND name = "no answer" ) MINUTE;

-- -----------------------------------------------------
-- View `queue_busy`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_busy` ;
DROP TABLE IF EXISTS `queue_busy`;
CREATE  OR REPLACE VIEW `queue_busy` AS
SELECT participant.*
FROM sample, sample_has_participant, participant_for_queue AS participant, phone_call, interview, assignment
WHERE sample.qnaire_id IS NOT NULL
AND sample.id = sample_has_participant.sample_id
AND sample_has_participant.participant_id = participant.id
AND participant.last_assignment_id = assignment.id
AND assignment.interview_id = interview.id
AND interview.completed = false
AND participant.last_phone_call_id = phone_call.id
AND phone_call.status = "busy"
AND NOW() >= phone_call.end_time + INTERVAL (
  SELECT value
  FROM setting
  WHERE category = "callback timing"
  AND name = "busy" ) MINUTE;

-- -----------------------------------------------------
-- View `queue_available`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_available` ;
DROP TABLE IF EXISTS `queue_available`;
CREATE  OR REPLACE VIEW `queue_available` AS
SELECT participant.*
FROM sample, sample_has_participant, participant_for_queue AS participant, availability, interview
WHERE sample.qnaire_id IS NOT NULL
AND sample.id = sample_has_participant.sample_id
AND sample_has_participant.participant_id = participant.id
AND participant.last_assignment_id IS NOT NULL
AND participant.id = interview.participant_id
AND interview.completed = false
AND participant.id = availability.participant_id
AND CASE DAYOFWEEK( NOW() )
  WHEN 1 THEN availability.sunday
  WHEN 2 THEN availability.monday
  WHEN 3 THEN availability.tuesday
  WHEN 4 THEN availability.wednesday
  WHEN 5 THEN availability.thursday
  WHEN 6 THEN availability.friday
  WHEN 7 THEN availability.saturday
  ELSE 0
END = 1
AND availability.start_time < NOW()
AND availability.end_time > NOW();

-- -----------------------------------------------------
-- View `queue_appointment`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_appointment` ;
DROP TABLE IF EXISTS `queue_appointment`;
CREATE  OR REPLACE VIEW `queue_appointment` AS
SELECT participant.*
FROM sample, sample_has_participant, participant_for_queue AS participant, appointment, interview
WHERE sample.qnaire_id IS NOT NULL
AND sample.id = sample_has_participant.sample_id
AND sample_has_participant.participant_id = participant.id
AND participant.id = interview.participant_id
AND interview.completed = false
AND participant.id = appointment.participant_id
AND appointment.completed = false
AND appointment.date >= NOW() - INTERVAL (
  SELECT value
  FROM setting
  WHERE category = "appointment"
  AND name = "call pre-window" )
  MINUTE
AND appointment.date <= NOW() + INTERVAL (
  SELECT value
  FROM setting
  WHERE category = "appointment"
  AND name = "call post-window" )
  MINUTE;

-- -----------------------------------------------------
-- View `queue_missed`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_missed` ;
DROP TABLE IF EXISTS `queue_missed`;
CREATE  OR REPLACE VIEW `queue_missed` AS
SELECT participant.*
FROM sample, sample_has_participant, participant_for_queue AS participant, appointment, interview
WHERE sample.qnaire_id IS NOT NULL
AND sample.id = sample_has_participant.sample_id
AND sample_has_participant.participant_id = participant.id
AND participant.id = interview.participant_id
AND interview.completed = false
AND participant.id = appointment.participant_id
AND appointment.completed = false
AND appointment.date > NOW() + INTERVAL (
  SELECT value
  FROM setting
  WHERE category = "appointment"
  AND name = "call post-window" )
  MINUTE;

-- -----------------------------------------------------
-- View `queue_machine_no_message`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_machine_no_message` ;
DROP TABLE IF EXISTS `queue_machine_no_message`;
CREATE  OR REPLACE VIEW `queue_machine_no_message` AS
SELECT participant.*
FROM sample, sample_has_participant, participant_for_queue AS participant, phone_call, interview, assignment
WHERE sample.qnaire_id IS NOT NULL
AND sample.id = sample_has_participant.sample_id
AND sample_has_participant.participant_id = participant.id
AND participant.last_assignment_id = assignment.id
AND assignment.interview_id = interview.id
AND interview.completed = false
AND participant.last_phone_call_id = phone_call.id
AND phone_call.status = "machine no message"
AND NOW() >= phone_call.end_time + INTERVAL (
  SELECT value
  FROM setting
  WHERE category = "callback timing"
  AND name = "machine no message" ) MINUTE;

-- -----------------------------------------------------
-- View `participant_for_queue`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `participant_for_queue` ;
DROP TABLE IF EXISTS `participant_for_queue`;
CREATE  OR REPLACE VIEW `participant_for_queue` AS
SELECT participant.*, contact.province_id, participant_last_assignment.assignment_id AS last_assignment_id, assignment_last_phone_call.phone_call_id AS last_phone_call_id
FROM participant
LEFT JOIN participant_primary_location
ON participant.id = participant_primary_location.participant_id 
LEFT JOIN contact
ON participant_primary_location.contact_id = contact.id
LEFT JOIN participant_last_assignment
ON participant.id = participant_last_assignment.participant_id 
LEFT JOIN assignment_last_phone_call
ON participant_last_assignment.assignment_id = assignment_last_phone_call.assignment_id
WHERE participant.status IS NULL;

-- -----------------------------------------------------
-- View `queue_general_available`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_general_available` ;
DROP TABLE IF EXISTS `queue_general_available`;
CREATE  OR REPLACE VIEW `queue_general_available` AS
SELECT participant.*
FROM sample, sample_has_participant, participant_for_queue AS participant, availability, interview
WHERE sample.qnaire_id IS NOT NULL
AND sample.id = sample_has_participant.sample_id
AND sample_has_participant.participant_id = participant.id
AND participant.last_assignment_id IS NULL
AND participant.id = interview.participant_id
AND interview.completed = false
AND participant.id = availability.participant_id
AND CASE DAYOFWEEK( NOW() )
  WHEN 1 THEN availability.sunday
  WHEN 2 THEN availability.monday
  WHEN 3 THEN availability.tuesday
  WHEN 4 THEN availability.wednesday
  WHEN 5 THEN availability.thursday
  WHEN 6 THEN availability.friday
  WHEN 7 THEN availability.saturday
  ELSE 0
END = 1
AND availability.start_time < NOW()
AND availability.end_time > NOW();

-- -----------------------------------------------------
-- View `assignment_last_phone_call`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `assignment_last_phone_call` ;
DROP TABLE IF EXISTS `assignment_last_phone_call`;
CREATE  OR REPLACE VIEW `assignment_last_phone_call` AS
SELECT assignment_1.id as assignment_id, phone_call_1.id as phone_call_id
FROM phone_call AS phone_call_1, assignment AS assignment_1
WHERE assignment_1.id = phone_call_1.assignment_id
AND phone_call_1.start_time = (
  SELECT MAX( phone_call_2.start_time )
  FROM phone_call AS phone_call_2, assignment AS assignment_2
  WHERE assignment_2.id = phone_call_2.assignment_id
  AND assignment_1.id = assignment_2.id
  AND phone_call_2.end_time IS NOT NULL
  GROUP BY assignment_2.id );


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
