SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';


-- -----------------------------------------------------
-- Table `source`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `source` ;

CREATE  TABLE IF NOT EXISTS `source` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `name` VARCHAR(45) NOT NULL ,
  `withdraw_type` ENUM('verbal accept','verbal deny','written accept','written deny','retract','withdraw') NOT NULL DEFAULT "withdraw" ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_name` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `participant`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `participant` ;

CREATE  TABLE IF NOT EXISTS `participant` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `active` TINYINT(1)  NOT NULL DEFAULT true ,
  `uid` VARCHAR(45) NOT NULL COMMENT 'External unique ID' ,
  `source_id` INT UNSIGNED NULL DEFAULT NULL ,
  `first_name` VARCHAR(45) NOT NULL ,
  `last_name` VARCHAR(45) NOT NULL ,
  `status` ENUM('deceased', 'deaf', 'mentally unfit','language barrier','age range','other') NULL DEFAULT NULL ,
  `language` ENUM('en','fr') NULL DEFAULT NULL ,
  `site_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'If not null then force all calls to this participant to the site.' ,
  `prior_contact_date` DATE NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `dk_active` (`active` ASC) ,
  INDEX `dk_status` (`status` ASC) ,
  INDEX `dk_prior_contact_date` (`prior_contact_date` ASC) ,
  UNIQUE INDEX `uq_uid` (`uid` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `fk_source_id` (`source_id` ASC) ,
  CONSTRAINT `fk_participant_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_participant_source_id`
    FOREIGN KEY (`source_id` )
    REFERENCES `source` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `qnaire`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `qnaire` ;

CREATE  TABLE IF NOT EXISTS `qnaire` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `name` VARCHAR(255) NOT NULL ,
  `rank` INT NOT NULL ,
  `prev_qnaire_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'The qnaire which must be completed before this one begins.' ,
  `delay` INT NOT NULL DEFAULT 0 COMMENT 'How many weeks after then end of the previous qnaire before starting.' ,
  `withdraw_sid` INT NULL DEFAULT NULL ,
  `rescore_sid` INT NULL DEFAULT NULL ,
  `description` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_name` (`name` ASC) ,
  UNIQUE INDEX `uq_rank` (`rank` ASC) ,
  INDEX `fk_prev_qnaire_id` (`prev_qnaire_id` ASC) ,
  CONSTRAINT `fk_qnaire_prev_qnaire`
    FOREIGN KEY (`prev_qnaire_id` )
    REFERENCES `qnaire` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `phase`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `phase` ;

CREATE  TABLE IF NOT EXISTS `phase` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `qnaire_id` INT UNSIGNED NOT NULL ,
  `sid` INT NOT NULL COMMENT 'The default survey ID to use for this phase.' ,
  `rank` SMALLINT UNSIGNED NOT NULL ,
  `repeated` TINYINT(1)  NOT NULL DEFAULT false ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_qnaire_id` (`qnaire_id` ASC) ,
  UNIQUE INDEX `uq_qnaire_id_rank` (`qnaire_id` ASC, `rank` ASC) ,
  CONSTRAINT `fk_phase_qnaire_id`
    FOREIGN KEY (`qnaire_id` )
    REFERENCES `qnaire` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'aka: qnaire_has_survey' ;


-- -----------------------------------------------------
-- Table `interview`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `interview` ;

CREATE  TABLE IF NOT EXISTS `interview` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `qnaire_id` INT UNSIGNED NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `require_supervisor` TINYINT(1)  NOT NULL DEFAULT false ,
  `completed` TINYINT(1)  NOT NULL DEFAULT false ,
  `rescored` ENUM('Yes','No','N/A') NOT NULL DEFAULT 'N/A' ,
  `duplicate_qnaire_id` INT UNSIGNED NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_qnaire_id` (`qnaire_id` ASC) ,
  INDEX `fk_duplicate_qnaire_id` (`qnaire_id` ASC) ,
  INDEX `dk_completed` (`completed` ASC) ,
  UNIQUE INDEX `uq_participant_id_qnaire_id` (`participant_id` ASC, `qnaire_id` ASC) ,
  INDEX `dk_rescored` (`rescored` ASC) ,
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
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `name` VARCHAR(45) NOT NULL ,
  `title` VARCHAR(255) NOT NULL ,
  `rank` INT UNSIGNED NULL DEFAULT NULL ,
  `qnaire_specific` TINYINT(1)  NOT NULL ,
  `parent_queue_id` INT UNSIGNED NULL DEFAULT NULL ,
  `description` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_rank` (`rank` ASC) ,
  INDEX `fk_parent_queue_id` (`parent_queue_id` ASC) ,
  UNIQUE INDEX `uq_name` (`name` ASC) ,
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
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL COMMENT 'The site from which the user was assigned.' ,
  `interview_id` INT UNSIGNED NOT NULL ,
  `queue_id` INT UNSIGNED NOT NULL COMMENT 'The queue that the assignment came from.' ,
  `start_datetime` DATETIME NOT NULL ,
  `end_datetime` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_interview_id` (`interview_id` ASC) ,
  INDEX `fk_queue_id` (`queue_id` ASC) ,
  INDEX `dk_start_datetime` (`start_datetime` ASC) ,
  INDEX `dk_end_datetime` (`end_datetime` ASC) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  CONSTRAINT `fk_assignment_interview_id`
    FOREIGN KEY (`interview_id` )
    REFERENCES `interview` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_queue_id`
    FOREIGN KEY (`queue_id` )
    REFERENCES `queue` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `region`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `region` ;

CREATE  TABLE IF NOT EXISTS `region` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `name` VARCHAR(45) NOT NULL ,
  `abbreviation` VARCHAR(5) NOT NULL ,
  `country` VARCHAR(45) NOT NULL ,
  `site_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Which site manages participants.' ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_name` (`name` ASC) ,
  UNIQUE INDEX `uq_abbreviation` (`abbreviation` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  CONSTRAINT `fk_region_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `address`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `address` ;

CREATE  TABLE IF NOT EXISTS `address` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `active` TINYINT(1)  NOT NULL DEFAULT true ,
  `rank` INT NOT NULL ,
  `address1` VARCHAR(512) NOT NULL ,
  `address2` VARCHAR(512) NULL DEFAULT NULL ,
  `city` VARCHAR(100) NOT NULL ,
  `region_id` INT UNSIGNED NOT NULL ,
  `postcode` VARCHAR(10) NOT NULL ,
  `january` TINYINT(1)  NOT NULL DEFAULT true ,
  `february` TINYINT(1)  NOT NULL DEFAULT true ,
  `march` TINYINT(1)  NOT NULL DEFAULT true ,
  `april` TINYINT(1)  NOT NULL DEFAULT true ,
  `may` TINYINT(1)  NOT NULL DEFAULT true ,
  `june` TINYINT(1)  NOT NULL DEFAULT true ,
  `july` TINYINT(1)  NOT NULL DEFAULT true ,
  `august` TINYINT(1)  NOT NULL DEFAULT true ,
  `september` TINYINT(1)  NOT NULL DEFAULT true ,
  `october` TINYINT(1)  NOT NULL DEFAULT true ,
  `november` TINYINT(1)  NOT NULL DEFAULT true ,
  `december` TINYINT(1)  NOT NULL DEFAULT true ,
  `note` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_region_id` (`region_id` ASC) ,
  UNIQUE INDEX `uq_participant_id_rank` (`participant_id` ASC, `rank` ASC) ,
  INDEX `dk_city` (`city` ASC) ,
  INDEX `dk_postcode` (`postcode` ASC) ,
  CONSTRAINT `fk_address_participant_id`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_address_region_id`
    FOREIGN KEY (`region_id` )
    REFERENCES `region` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `phone`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `phone` ;

CREATE  TABLE IF NOT EXISTS `phone` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `address_id` INT UNSIGNED NULL DEFAULT NULL ,
  `active` TINYINT(1)  NOT NULL DEFAULT true ,
  `rank` INT NOT NULL ,
  `type` ENUM('home','home2','work','work2','mobile','mobile2','other','other2') NOT NULL ,
  `number` VARCHAR(45) NOT NULL ,
  `note` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_address_id` (`address_id` ASC) ,
  UNIQUE INDEX `uq_participant_id_rank` (`participant_id` ASC, `rank` ASC) ,
  CONSTRAINT `fk_phone_participant_id`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_phone_address_id`
    FOREIGN KEY (`address_id` )
    REFERENCES `address` (`id` )
    ON DELETE SET NULL
    ON UPDATE SET NULL)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `phone_call`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `phone_call` ;

CREATE  TABLE IF NOT EXISTS `phone_call` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `assignment_id` INT UNSIGNED NOT NULL ,
  `phone_id` INT UNSIGNED NOT NULL ,
  `start_datetime` DATETIME NOT NULL COMMENT 'The time the call started.' ,
  `end_datetime` DATETIME NULL DEFAULT NULL COMMENT 'The time the call endede.' ,
  `status` ENUM('contacted','busy','no answer','machine message','machine no message','fax','disconnected','wrong number','not reached','hang up','soft refusal') NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_assignment_id` (`assignment_id` ASC) ,
  INDEX `dk_status` (`status` ASC) ,
  INDEX `fk_phone_id` (`phone_id` ASC) ,
  INDEX `dk_start_datetime` (`start_datetime` ASC) ,
  INDEX `dk_end_datetime` (`end_datetime` ASC) ,
  CONSTRAINT `fk_phone_call_assignment`
    FOREIGN KEY (`assignment_id` )
    REFERENCES `assignment` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_phone_call_phone_id`
    FOREIGN KEY (`phone_id` )
    REFERENCES `phone` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `consent`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `consent` ;

CREATE  TABLE IF NOT EXISTS `consent` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `event` ENUM('verbal accept','verbal deny','written accept','written deny','retract','withdraw') NOT NULL ,
  `date` DATE NOT NULL ,
  `note` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `dk_event` (`event` ASC) ,
  INDEX `dk_date` (`date` ASC) ,
  CONSTRAINT `fk_consent_participant`
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
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
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
  INDEX `dk_start_time` (`start_time` ASC) ,
  INDEX `dk_end_time` (`end_time` ASC) ,
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
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `start_datetime` DATETIME NOT NULL ,
  `end_datetime` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `dk_start_datetime` (`start_datetime` ASC) ,
  INDEX `dk_end_datetime` (`end_datetime` ASC) ,
  CONSTRAINT `fk_shift_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_shift_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `participant_note`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `participant_note` ;

CREATE  TABLE IF NOT EXISTS `participant_note` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `sticky` TINYINT(1)  NOT NULL DEFAULT false ,
  `datetime` DATETIME NOT NULL ,
  `note` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `dk_sticky_datetime` (`sticky` ASC, `datetime` ASC) ,
  CONSTRAINT `fk_participant_note_participant_id`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_participant_note_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `assignment_note`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `assignment_note` ;

CREATE  TABLE IF NOT EXISTS `assignment_note` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `assignment_id` INT UNSIGNED NOT NULL ,
  `sticky` TINYINT(1)  NOT NULL DEFAULT false ,
  `datetime` DATETIME NOT NULL ,
  `note` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_assignment_id` (`assignment_id` ASC) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `dk_sticky_datetime` (`sticky` ASC, `datetime` ASC) ,
  CONSTRAINT `fk_assignment_note_assignment_id`
    FOREIGN KEY (`assignment_id` )
    REFERENCES `assignment` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_note_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `appointment`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `appointment` ;

CREATE  TABLE IF NOT EXISTS `appointment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `phone_id` INT UNSIGNED NULL DEFAULT NULL ,
  `assignment_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'This appointment\'s assignment.' ,
  `datetime` DATETIME NOT NULL ,
  `type` ENUM('full','half') NOT NULL DEFAULT 'full' ,
  `reached` TINYINT(1)  NULL DEFAULT NULL COMMENT 'If the appointment was met, whether the participant was reached.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_assignment_id` (`assignment_id` ASC) ,
  INDEX `dk_reached` (`reached` ASC) ,
  INDEX `fk_phone_id` (`phone_id` ASC) ,
  INDEX `dk_datetime` (`datetime` ASC) ,
  CONSTRAINT `fk_appointment_participant`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_appointment_assignment`
    FOREIGN KEY (`assignment_id` )
    REFERENCES `assignment` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_appointment_phone_id`
    FOREIGN KEY (`phone_id` )
    REFERENCES `phone` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `away_time`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `away_time` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`away_time` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  `role_id` INT UNSIGNED NOT NULL ,
  `start_datetime` DATETIME NOT NULL ,
  `end_datetime` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `dk_start_datetime` (`start_datetime` ASC) ,
  INDEX `dk_end_datetime` (`end_datetime` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `fk_role_id` (`role_id` ASC) ,
  CONSTRAINT `fk_away_time_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `cenozo`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_away_time_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `cenozo`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_away_time_role_id`
    FOREIGN KEY (`role_id` )
    REFERENCES `cenozo`.`role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `shift_template`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `shift_template` ;

CREATE  TABLE IF NOT EXISTS `shift_template` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  `start_time` TIME NOT NULL ,
  `end_time` TIME NOT NULL ,
  `start_date` DATE NOT NULL ,
  `end_date` DATE NULL ,
  `operators` INT UNSIGNED NOT NULL ,
  `repeat_type` ENUM('weekly','day of month','day of week') NOT NULL DEFAULT "weekly" ,
  `repeat_every` INT NOT NULL DEFAULT 1 ,
  `monday` TINYINT(1)  NOT NULL DEFAULT false ,
  `tuesday` TINYINT(1)  NOT NULL DEFAULT false ,
  `wednesday` TINYINT(1)  NOT NULL DEFAULT false ,
  `thursday` TINYINT(1)  NOT NULL DEFAULT false ,
  `friday` TINYINT(1)  NOT NULL DEFAULT false ,
  `saturday` TINYINT(1)  NOT NULL DEFAULT false ,
  `sunday` TINYINT(1)  NOT NULL DEFAULT false ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `dk_start_time` (`start_time` ASC) ,
  INDEX `dk_end_time` (`end_time` ASC) ,
  INDEX `dk_start_date` (`start_date` ASC) ,
  INDEX `dk_end_date` (`end_date` ASC) ,
  CONSTRAINT `fk_shift_template_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `queue_restriction`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `queue_restriction` ;

CREATE  TABLE IF NOT EXISTS `queue_restriction` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `site_id` INT UNSIGNED NULL DEFAULT NULL ,
  `city` VARCHAR(100) NULL DEFAULT NULL ,
  `region_id` INT UNSIGNED NULL DEFAULT NULL ,
  `postcode` VARCHAR(10) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_region_id` (`region_id` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `dk_city` (`city` ASC) ,
  INDEX `dk_postcode` (`postcode` ASC) ,
  CONSTRAINT `fk_queue_restriction_region_id`
    FOREIGN KEY (`region_id` )
    REFERENCES `region` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_queue_restriction_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `recording`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `recording` ;

CREATE  TABLE IF NOT EXISTS `recording` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `interview_id` INT UNSIGNED NOT NULL ,
  `assignment_id` INT UNSIGNED NULL DEFAULT NULL ,
  `rank` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_interview_id` (`interview_id` ASC) ,
  UNIQUE INDEX `uq_interview_rank` (`interview_id` ASC, `rank` ASC) ,
  INDEX `fk_assignment_id` (`assignment_id` ASC) ,
  CONSTRAINT `fk_recording_interview_id`
    FOREIGN KEY (`interview_id` )
    REFERENCES `interview` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_recording_assignment_id`
    FOREIGN KEY (`assignment_id` )
    REFERENCES `assignment` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `source_survey`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `source_survey` ;

CREATE  TABLE IF NOT EXISTS `source_survey` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `phase_id` INT UNSIGNED NOT NULL ,
  `source_id` INT UNSIGNED NOT NULL ,
  `sid` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_phase_id` (`phase_id` ASC) ,
  INDEX `fk_source_id` (`source_id` ASC) ,
  UNIQUE INDEX `uq_phase_id_source_id` (`phase_id` ASC, `source_id` ASC) ,
  CONSTRAINT `fk_source_survey_phase_id`
    FOREIGN KEY (`phase_id` )
    REFERENCES `phase` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_source_survey_source_id`
    FOREIGN KEY (`source_id` )
    REFERENCES `source` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `source_withdraw`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `source_withdraw` ;

CREATE  TABLE IF NOT EXISTS `source_withdraw` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `qnaire_id` INT UNSIGNED NOT NULL ,
  `source_id` INT UNSIGNED NOT NULL ,
  `sid` INT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_source_withdraw_qnaire_id` (`qnaire_id` ASC) ,
  INDEX `fk_source_withdraw_source_id` (`source_id` ASC) ,
  UNIQUE INDEX `uq_qnaire_id_source_id` (`qnaire_id` ASC, `source_id` ASC) ,
  CONSTRAINT `fk_source_withdraw_qnaire_id`
    FOREIGN KEY (`qnaire_id` )
    REFERENCES `qnaire` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_source_withdraw_source_id`
    FOREIGN KEY (`source_id` )
    REFERENCES `source` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `user_time`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `user_time` ;

CREATE  TABLE IF NOT EXISTS `user_time` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  `role_id` INT UNSIGNED NOT NULL ,
  `date` DATE NOT NULL ,
  `total` FLOAT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `fk_role_id` (`role_id` ASC) ,
  UNIQUE INDEX `uq_user_site_role_date` (`user_id` ASC, `role_id` ASC, `site_id` ASC, `date` ASC) ,
  INDEX `dk_date` (`date` ASC) ,
  CONSTRAINT `fk_operator_time_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_operator_time_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_operator_time_role_id`
    FOREIGN KEY (`role_id` )
    REFERENCES `role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Placeholder table for view `participant_first_address`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_first_address` (`participant_id` INT, `address_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `participant_last_assignment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_last_assignment` (`participant_id` INT, `assignment_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `participant_for_queue`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_for_queue` (`id` INT, `update_timestamp` INT, `create_timestamp` INT, `active` INT, `uid` INT, `source_id` INT, `first_name` INT, `last_name` INT, `status` INT, `language` INT, `site_id` INT, `prior_contact_date` INT, `city` INT, `region_id` INT, `postcode` INT, `phone_number_count` INT, `last_consent` INT, `last_assignment_id` INT, `base_site_id` INT, `assigned` INT, `current_qnaire_id` INT, `start_qnaire_date` INT);

-- -----------------------------------------------------
-- Placeholder table for view `assignment_last_phone_call`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `assignment_last_phone_call` (`assignment_id` INT, `phone_call_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `participant_last_consent`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_last_consent` (`participant_id` INT, `consent_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `participant_primary_address`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_primary_address` (`participant_id` INT, `address_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `participant_last_contacted_phone_call`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_last_contacted_phone_call` (`participant_id` INT, `phone_call_id` INT);

-- -----------------------------------------------------
-- View `participant_first_address`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `participant_first_address` ;
DROP TABLE IF EXISTS `participant_first_address`;
CREATE  OR REPLACE VIEW `participant_first_address` AS
SELECT participant_id, id AS address_id
FROM address AS t1
WHERE t1.rank = (
  SELECT MIN( t2.rank )
  FROM address AS t2
  WHERE t2.active
  AND t1.participant_id = t2.participant_id
  AND CASE MONTH( CURRENT_DATE() )
        WHEN 1 THEN t2.january
        WHEN 2 THEN t2.february
        WHEN 3 THEN t2.march
        WHEN 4 THEN t2.april
        WHEN 5 THEN t2.may
        WHEN 6 THEN t2.june
        WHEN 7 THEN t2.july
        WHEN 8 THEN t2.august
        WHEN 9 THEN t2.september
        WHEN 10 THEN t2.october
        WHEN 11 THEN t2.november
        WHEN 12 THEN t2.december
        ELSE 0 END = 1
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
AND assignment_1.start_datetime = (
  SELECT MAX( assignment_2.start_datetime )
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
       first_address.city,
       first_address.region_id,
       first_address.postcode,
       COUNT( DISTINCT phone.id ) as phone_number_count,
       consent.event AS last_consent,
       assignment.id AS last_assignment_id,
       IFNULL( participant.site_id, primary_region.site_id ) AS base_site_id,
       assignment.id IS NOT NULL AND assignment.end_datetime IS NULL AS assigned,
       IF( current_interview.id IS NULL,
           ( SELECT id FROM qnaire WHERE rank = 1 ),
           IF( current_interview.completed, next_qnaire.id, current_qnaire.id )
       ) AS current_qnaire_id,
       IF( current_interview.id IS NULL,
           IF( participant.prior_contact_date IS NULL,
               NULL,
               participant.prior_contact_date + INTERVAL(
                 SELECT delay FROM qnaire WHERE rank = 1
               ) WEEK ),
           IF( current_interview.completed,
               IF( next_qnaire.id IS NULL,
                   NULL,
                   IF( next_prev_assignment.end_datetime IS NULL,
                       participant.prior_contact_date,
                       next_prev_assignment.end_datetime
                   ) + INTERVAL next_qnaire.delay WEEK
               ),
               NULL
           )
       ) AS start_qnaire_date
FROM participant
LEFT JOIN phone
ON phone.participant_id = participant.id
AND phone.active
AND phone.number IS NOT NULL
LEFT JOIN participant_primary_address
ON participant.id = participant_primary_address.participant_id 
LEFT JOIN address AS primary_address
ON participant_primary_address.address_id = primary_address.id
LEFT JOIN region AS primary_region
ON primary_address.region_id = primary_region.id
LEFT JOIN participant_first_address
ON participant.id = participant_first_address.participant_id 
LEFT JOIN address AS first_address
ON participant_first_address.address_id = first_address.id
LEFT JOIN participant_last_consent
ON participant.id = participant_last_consent.participant_id 
LEFT JOIN consent
ON consent.id = participant_last_consent.consent_id
LEFT JOIN participant_last_assignment
ON participant.id = participant_last_assignment.participant_id 
LEFT JOIN assignment
ON participant_last_assignment.assignment_id = assignment.id
LEFT JOIN interview AS current_interview
ON current_interview.participant_id = participant.id
LEFT JOIN qnaire AS current_qnaire
ON current_qnaire.id = current_interview.qnaire_id
LEFT JOIN qnaire AS next_qnaire
ON next_qnaire.rank = ( current_qnaire.rank + 1 )
LEFT JOIN qnaire AS next_prev_qnaire
ON next_prev_qnaire.id = next_qnaire.prev_qnaire_id
LEFT JOIN interview AS next_prev_interview
ON next_prev_interview.qnaire_id = next_prev_qnaire.id
AND next_prev_interview.participant_id = participant.id
LEFT JOIN assignment next_prev_assignment
ON next_prev_assignment.interview_id = next_prev_interview.id
WHERE (
  current_qnaire.rank IS NULL OR
  current_qnaire.rank = (
    SELECT MAX( qnaire.rank )
    FROM interview, qnaire
    WHERE qnaire.id = interview.qnaire_id
    AND current_interview.participant_id = interview.participant_id
    GROUP BY current_interview.participant_id ) )
AND (
  next_prev_assignment.end_datetime IS NULL OR
  next_prev_assignment.end_datetime = (
    SELECT MAX( assignment.end_datetime )
    FROM interview, assignment
    WHERE interview.qnaire_id = next_prev_qnaire.id
    AND interview.id = assignment.interview_id
    AND next_prev_assignment.id = assignment.id
    GROUP BY next_prev_assignment.interview_id ) )
GROUP BY participant.id;

-- -----------------------------------------------------
-- View `assignment_last_phone_call`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `assignment_last_phone_call` ;
DROP TABLE IF EXISTS `assignment_last_phone_call`;
CREATE  OR REPLACE VIEW `assignment_last_phone_call` AS
SELECT assignment_1.id as assignment_id, phone_call_1.id as phone_call_id
FROM phone_call AS phone_call_1, assignment AS assignment_1
WHERE assignment_1.id = phone_call_1.assignment_id
AND phone_call_1.start_datetime = (
  SELECT MAX( phone_call_2.start_datetime )
  FROM phone_call AS phone_call_2, assignment AS assignment_2
  WHERE assignment_2.id = phone_call_2.assignment_id
  AND assignment_1.id = assignment_2.id
  AND phone_call_2.end_datetime IS NOT NULL
  GROUP BY assignment_2.id );

-- -----------------------------------------------------
-- View `participant_last_consent`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `participant_last_consent` ;
DROP TABLE IF EXISTS `participant_last_consent`;
CREATE  OR REPLACE VIEW `participant_last_consent` AS
SELECT participant_id, id AS consent_id
FROM consent AS t1
WHERE t1.date = (
  SELECT MAX( t2.date )
  FROM consent AS t2
  WHERE t1.participant_id = t2.participant_id
  GROUP BY t2.participant_id );

-- -----------------------------------------------------
-- View `participant_primary_address`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `participant_primary_address` ;
DROP TABLE IF EXISTS `participant_primary_address`;
CREATE  OR REPLACE VIEW `participant_primary_address` AS
SELECT participant_id, id AS address_id
FROM address AS t1
WHERE t1.rank = (
  SELECT MIN( t2.rank )
  FROM address AS t2, region
  WHERE t2.region_id = region.id
  AND t2.active
  AND region.site_id IS NOT NULL
  AND t1.participant_id = t2.participant_id
  GROUP BY t2.participant_id );

-- -----------------------------------------------------
-- View `participant_last_contacted_phone_call`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `participant_last_contacted_phone_call` ;
DROP TABLE IF EXISTS `participant_last_contacted_phone_call`;
CREATE  OR REPLACE VIEW `participant_last_contacted_phone_call` AS
SELECT interview_1.participant_id, phone_call_1.id as phone_call_id
FROM phone_call AS phone_call_1, assignment AS assignment_1, interview AS interview_1
WHERE phone_call_1.assignment_id = assignment_1.id
AND interview_1.id = assignment_1.interview_id
AND phone_call_1.start_datetime = (
  SELECT MAX( phone_call_2.start_datetime )
  FROM phone_call AS phone_call_2, assignment AS assignment_2, interview AS interview_2
  WHERE phone_call_2.status = "contact"
  AND phone_call_2.assignment_id = assignment_2.id
  AND assignment_2.interview_id = interview_2.id
  AND interview_1.participant_id = interview_2.participant_id
  GROUP BY interview_2.participant_id );


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
