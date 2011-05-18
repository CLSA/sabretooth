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
  `uid` VARCHAR(45) NULL COMMENT 'External unique ID' ,
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
  `first_name` VARCHAR(255) NOT NULL ,
  `last_name` VARCHAR(255) NOT NULL ,
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
  `qnaire_id` INT UNSIGNED NOT NULL ,
  `require_supervisor` TINYINT(1)  NOT NULL DEFAULT false ,
  `completed` TINYINT(1)  NOT NULL DEFAULT false ,
  `duplicate_qnaire_id` INT UNSIGNED NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_qnaire_id` (`qnaire_id` ASC) ,
  INDEX `fk_duplicate_qnaire_id` (`qnaire_id` ASC) ,
  CONSTRAINT `fk_interview_participant`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_interview_qnaire_id`
    FOREIGN KEY (`qnaire_id` )
    REFERENCES `qnaire` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_interiew_duplicate_qnaire_id`
    FOREIGN KEY (`qnaire_id` )
    REFERENCES `qnaire` (`id` )
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
  `title` VARCHAR(255) NOT NULL ,
  `rank` INT UNSIGNED NULL DEFAULT NULL ,
  `parent_queue_id` INT UNSIGNED NULL DEFAULT NULL ,
  `description` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_rank` (`rank` ASC) ,
  UNIQUE INDEX `uq_title` (`title` ASC) ,
  INDEX `fk_parent_queue_id` (`parent_queue_id` ASC) ,
  CONSTRAINT `fk_queue_parent_queue_id`
    FOREIGN KEY (`parent_queue_id` )
    REFERENCES `queue` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
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
-- Table `phone_call`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `phone_call` ;

CREATE  TABLE IF NOT EXISTS `phone_call` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `assignment_id` INT UNSIGNED NOT NULL ,
  `contact_id` INT UNSIGNED NOT NULL ,
  `start_time` DATETIME NOT NULL COMMENT 'The time the call started.' ,
  `end_time` DATETIME NULL DEFAULT NULL COMMENT 'The time the call endede.' ,
  `status` ENUM('contacted', 'busy','no answer','machine message','machine no message','fax','disconnected','wrong number','language') NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_contact_id` (`contact_id` ASC) ,
  INDEX `fk_assignment_id` (`assignment_id` ASC) ,
  CONSTRAINT `fk_phone_call_contact`
    FOREIGN KEY (`contact_id` )
    REFERENCES `contact` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_phone_call_assignment`
    FOREIGN KEY (`assignment_id` )
    REFERENCES `assignment` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `operation`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `operation` ;

CREATE  TABLE IF NOT EXISTS `operation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `type` ENUM('action','datum','widget') NOT NULL ,
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
  `date` DATETIME NOT NULL ,
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
-- Table `assignment_note`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `assignment_note` ;

CREATE  TABLE IF NOT EXISTS `assignment_note` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `assignment_id` INT UNSIGNED NOT NULL ,
  `sticky` TINYINT(1)  NOT NULL DEFAULT false ,
  `date` DATETIME NOT NULL ,
  `note` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_assignment_id` (`assignment_id` ASC) ,
  CONSTRAINT `fk_assignment_note_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_note_assignment`
    FOREIGN KEY (`assignment_id` )
    REFERENCES `assignment` (`id` )
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
  `type` ENUM( 'boolean', 'integer', 'float', 'string' ) NOT NULL ,
  `value` VARCHAR(45) NOT NULL ,
  `description` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_category_name` (`category` ASC, `name` ASC) ,
  INDEX `category` (`category` ASC) ,
  INDEX `name` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `appointment`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `appointment` ;

CREATE  TABLE IF NOT EXISTS `appointment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `contact_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Which contact to use.' ,
  `assignment_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'This appointment\'s assignment.' ,
  `date` DATETIME NOT NULL ,
  `status` ENUM('complete','incomplete') NULL DEFAULT NULL COMMENT 'If the appointment was met, whether the participant was reached.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_contact_id` (`contact_id` ASC) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_assignment_id` (`assignment_id` ASC) ,
  CONSTRAINT `fk_appointment_contact`
    FOREIGN KEY (`contact_id` )
    REFERENCES `contact` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_appointment_participant`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_appointment_assignment`
    FOREIGN KEY (`assignment_id` )
    REFERENCES `assignment` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
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
-- Table `away_time`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `away_time` ;

CREATE  TABLE IF NOT EXISTS `away_time` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `start_time` DATETIME NOT NULL ,
  `end_time` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  CONSTRAINT `fk_away_time_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Placeholder table for view `participant_primary_location`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_primary_location` (`participant_id` INT, `contact_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `participant_last_assignment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_last_assignment` (`participant_id` INT, `assignment_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `participant_for_queue`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_for_queue` (`id` INT, `uid` INT, `first_name` INT, `last_name` INT, `language` INT, `hin` INT, `status` INT, `site_id` INT, `last_assignment_id` INT, `base_site_id` INT, `assigned` INT);

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
  GROUP BY interview_2.participant_id );

-- -----------------------------------------------------
-- View `participant_for_queue`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `participant_for_queue` ;
DROP TABLE IF EXISTS `participant_for_queue`;
CREATE  OR REPLACE VIEW `participant_for_queue` AS
SELECT participant.*,
       assignment.id AS last_assignment_id,
       IFNULL( participant.site_id, province.site_id ) AS base_site_id,
       assignment.id IS NOT NULL AND assignment.end_time IS NULL AS assigned
FROM participant
LEFT JOIN participant_primary_location
ON participant.id = participant_primary_location.participant_id 
LEFT JOIN contact
ON participant_primary_location.contact_id = contact.id
LEFT JOIN province
ON contact.province_id = province.id
LEFT JOIN participant_last_assignment
ON participant.id = participant_last_assignment.participant_id 
LEFT JOIN assignment
ON participant_last_assignment.assignment_id = assignment.id;

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
