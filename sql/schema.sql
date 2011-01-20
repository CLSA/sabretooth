SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

DROP SCHEMA IF EXISTS `sabretooth` ;
CREATE SCHEMA IF NOT EXISTS `sabretooth` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci ;
USE `sabretooth` ;

-- -----------------------------------------------------
-- Table `sabretooth`.`site`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`site` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`site` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_name` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`participant`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`participant` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`participant` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `first_name` VARCHAR(45) NOT NULL ,
  `last_name` VARCHAR(45) NOT NULL ,
  `language` ENUM('en','fr') NOT NULL DEFAULT 'en' ,
  `hin` VARCHAR(45) NULL DEFAULT NULL ,
  `condition` ENUM('deceased', 'deaf', 'mentally unfit') NULL DEFAULT NULL ,
  `site_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'If not null then force all calls to this participant to the site.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  CONSTRAINT `fk_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `sabretooth`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`lime_users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`lime_users` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`lime_users` (
  `uid` INT NOT NULL AUTO_INCREMENT ,
  PRIMARY KEY (`uid`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`user`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`user` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NOT NULL ,
  `lime_uid` INT NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) ,
  INDEX `fk_lime_users_uid` (`lime_uid` ASC) ,
  UNIQUE INDEX `uq_lime_uid` (`lime_uid` ASC) ,
  CONSTRAINT `fk_user_lime_users1`
    FOREIGN KEY (`lime_uid` )
    REFERENCES `sabretooth`.`lime_users` (`uid` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`role`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`role` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`role` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_name` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`lime_surveys`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`lime_surveys` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`lime_surveys` (
  `sid` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  PRIMARY KEY (`sid`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`qnaire`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`qnaire` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`qnaire` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`script`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`script` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`script` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `lime_sid` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_lime_surveys_sid` (`lime_sid` ASC) ,
  UNIQUE INDEX `uq_lime_sid` (`lime_sid` ASC) ,
  CONSTRAINT `fk_script_lime_surveys1`
    FOREIGN KEY (`lime_sid` )
    REFERENCES `sabretooth`.`lime_surveys` (`sid` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`qnaire_stage`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`qnaire_stage` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`qnaire_stage` (
  `qnaire_id` INT UNSIGNED NOT NULL ,
  `stage` SMALLINT UNSIGNED NOT NULL ,
  `script_id` INT UNSIGNED NOT NULL ,
  `repeated` TINYINT(1)  NOT NULL DEFAULT false ,
  PRIMARY KEY (`qnaire_id`, `stage`) ,
  INDEX `fk_script` (`script_id` ASC) ,
  INDEX `fk_qnaire` (`qnaire_id` ASC) ,
  CONSTRAINT `fk_qnaire_has_script_qnaire1`
    FOREIGN KEY (`qnaire_id` )
    REFERENCES `sabretooth`.`qnaire` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_qnaire_has_script_script1`
    FOREIGN KEY (`script_id` )
    REFERENCES `sabretooth`.`script` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`contact`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`contact` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`contact` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `active` TINYINT(1)  NOT NULL DEFAULT true ,
  `rank` INT NOT NULL ,
  `phone` VARCHAR(20) NULL DEFAULT NULL ,
  `type` ENUM('home','home2','work','work2','cell','cell2','other') NULL DEFAULT NULL ,
  `address1` VARCHAR(512) NULL DEFAULT NULL ,
  `address2` VARCHAR(512) NULL DEFAULT NULL ,
  `city` VARCHAR(45) NULL DEFAULT NULL COMMENT 'If outside Canada, this should contain state and/or region as well.' ,
  `province` ENUM('AB','BC','MB','NB','NL','NT','NS','NU','ON','PE','QC','SK','YT') NULL DEFAULT NULL COMMENT 'Only filled out if address is in Canada.  This is used to determine which site calls the participant.' ,
  `country` VARCHAR(20) NULL DEFAULT NULL ,
  `postcode` VARCHAR(20) NULL DEFAULT NULL ,
  `note` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  UNIQUE INDEX `uq_participant_id_active_order` (`participant_id` ASC, `active` ASC, `rank` ASC) ,
  CONSTRAINT `fk_contact_information_participant1`
    FOREIGN KEY (`participant_id` )
    REFERENCES `sabretooth`.`participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`interview`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`interview` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`interview` (
  `participant_id` INT UNSIGNED NOT NULL ,
  `qnaire_id` INT UNSIGNED NOT NULL ,
  `qnaire_stage` SMALLINT UNSIGNED NOT NULL ,
  `require_supervisor` TINYINT(1)  NOT NULL DEFAULT false ,
  `completed` TINYINT(1)  NOT NULL DEFAULT false ,
  `site_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'If not null then force all calls for this interview to the site.' ,
  `appointment` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`participant_id`, `qnaire_id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `fk_qnaire_stage_qnaire_id1` (`qnaire_id` ASC, `qnaire_stage` ASC) ,
  CONSTRAINT `fk_participant_id1`
    FOREIGN KEY (`participant_id` )
    REFERENCES `sabretooth`.`participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_site_id2`
    FOREIGN KEY (`site_id` )
    REFERENCES `sabretooth`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_qnaire_stage_qnaire_id1`
    FOREIGN KEY (`qnaire_id` , `qnaire_stage` )
    REFERENCES `sabretooth`.`qnaire_stage` (`qnaire_id` , `stage` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'aka: qnaire_has_participant';


-- -----------------------------------------------------
-- Table `sabretooth`.`interview_queue`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`interview_queue` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`interview_queue` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NOT NULL ,
  `query` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`assignment`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`assignment` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`assignment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `qnaire_id` INT UNSIGNED NOT NULL ,
  `interview_queue_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'What queue did the interview get assigned from?' ,
  `start_time` TIMESTAMP NOT NULL ,
  `end_time` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_assignment_interview1` (`participant_id` ASC, `qnaire_id` ASC) ,
  INDEX `fk_assignment_user1` (`user_id` ASC) ,
  INDEX `fk_assignment_interview_queue1` (`interview_queue_id` ASC) ,
  CONSTRAINT `fk_assignment_interview1`
    FOREIGN KEY (`participant_id` , `qnaire_id` )
    REFERENCES `sabretooth`.`interview` (`participant_id` , `qnaire_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `sabretooth`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_interview_queue1`
    FOREIGN KEY (`interview_queue_id` )
    REFERENCES `sabretooth`.`interview_queue` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`phone_call`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`phone_call` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`phone_call` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `assignment_id` INT UNSIGNED NOT NULL ,
  `contact_id` INT UNSIGNED NOT NULL ,
  `status` ENUM('in progress','contacted', 'busy','no answer','machine message','machine no message','fax','disconnected','wrong number','language') NOT NULL DEFAULT 'in progress' ,
  `start_time` TIMESTAMP NOT NULL COMMENT 'The time the call started.' ,
  `start_qnaire_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'What qnaire stage the call starts with.  Null for calls not associated with a qnaire.' ,
  `start_qnaire_stage` SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'What qnaire stage the call starts with.  Null for calls not associated with a qnaire.' ,
  `end_time` DATETIME NULL DEFAULT NULL COMMENT 'The time the call endede.' ,
  `end_qnaire_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'What qnaire stage the call ends with.  Null for calls not associated with a qnaire.' ,
  `end_qnaire_stage` SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'What qnaire stage the call ends with.  Null for calls not associated with a qnaire.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_contact_id` (`contact_id` ASC) ,
  INDEX `fk_start_qnaire_stage` (`start_qnaire_id` ASC, `start_qnaire_stage` ASC) ,
  INDEX `fk_end_qnaire_stage` (`end_qnaire_id` ASC, `end_qnaire_stage` ASC) ,
  INDEX `fk_phone_call_assignment1` (`assignment_id` ASC) ,
  CONSTRAINT `fk_phone_call_contact_information1`
    FOREIGN KEY (`contact_id` )
    REFERENCES `sabretooth`.`contact` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_qnaire_stage2_qnaire_id1`
    FOREIGN KEY (`start_qnaire_id` , `start_qnaire_stage` )
    REFERENCES `sabretooth`.`qnaire_stage` (`qnaire_id` , `stage` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_qnaire_stage2_qnaire_id11`
    FOREIGN KEY (`end_qnaire_id` , `end_qnaire_stage` )
    REFERENCES `sabretooth`.`qnaire_stage` (`qnaire_id` , `stage` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_phone_call_assignment1`
    FOREIGN KEY (`assignment_id` )
    REFERENCES `sabretooth`.`assignment` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`operation`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`operation` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`operation` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NULL ,
  `reval` ENUM('remove','edit','view','add','list') NULL ,
  `description` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_name_reval` (`name` ASC, `reval` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`role_has_operation`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`role_has_operation` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`role_has_operation` (
  `role_id` INT UNSIGNED NOT NULL ,
  `operation_id` INT NOT NULL ,
  PRIMARY KEY (`role_id`, `operation_id`) ,
  INDEX `fk_operation_id` (`operation_id` ASC) ,
  INDEX `fk_role_id` (`role_id` ASC) ,
  CONSTRAINT `fk_role_has_operation_role1`
    FOREIGN KEY (`role_id` )
    REFERENCES `sabretooth`.`role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_role_has_operation_operation1`
    FOREIGN KEY (`operation_id` )
    REFERENCES `sabretooth`.`operation` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`consent`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`consent` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`consent` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `event` ENUM('verbal accept','verbal deny','written accept','written deny','retract','mail request','mail sent') NOT NULL ,
  `date` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  CONSTRAINT `fk_consent_participant1`
    FOREIGN KEY (`participant_id` )
    REFERENCES `sabretooth`.`participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`sample`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`sample` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`sample` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`sample_has_participant`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`sample_has_participant` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`sample_has_participant` (
  `sample_id` INT UNSIGNED NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`sample_id`, `participant_id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_sample_id` (`sample_id` ASC) ,
  CONSTRAINT `fk_sample_has_participant_sample1`
    FOREIGN KEY (`sample_id` )
    REFERENCES `sabretooth`.`sample` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_sample_has_participant_participant1`
    FOREIGN KEY (`participant_id` )
    REFERENCES `sabretooth`.`participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`availability`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`availability` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`availability` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `day_of_week` SET('mon','tue','wed','thu','fri','sat','sun') NOT NULL ,
  `period_start` TIME NOT NULL ,
  `period_end` TIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  CONSTRAINT `fk_appointment_participant10`
    FOREIGN KEY (`participant_id` )
    REFERENCES `sabretooth`.`participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`qnaire_has_sample`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`qnaire_has_sample` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`qnaire_has_sample` (
  `qnaire_id` INT UNSIGNED NOT NULL ,
  `sample_id` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`qnaire_id`, `sample_id`) ,
  INDEX `fk_sample_id` (`sample_id` ASC) ,
  INDEX `fk_qnaire_id` (`qnaire_id` ASC) ,
  CONSTRAINT `fk_qnaire_has_sample_qnaire1`
    FOREIGN KEY (`qnaire_id` )
    REFERENCES `sabretooth`.`qnaire` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_qnaire_has_sample_sample1`
    FOREIGN KEY (`sample_id` )
    REFERENCES `sabretooth`.`sample` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`qnaire_note`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`qnaire_note` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`qnaire_note` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `qnaire_id` INT UNSIGNED NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `sticky` TINYINT(1)  NOT NULL DEFAULT false ,
  `date` TIMESTAMP NOT NULL ,
  `description` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_qnaire_id` (`qnaire_id` ASC) ,
  CONSTRAINT `fk_participant_note_copy1_user11`
    FOREIGN KEY (`user_id` )
    REFERENCES `sabretooth`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_interview_note_copy1_qnaire1`
    FOREIGN KEY (`qnaire_id` )
    REFERENCES `sabretooth`.`qnaire` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`shift`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`shift` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`shift` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `site_id` INT UNSIGNED NOT NULL ,
  `date` DATE NOT NULL ,
  `start_time` TIME NOT NULL ,
  `end_time` TIME NOT NULL ,
  `operators` INT UNSIGNED NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_site_id1` (`site_id` ASC) ,
  CONSTRAINT `fk_site_id1`
    FOREIGN KEY (`site_id` )
    REFERENCES `sabretooth`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`user_access`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`user_access` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`user_access` (
  `user_id` INT UNSIGNED NOT NULL ,
  `role_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`user_id`, `role_id`, `site_id`) ,
  INDEX `fk_user_has_role_role1` (`role_id` ASC) ,
  INDEX `fk_user_has_role_user1` (`user_id` ASC) ,
  INDEX `fk_user_has_role_site1` (`site_id` ASC) ,
  CONSTRAINT `fk_user_has_role_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `sabretooth`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_has_role_role1`
    FOREIGN KEY (`role_id` )
    REFERENCES `sabretooth`.`role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_has_role_site1`
    FOREIGN KEY (`site_id` )
    REFERENCES `sabretooth`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`participant_note`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`participant_note` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`participant_note` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `sticky` TINYINT(1)  NOT NULL DEFAULT false ,
  `date` TIMESTAMP NOT NULL ,
  `note` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  CONSTRAINT `fk_participant_note_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `sabretooth`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_participant_note_participant1`
    FOREIGN KEY (`participant_id` )
    REFERENCES `sabretooth`.`participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`interview_note`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`interview_note` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`interview_note` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `interview_qnaire_id` INT UNSIGNED NOT NULL ,
  `interview_participant_id` INT UNSIGNED NOT NULL ,
  `sticky` TINYINT(1)  NOT NULL DEFAULT false ,
  `date` TIMESTAMP NOT NULL ,
  `note` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_interview_id` (`interview_qnaire_id` ASC, `interview_participant_id` ASC) ,
  CONSTRAINT `fk_participant_note_user10`
    FOREIGN KEY (`user_id` )
    REFERENCES `sabretooth`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_interview_qnaire_id1`
    FOREIGN KEY (`interview_participant_id` )
    REFERENCES `sabretooth`.`interview` (`participant_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`phone_call_note`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`phone_call_note` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`phone_call_note` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `phone_call_id` INT UNSIGNED NOT NULL ,
  `sticky` TINYINT(1)  NOT NULL DEFAULT false ,
  `date` TIMESTAMP NOT NULL ,
  `note` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_phone_call_id` (`phone_call_id` ASC) ,
  CONSTRAINT `fk_participant_note_user100`
    FOREIGN KEY (`user_id` )
    REFERENCES `sabretooth`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_phone_call_phone_call_id1`
    FOREIGN KEY (`phone_call_id` )
    REFERENCES `sabretooth`.`phone_call` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`site_has_interview_queue`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`site_has_interview_queue` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`site_has_interview_queue` (
  `site_id` INT UNSIGNED NOT NULL ,
  `interview_queue_id` INT UNSIGNED NOT NULL ,
  `active` TINYINT(1)  NOT NULL DEFAULT true ,
  `priority` TINYINT NOT NULL ,
  PRIMARY KEY (`site_id`, `interview_queue_id`) ,
  INDEX `fk_site_has_queue_queue1` (`interview_queue_id` ASC) ,
  INDEX `fk_site_has_queue_site1` (`site_id` ASC) ,
  UNIQUE INDEX `uq_site_queue_priority` (`site_id` ASC, `interview_queue_id` ASC, `priority` ASC) ,
  CONSTRAINT `fk_site_has_queue_site1`
    FOREIGN KEY (`site_id` )
    REFERENCES `sabretooth`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_site_has_queue_queue1`
    FOREIGN KEY (`interview_queue_id` )
    REFERENCES `sabretooth`.`interview_queue` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sabretooth`.`log`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sabretooth`.`log` ;

CREATE  TABLE IF NOT EXISTS `sabretooth`.`log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  `date` TIMESTAMP NOT NULL ,
  `text` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_log_user1` (`user_id` ASC) ,
  INDEX `fk_log_site1` (`site_id` ASC) ,
  CONSTRAINT `fk_log_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `sabretooth`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_log_site1`
    FOREIGN KEY (`site_id` )
    REFERENCES `sabretooth`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Placeholder table for view `sabretooth`.`participant_primary_location`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sabretooth`.`participant_primary_location` (`participant_id` INT, `contact_id` INT, `province` INT);

-- -----------------------------------------------------
-- Placeholder table for view `sabretooth`.`participant_current_consent`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sabretooth`.`participant_current_consent` (`participant_id` INT, `consent_id` INT, `consent` INT);

-- -----------------------------------------------------
-- Placeholder table for view `sabretooth`.`participant_last_phone_call_status`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sabretooth`.`participant_last_phone_call_status` (`phone_call_id` INT, `participant_id` INT, `status` INT);

-- -----------------------------------------------------
-- View `sabretooth`.`participant_primary_location`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `sabretooth`.`participant_primary_location` ;
DROP TABLE IF EXISTS `sabretooth`.`participant_primary_location`;
USE `sabretooth`;
CREATE  OR REPLACE VIEW `sabretooth`.`participant_primary_location` AS
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
-- View `sabretooth`.`participant_current_consent`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `sabretooth`.`participant_current_consent` ;
DROP TABLE IF EXISTS `sabretooth`.`participant_current_consent`;
USE `sabretooth`;
CREATE  OR REPLACE VIEW `sabretooth`.`participant_current_consent` AS
SELECT participant_id, id AS consent_id, event IN( 'verbal accept', 'written accept' ) AS consent
FROM consent AS t1
WHERE t1.date = (
  SELECT MAX( t2.date )
  FROM consent AS t2
  WHERE t2.event IN ( 'verbal accept','verbal deny','written accept','written deny','retract' )
  AND t1.participant_id = t2.participant_id
  GROUP BY t2.participant_id );

-- -----------------------------------------------------
-- View `sabretooth`.`participant_last_phone_call_status`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `sabretooth`.`participant_last_phone_call_status` ;
DROP TABLE IF EXISTS `sabretooth`.`participant_last_phone_call_status`;
USE `sabretooth`;
CREATE  OR REPLACE VIEW `sabretooth`.`participant_last_phone_call_status` AS
SELECT phone_call_1.id AS phone_call_id, contact_1.participant_id, phone_call_1.status
FROM phone_call AS phone_call_1, contact AS contact_1
WHERE contact_1.id = phone_call_1.contact_id
AND phone_call_1.start_time = (
  SELECT MAX( phone_call_2.start_time )
  FROM phone_call AS phone_call_2, contact AS contact_2
  WHERE contact_2.id = phone_call_2.contact_id
  AND contact_1.participant_id = contact_2.participant_id
  GROUP BY contact_2.participant_id );


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
