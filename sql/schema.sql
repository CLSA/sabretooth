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
COMMENT = 'aka: qnaire_has_survey';


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
  `province` ENUM('AB','BC','MB','NB','NL','NT','NS','NU','ON','PE','QC','SK','YT') NULL DEFAULT NULL COMMENT 'Only filled out if address is in Canada.  This is used to determine which site calls the participant.' ,
  `country` VARCHAR(20) NULL DEFAULT NULL ,
  `postcode` VARCHAR(20) NULL DEFAULT NULL ,
  `note` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  UNIQUE INDEX `uq_participant_id_active_rank` (`participant_id` ASC, `rank` ASC) ,
  CONSTRAINT `fk_contact_participant`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


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
-- Table `interview`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `interview` ;

CREATE  TABLE IF NOT EXISTS `interview` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `phase_id` INT UNSIGNED NOT NULL ,
  `require_supervisor` TINYINT(1)  NOT NULL DEFAULT false ,
  `completed` TINYINT(1)  NOT NULL DEFAULT false ,
  `duplicate_phase_id` INT UNSIGNED NULL DEFAULT NULL ,
  `site_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'If not null then force all calls for this interview to the site.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `fk_phase_id` (`phase_id` ASC) ,
  INDEX `fk_duplicate_phase_id` (`duplicate_phase_id` ASC) ,
  CONSTRAINT `fk_interview_participant`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_interview_site`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
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
ENGINE = InnoDB
COMMENT = 'aka: qnaire_has_participant';


-- -----------------------------------------------------
-- Table `assignment`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `assignment` ;

CREATE  TABLE IF NOT EXISTS `assignment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `interview_id` INT UNSIGNED NOT NULL ,
  `queue_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'What queue did the interview get assigned from?' ,
  `start_time` TIMESTAMP NOT NULL ,
  `end_time` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_queue_id` (`queue_id` ASC) ,
  INDEX `fk_interview_id` (`interview_id` ASC) ,
  CONSTRAINT `fk_assignment_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_queue`
    FOREIGN KEY (`queue_id` )
    REFERENCES `queue` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_interview`
    FOREIGN KEY (`interview_id` )
    REFERENCES `interview` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `appointment`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `appointment` ;

CREATE  TABLE IF NOT EXISTS `appointment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `contact_id` INT UNSIGNED NOT NULL ,
  `date` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_contact_id` (`contact_id` ASC) ,
  CONSTRAINT `fk_appointment_contact`
    FOREIGN KEY (`contact_id` )
    REFERENCES `contact` (`id` )
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
  `status` ENUM('in progress','contacted', 'busy','no answer','machine message','machine no message','fax','disconnected','wrong number','language') NOT NULL DEFAULT 'in progress' ,
  `start_time` TIMESTAMP NOT NULL COMMENT 'The time the call started.' ,
  `end_time` DATETIME NULL DEFAULT NULL COMMENT 'The time the call endede.' ,
  `phase_id` INT UNSIGNED NOT NULL ,
  `appointment_id` INT UNSIGNED NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_contact_id` (`contact_id` ASC) ,
  INDEX `fk_assignment_id` (`assignment_id` ASC) ,
  INDEX `fk_phase_id` (`phase_id` ASC) ,
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
  CONSTRAINT `fk_phone_call_phase`
    FOREIGN KEY (`phase_id` )
    REFERENCES `phase` (`id` )
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
  `description` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) )
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
  CONSTRAINT `fk_appointment_participant`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `qnaire_has_sample`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `qnaire_has_sample` ;

CREATE  TABLE IF NOT EXISTS `qnaire_has_sample` (
  `qnaire_id` INT UNSIGNED NOT NULL ,
  `sample_id` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`qnaire_id`, `sample_id`) ,
  INDEX `fk_sample_id` (`sample_id` ASC) ,
  INDEX `fk_qnaire_id` (`qnaire_id` ASC) ,
  CONSTRAINT `fk_qnaire_has_sample_qnaire`
    FOREIGN KEY (`qnaire_id` )
    REFERENCES `qnaire` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_qnaire_has_sample_sample`
    FOREIGN KEY (`sample_id` )
    REFERENCES `sample` (`id` )
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
  `value` VARCHAR(45) NULL DEFAULT NULL ,
  `site_id` INT UNSIGNED NULL DEFAULT NULL ,
  `description` TEXT NULL ,
  INDEX `fk_setting_site` (`site_id` ASC) ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_category_name_site_id` (`category` ASC, `name` ASC, `site_id` ASC) ,
  CONSTRAINT `fk_setting_site`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Placeholder table for view `participant_primary_location`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_primary_location` (`participant_id` INT, `contact_id` INT, `province` INT);

-- -----------------------------------------------------
-- Placeholder table for view `participant_current_consent`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_current_consent` (`participant_id` INT, `consent_id` INT, `consent` INT);

-- -----------------------------------------------------
-- Placeholder table for view `participant_last_phone_call_status`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_last_phone_call_status` (`phone_call_id` INT, `participant_id` INT, `status` INT);

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
CREATE TABLE IF NOT EXISTS `queue_general` (`id` INT, `first_name` INT, `last_name` INT, `language` INT, `hin` INT, `status` INT, `site_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_appointment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_appointment` (`id` INT, `first_name` INT, `last_name` INT, `language` INT, `hin` INT, `status` INT, `site_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_missed`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_missed` (`id` INT, `first_name` INT, `last_name` INT, `language` INT, `hin` INT, `status` INT, `site_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_available`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_available` (`id` INT, `first_name` INT, `last_name` INT, `language` INT, `hin` INT, `status` INT, `site_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_previous_busy`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_previous_busy` (`id` INT, `first_name` INT, `last_name` INT, `language` INT, `hin` INT, `status` INT, `site_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_previous_no_answer`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_previous_no_answer` (`id` INT, `first_name` INT, `last_name` INT, `language` INT, `hin` INT, `status` INT, `site_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_previous_answering_machine`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_previous_answering_machine` (`id` INT, `first_name` INT, `last_name` INT, `language` INT, `hin` INT, `status` INT, `site_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `queue_previous_fax`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `queue_previous_fax` (`id` INT, `first_name` INT, `last_name` INT, `language` INT, `hin` INT, `status` INT, `site_id` INT);

-- -----------------------------------------------------
-- View `participant_primary_location`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `participant_primary_location` ;
DROP TABLE IF EXISTS `participant_primary_location`;
CREATE  OR REPLACE VIEW `participant_primary_location` AS
SELECT participant_id, id AS contact_id, province
FROM contact AS t1
WHERE t1.rank = (
  SELECT MIN( t2.rank )
  FROM contact AS t2
  WHERE t2.active
  AND t2.province IS NOT NULL
  AND t1.participant_id = t2.participant_id
  GROUP BY t2.participant_id );

-- -----------------------------------------------------
-- View `participant_current_consent`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `participant_current_consent` ;
DROP TABLE IF EXISTS `participant_current_consent`;
CREATE  OR REPLACE VIEW `participant_current_consent` AS
SELECT participant_id, id AS consent_id, event IN( 'verbal accept', 'written accept' ) AS consent
FROM consent AS t1
WHERE t1.date = (
  SELECT MAX( t2.date )
  FROM consent AS t2
  WHERE t2.event IN ( 'verbal accept','verbal deny','written accept','written deny','retract' )
  AND t1.participant_id = t2.participant_id
  GROUP BY t2.participant_id );

-- -----------------------------------------------------
-- View `participant_last_phone_call_status`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `participant_last_phone_call_status` ;
DROP TABLE IF EXISTS `participant_last_phone_call_status`;
CREATE  OR REPLACE VIEW `participant_last_phone_call_status` AS
SELECT phone_call_1.id AS phone_call_id, contact_1.participant_id, phone_call_1.status
FROM phone_call AS phone_call_1, contact AS contact_1
WHERE contact_1.id = phone_call_1.contact_id
AND phone_call_1.start_time = (
  SELECT MAX( phone_call_2.start_time )
  FROM phone_call AS phone_call_2, contact AS contact_2
  WHERE contact_2.id = phone_call_2.contact_id
  AND contact_1.participant_id = contact_2.participant_id
  GROUP BY contact_2.participant_id );

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
FROM participant
LEFT JOIN interview
ON participant.id = interview.participant_id
WHERE interview.id IS NULL;

-- -----------------------------------------------------
-- View `queue_appointment`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_appointment` ;
DROP TABLE IF EXISTS `queue_appointment`;
CREATE  OR REPLACE VIEW `queue_appointment` AS
SELECT participant.*
FROM participant, contact, appointment
LEFT JOIN phone_call
ON appointment.id = phone_call.appointment_id
WHERE participant.id = contact.participant_id
AND contact.id = appointment.contact_id
AND phone_call.status NOT IN ( 'in progress', 'contacted' )
;

-- -----------------------------------------------------
-- View `queue_missed`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_missed` ;
DROP TABLE IF EXISTS `queue_missed`;
CREATE  OR REPLACE VIEW `queue_missed` AS
SELECT participant.*
FROM participant;

-- -----------------------------------------------------
-- View `queue_available`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_available` ;
DROP TABLE IF EXISTS `queue_available`;
CREATE  OR REPLACE VIEW `queue_available` AS
SELECT participant.*
FROM participant;

-- -----------------------------------------------------
-- View `queue_previous_busy`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_previous_busy` ;
DROP TABLE IF EXISTS `queue_previous_busy`;
CREATE  OR REPLACE VIEW `queue_previous_busy` AS
SELECT participant.*
FROM participant;

-- -----------------------------------------------------
-- View `queue_previous_no_answer`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_previous_no_answer` ;
DROP TABLE IF EXISTS `queue_previous_no_answer`;
CREATE  OR REPLACE VIEW `queue_previous_no_answer` AS
SELECT participant.*
FROM participant;

-- -----------------------------------------------------
-- View `queue_previous_answering_machine`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_previous_answering_machine` ;
DROP TABLE IF EXISTS `queue_previous_answering_machine`;
CREATE  OR REPLACE VIEW `queue_previous_answering_machine` AS
SELECT participant.*
FROM participant;

-- -----------------------------------------------------
-- View `queue_previous_fax`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `queue_previous_fax` ;
DROP TABLE IF EXISTS `queue_previous_fax`;
CREATE  OR REPLACE VIEW `queue_previous_fax` AS
SELECT participant.*
FROM participant;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
