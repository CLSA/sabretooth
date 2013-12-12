-- 
-- Cross-application database amalgamation redesign
-- This script converts pre version 1.2 databases to the new amalgamated design
-- 

-- change the cohort column to a varchar
DROP PROCEDURE IF EXISTS convert_database;
DELIMITER //
CREATE PROCEDURE convert_database()
  BEGIN
    SET @test = (
      SELECT COUNT( * )
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "participant" );
    IF @test = 1 THEN

      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
      SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';

      -- determine the @cenozo database name
      SET @cenozo = REPLACE( DATABASE(), 'sabretooth', 'cenozo' );

      -- qnaire ------------------------------------------------------------------------------------
      SELECT "Processing qnaire" AS "";
      ALTER TABLE qnaire DROP FOREIGN KEY fk_qnaire_prev_qnaire;
      ALTER TABLE qnaire
      ADD CONSTRAINT fk_qnaire_prev_qnaire_id
      FOREIGN KEY ( prev_qnaire_id ) REFERENCES qnaire ( id )
      ON DELETE NO ACTION ON UPDATE NO ACTION;

      -- qnaire_has_event_type ---------------------------------------------------------------------
      SELECT "Processing qnaire_has_event_type" AS "";
      SET @sql = CONCAT(
        "CREATE  TABLE IF NOT EXISTS qnaire_has_event_type ( ",
          "qnaire_id INT UNSIGNED NOT NULL , ",
          "event_type_id INT UNSIGNED NOT NULL , ",
          "update_timestamp TIMESTAMP NOT NULL , ",
          "create_timestamp TIMESTAMP NOT NULL , ",
          "PRIMARY KEY ( qnaire_id, event_type_id ) , ",
          "INDEX fk_event_type_id ( event_type_id ASC ) , ",
          "INDEX fk_qnaire_id ( qnaire_id ASC ) , ",
          "CONSTRAINT fk_qnaire_has_event_type_qnaire_id ",
            "FOREIGN KEY ( qnaire_id ) ",
            "REFERENCES qnaire ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_qnaire_has_event_type_event_type_id ",
            "FOREIGN KEY ( event_type_id ) ",
            "REFERENCES ", @cenozo, ".event_type ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- phase -------------------------------------------------------------------------------------
      SELECT "Processing phase" AS "";
      ALTER TABLE phase DROP FOREIGN KEY fk_phase_qnaire;
      ALTER TABLE phase
      ADD CONSTRAINT fk_phase_qnaire_id
      FOREIGN KEY ( qnaire_id ) REFERENCES qnaire ( id )
      ON DELETE NO ACTION ON UPDATE NO ACTION;

      -- interview ---------------------------------------------------------------------------------
      SELECT "Processing interview" AS "";
      ALTER TABLE interview RENAME interview_old;
      ALTER TABLE interview_old
      DROP FOREIGN KEY fk_interiew_duplicate_qnaire_id,
      DROP FOREIGN KEY fk_interview_participant,
      DROP FOREIGN KEY fk_interview_qnaire_id;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS interview ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "qnaire_id INT UNSIGNED NOT NULL, ",
          "participant_id INT UNSIGNED NOT NULL, ",
          "require_supervisor TINYINT( 1 ) NOT NULL DEFAULT 0, ",
          "completed TINYINT( 1 ) NOT NULL DEFAULT 0, ",
          "rescored ENUM( 'Yes','No','N/A' ) NOT NULL DEFAULT 'N/A', ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_participant_id ( participant_id ASC ), ",
          "INDEX fk_qnaire_id ( qnaire_id ASC ), ",
          "INDEX dk_completed ( completed ASC ), ",
          "UNIQUE INDEX uq_participant_id_qnaire_id ( participant_id ASC, qnaire_id ASC ), ",
          "INDEX dk_rescored ( rescored ASC ), ",
          "CONSTRAINT fk_interview_participant_id ",
            "FOREIGN KEY ( participant_id ) ",
            "REFERENCES ", @cenozo, ".participant ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_interview_qnaire_id ",
            "FOREIGN KEY ( qnaire_id ) ",
            "REFERENCES qnaire ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB ",
        "COMMENT = 'aka: qnaire_has_participant'" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO interview ( id, update_timestamp, create_timestamp, qnaire_id, ",
                                "participant_id, completed, rescored ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, old.qnaire_id, ",
               "cparticipant.id, old.completed, old.rescored ",
        "FROM interview_old old ",
        "JOIN participant ON old.participant_id = participant.id ",
        "JOIN ", @cenozo, ".participant cparticipant ON participant.uid = cparticipant.uid" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE interview_old;

      -- assignment --------------------------------------------------------------------------------
      SELECT "Processing assignment" AS "";
      ALTER TABLE assignment RENAME assignment_old;
      ALTER TABLE assignment_old
      DROP FOREIGN KEY fk_assignment_interview,
      DROP FOREIGN KEY fk_assignment_queue_id,
      DROP FOREIGN KEY fk_assignment_site,
      DROP FOREIGN KEY fk_assignment_user;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS assignment ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "user_id INT UNSIGNED NOT NULL, ",
          "site_id INT UNSIGNED NOT NULL COMMENT 'The site from which the user was assigned.', ",
          "interview_id INT UNSIGNED NOT NULL, ",
          "queue_id INT UNSIGNED NOT NULL COMMENT 'The queue that the assignment came from.', ",
          "start_datetime DATETIME NOT NULL, ",
          "end_datetime DATETIME NULL DEFAULT NULL, ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_interview_id ( interview_id ASC ), ",
          "INDEX fk_queue_id ( queue_id ASC ), ",
          "INDEX dk_start_datetime ( start_datetime ASC ), ",
          "INDEX dk_end_datetime ( end_datetime ASC ), ",
          "INDEX fk_user_id ( user_id ASC ), ",
          "INDEX fk_site_id ( site_id ASC ), ",
          "CONSTRAINT fk_assignment_interview_id ",
            "FOREIGN KEY ( interview_id ) ",
            "REFERENCES interview ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_assignment_queue_id ",
            "FOREIGN KEY ( queue_id ) ",
            "REFERENCES queue ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_assignment_user_id ",
            "FOREIGN KEY ( user_id ) ",
            "REFERENCES ", @cenozo, ".user ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_assignment_site_id ",
            "FOREIGN KEY ( site_id ) ",
            "REFERENCES ", @cenozo, ".site ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO assignment ( id, update_timestamp, create_timestamp, user_id, site_id, ",
                                 "interview_id, queue_id, start_datetime, end_datetime ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, cuser.id, csite.id, ",
               "old.interview_id, old.queue_id, old.start_datetime, old.end_datetime ",
        "FROM assignment_old old ",
        "JOIN user ON old.user_id = user.id ",
        "JOIN ", @cenozo, ".user cuser ON user.name = cuser.name ",
        "JOIN site ON old.site_id = site.id ",
        "JOIN ", @cenozo, ".site csite ON site.name = csite.name ",
        "AND csite.service_id = ( SELECT id FROM ", @cenozo, ".service WHERE title = 'Sabretooth' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE assignment_old;

      -- phone_call --------------------------------------------------------------------------------
      SELECT "Processing phone_call" AS "";
      ALTER TABLE phone_call RENAME phone_call_old;
      ALTER TABLE phone_call_old
      DROP FOREIGN KEY fk_phone_call_assignment,
      DROP FOREIGN KEY fk_phone_call_phone_id;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS phone_call ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "assignment_id INT UNSIGNED NOT NULL, ",
          "phone_id INT UNSIGNED NOT NULL, ",
          "start_datetime DATETIME NOT NULL COMMENT 'The time the call started.', ",
          "end_datetime DATETIME NULL DEFAULT NULL COMMENT 'The time the call endede.', ",
          "status ENUM( 'contacted','busy','no answer','machine message','machine no message',",
                       "'fax','disconnected','wrong number','not reached','hang up','soft refusal' )",
                       " NULL DEFAULT NULL, ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_assignment_id ( assignment_id ASC ), ",
          "INDEX dk_status ( status ASC ), ",
          "INDEX fk_phone_id ( phone_id ASC ), ",
          "INDEX dk_start_datetime ( start_datetime ASC ), ",
          "INDEX dk_end_datetime ( end_datetime ASC ), ",
          "CONSTRAINT fk_phone_call_assignment_id ",
            "FOREIGN KEY ( assignment_id ) ",
            "REFERENCES assignment ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_phone_call_phone_id ",
            "FOREIGN KEY ( phone_id ) ",
            "REFERENCES ", @cenozo, ".phone ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO phone_call ( id, update_timestamp, create_timestamp, assignment_id, ",
                                 "phone_id, start_datetime, end_datetime, status ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, old.assignment_id, ",
               "cphone.id, old.start_datetime, old.end_datetime, old.status ",
        "FROM phone_call_old old ",
        "JOIN phone ON old.phone_id = phone.id ",
        "JOIN participant ON phone.participant_id = participant.id ",
        "JOIN ", @cenozo, ".participant cparticipant ON participant.uid = cparticipant.uid ",
        "JOIN ", @cenozo, ".phone cphone ON phone.rank = cphone.rank ",
        "AND cparticipant.person_id = cphone.person_id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE phone_call_old;

      -- shift -------------------------------------------------------------------------------------
      SELECT "Processing shift" AS "";
      ALTER TABLE shift RENAME shift_old;
      ALTER TABLE shift_old
      DROP FOREIGN KEY fk_shift_site,
      DROP FOREIGN KEY fk_shift_user;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS shift ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "site_id INT UNSIGNED NOT NULL, ",
          "user_id INT UNSIGNED NOT NULL, ",
          "start_datetime DATETIME NOT NULL, ",
          "end_datetime DATETIME NOT NULL, ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_site_id ( site_id ASC ), ",
          "INDEX fk_user_id ( user_id ASC ), ",
          "INDEX dk_start_datetime ( start_datetime ASC ), ",
          "INDEX dk_end_datetime ( end_datetime ASC ), ",
          "CONSTRAINT fk_shift_site_id ",
            "FOREIGN KEY ( site_id ) ",
            "REFERENCES ", @cenozo, ".site ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_shift_user_id ",
            "FOREIGN KEY ( user_id ) ",
            "REFERENCES ", @cenozo, ".user ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO shift( id, update_timestamp, create_timestamp, ",
                           "site_id, user_id, start_datetime, end_datetime ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, ",
               "csite.id, cuser.id, old.start_datetime, old.end_datetime ",
        "FROM shift_old old ",
        "JOIN user ON old.user_id = user.id ",
        "JOIN ", @cenozo, ".user cuser ON user.name = cuser.name ",
        "JOIN site ON old.site_id = site.id ",
        "JOIN ", @cenozo, ".site csite ON site.name = csite.name ",
        "AND csite.service_id = ( SELECT id FROM ", @cenozo, ".service WHERE title = 'Sabretooth' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE shift_old;

      -- assignment_note ---------------------------------------------------------------------------
      SELECT "Processing assignment_note" AS "";
      ALTER TABLE assignment_note RENAME assignment_note_old;
      ALTER TABLE assignment_note_old
      DROP FOREIGN KEY fk_assignment_note_assignment,
      DROP FOREIGN KEY fk_assignment_note_user;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS assignment_note ( ",
          "id INT NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "user_id INT UNSIGNED NOT NULL, ",
          "assignment_id INT UNSIGNED NOT NULL, ",
          "sticky TINYINT( 1 ) NOT NULL DEFAULT 0, ",
          "datetime DATETIME NOT NULL, ",
          "note TEXT NOT NULL, ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_assignment_id ( assignment_id ASC ), ",
          "INDEX fk_user_id ( user_id ASC ), ",
          "INDEX dk_sticky_datetime ( sticky ASC, datetime ASC ), ",
          "CONSTRAINT fk_assignment_note_assignment_id ",
            "FOREIGN KEY ( assignment_id ) ",
            "REFERENCES assignment ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_assignment_note_user_id ",
            "FOREIGN KEY ( user_id ) ",
            "REFERENCES ", @cenozo, ".user ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO assignment_note( id, update_timestamp, create_timestamp, user_id, ",
                                     "assignment_id, sticky, datetime, note ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, cuser.id, ",
               "old.assignment_id, old.sticky, old.datetime, old.note ",
        "FROM assignment_note_old old ",
        "JOIN user ON old.user_id = user.id ",
        "JOIN ", @cenozo, ".user cuser ON user.name = cuser.name" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE assignment_note_old;

      -- appointment -------------------------------------------------------------------------------
      SELECT "Processing appointment" AS "";
      ALTER TABLE appointment RENAME appointment_old;
      ALTER TABLE appointment_old
      DROP FOREIGN KEY fk_appointment_assignment,
      DROP FOREIGN KEY fk_appointment_participant,
      DROP FOREIGN KEY fk_appointment_phone_id;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS appointment ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "participant_id INT UNSIGNED NOT NULL, ",
          "phone_id INT UNSIGNED NULL DEFAULT NULL, ",
          "assignment_id INT UNSIGNED NULL DEFAULT NULL, ",
          "datetime DATETIME NOT NULL, ",
          "type ENUM( 'full','half' ) NOT NULL DEFAULT 'full', ",
          "reached TINYINT( 1 ) NULL DEFAULT NULL ",
          "COMMENT 'If the appointment was met, whether the participant was reached.', ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_participant_id ( participant_id ASC ), ",
          "INDEX fk_assignment_id ( assignment_id ASC ), ",
          "INDEX dk_reached ( reached ASC ), ",
          "INDEX fk_phone_id ( phone_id ASC ), ",
          "INDEX dk_datetime ( datetime ASC ), ",
          "CONSTRAINT fk_appointment_participant_id ",
            "FOREIGN KEY ( participant_id ) ",
            "REFERENCES ", @cenozo, ".participant ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_appointment_assignment_id ",
            "FOREIGN KEY ( assignment_id ) ",
            "REFERENCES assignment ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_appointment_phone_id ",
            "FOREIGN KEY ( phone_id ) ",
            "REFERENCES ", @cenozo, ".phone ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO appointment( id, update_timestamp, create_timestamp, participant_id, phone_id, ",
                                 "assignment_id, datetime, type, reached ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, cparticipant.id, cphone.id, ",
               "old.assignment_id, old.datetime, old.type, old.reached ",
        "FROM appointment_old old ",
        "JOIN participant ON old.participant_id = participant.id ",
        "JOIN ", @cenozo, ".participant cparticipant ON participant.uid = cparticipant.uid ",
        "LEFT JOIN phone ON old.phone_id = phone.id ",
        "LEFT JOIN ", @cenozo, ".phone cphone ON phone.rank = cphone.rank ",
        "AND cparticipant.person_id = cphone.person_id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE appointment_old;

      -- away_time ---------------------------------------------------------------------------------
      SELECT "Processing away_time" AS "";
      ALTER TABLE away_time RENAME away_time_old;
      ALTER TABLE away_time_old
      DROP FOREIGN KEY fk_away_time_role_id,
      DROP FOREIGN KEY fk_away_time_site_id,
      DROP FOREIGN KEY fk_away_time_user;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS away_time ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "user_id INT UNSIGNED NOT NULL, ",
          "site_id INT UNSIGNED NOT NULL, ",
          "role_id INT UNSIGNED NOT NULL, ",
          "start_datetime DATETIME NOT NULL, ",
          "end_datetime DATETIME NULL DEFAULT NULL, ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_user_id ( user_id ASC ), ",
          "INDEX dk_start_datetime ( start_datetime ASC ), ",
          "INDEX dk_end_datetime ( end_datetime ASC ), ",
          "INDEX fk_site_id ( site_id ASC ), ",
          "INDEX fk_role_id ( role_id ASC ), ",
          "CONSTRAINT fk_away_time_user_id ",
            "FOREIGN KEY ( user_id ) ",
            "REFERENCES ", @cenozo, ".user ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_away_time_site_id ",
            "FOREIGN KEY ( site_id ) ",
            "REFERENCES ", @cenozo, ".site ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_away_time_role_id ",
            "FOREIGN KEY ( role_id ) ",
            "REFERENCES ", @cenozo, ".role ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO away_time( id, update_timestamp, create_timestamp, user_id, ",
                               "site_id, role_id, start_datetime, end_datetime ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, cuser.id, ",
               "csite.id, crole.id, old.start_datetime, old.end_datetime ",
        "FROM away_time_old old ",
        "JOIN user ON old.user_id = user.id ",
        "JOIN ", @cenozo, ".user cuser ON user.name = cuser.name ",
        "JOIN site ON old.site_id = site.id ",
        "JOIN ", @cenozo, ".site csite ON site.name = csite.name ",
        "AND csite.service_id = ( SELECT id FROM ", @cenozo, ".service WHERE title = 'Sabretooth' )",
        "JOIN role ON old.role_id = role.id ",
        "JOIN ", @cenozo, ".role crole ON role.name = crole.name" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE away_time_old;

      -- shift_template --------------------------------------------------------------------------------
      SELECT "Processing shift_template" AS "";
      ALTER TABLE shift_template RENAME shift_template_old;
      ALTER TABLE shift_template_old
      DROP FOREIGN KEY fk_shift_template_site;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS shift_template ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "site_id INT UNSIGNED NOT NULL, ",
          "start_time TIME NOT NULL, ",
          "end_time TIME NOT NULL, ",
          "start_date DATE NOT NULL, ",
          "end_date DATE NULL DEFAULT NULL, ",
          "operators INT UNSIGNED NOT NULL, ",
          "repeat_type ENUM( 'weekly','day of month','day of week' ) NOT NULL DEFAULT 'weekly', ",
          "repeat_every INT NOT NULL DEFAULT 1, ",
          "monday TINYINT( 1 ) NOT NULL DEFAULT 0, ",
          "tuesday TINYINT( 1 ) NOT NULL DEFAULT 0, ",
          "wednesday TINYINT( 1 ) NOT NULL DEFAULT 0, ",
          "thursday TINYINT( 1 ) NOT NULL DEFAULT 0, ",
          "friday TINYINT( 1 ) NOT NULL DEFAULT 0, ",
          "saturday TINYINT( 1 ) NOT NULL DEFAULT 0, ",
          "sunday TINYINT( 1 ) NOT NULL DEFAULT 0, ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_site_id ( site_id ASC ), ",
          "INDEX dk_start_time ( start_time ASC ), ",
          "INDEX dk_end_time ( end_time ASC ), ",
          "INDEX dk_start_date ( start_date ASC ), ",
          "INDEX dk_end_date ( end_date ASC ), ",
          "CONSTRAINT fk_shift_template_site_id ",
            "FOREIGN KEY ( site_id ) ",
            "REFERENCES ", @cenozo, ".site ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO shift_template( id, update_timestamp, create_timestamp, site_id, start_time, end_time, ",
                                    "start_date, end_date, operators, repeat_type, repeat_every, monday, ",
                                    "tuesday, wednesday, thursday, friday, saturday, sunday ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, csite.id, old.start_time, old.end_time, ",
               "old.start_date, old.end_date, old.operators, old.repeat_type, old.repeat_every, old.monday, ",
               "old.tuesday, old.wednesday, old.thursday, old.friday, old.saturday, old.sunday ",
        "FROM shift_template_old old ",
        "JOIN site ON old.site_id = site.id ",
        "JOIN ", @cenozo, ".site csite ON site.name = csite.name ",
        "AND csite.service_id = ( SELECT id FROM ", @cenozo, ".service WHERE title = 'Sabretooth' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE shift_template_old;

      -- queue_restriction --------------------------------------------------------------------------------
      SELECT "Processing queue_restriction" AS "";
      ALTER TABLE queue_restriction RENAME queue_restriction_old;
      ALTER TABLE queue_restriction_old
      DROP FOREIGN KEY fk_region_id1,
      DROP FOREIGN KEY fk_site_id1;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS queue_restriction ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "site_id INT UNSIGNED NULL DEFAULT NULL, ",
          "city VARCHAR( 100 ) NULL DEFAULT NULL, ",
          "region_id INT UNSIGNED NULL DEFAULT NULL, ",
          "postcode VARCHAR( 10 ) NULL DEFAULT NULL, ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_region_id ( region_id ASC ), ",
          "INDEX fk_site_id ( site_id ASC ), ",
          "INDEX dk_city ( city ASC ), ",
          "INDEX dk_postcode ( postcode ASC ), ",
          "CONSTRAINT fk_queue_restriction_region_id ",
            "FOREIGN KEY ( region_id ) ",
            "REFERENCES ", @cenozo, ".region ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_queue_restriction_site_id ",
            "FOREIGN KEY ( site_id ) ",
            "REFERENCES ", @cenozo, ".site ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO queue_restriction( id, update_timestamp, create_timestamp, ",
                                       "site_id, city, region_id, postcode ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, ",
               "csite.id, old.city, cregion.id, old.postcode ",
        "FROM queue_restriction_old old ",
        "LEFT JOIN region ON old.region_id = region.id ",
        "LEFT JOIN ", @cenozo, ".region cregion ON region.name = cregion.name ",
        "LEFT JOIN site ON old.site_id = site.id ",
        "LEFT JOIN ", @cenozo, ".site csite ON site.name = csite.name ",
        "AND csite.service_id = ( SELECT id FROM ", @cenozo, ".service WHERE title = 'Sabretooth' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE queue_restriction_old;

      -- quota_state -------------------------------------------------------------------------------
      SELECT "Processing quota_state" AS "";
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS quota_state ( ",
          "quota_id INT UNSIGNED NOT NULL , ",
          "disabled TINYINT( 1 ) NOT NULL DEFAULT 0 , ",
          "PRIMARY KEY ( quota_id) , ",
          "CONSTRAINT fk_quota_state_quota_id ",
            "FOREIGN KEY ( quota_id ) ",
            "REFERENCES ", @cenozo, ".quota ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO quota_state ( quota_id, disabled ) ",
        "SELECT cquota.id, quota.disabled ",
        "FROM ", @cenozo, ".quota cquota ",
        "JOIN ", @cenozo, ".region cregion ON cquota.region_id = cregion.id ",
        "JOIN region ON cregion.name = region.name ",
        "JOIN ", @cenozo, ".site csite ON cquota.site_id = csite.id ",
        "AND csite.service_id = ( SELECT id FROM ", @cenozo, ".service WHERE title = 'Sabretooth' ) ",
        "JOIN site ON csite.name = site.name ",
        "JOIN ", @cenozo, ".age_group cage_group ON cquota.age_group_id = cage_group.id ",
        "JOIN age_group ON cage_group.lower = age_group.lower ",
        "LEFT JOIN quota ",
        "ON quota.region_id = region.id ",
        "AND quota.site_id = site.id ",
        "AND quota.gender = cquota.gender ",
        "AND quota.age_group_id = age_group.id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- recording ---------------------------------------------------------------------------------
      SELECT "Processing recording" AS "";
      ALTER TABLE recording DROP FOREIGN KEY fk_recording_interview;
      ALTER TABLE recording
      ADD CONSTRAINT fk_recording_interview_id
      FOREIGN KEY ( interview_id ) REFERENCES interview ( id )
      ON DELETE NO ACTION ON UPDATE NO ACTION;

      ALTER TABLE recording DROP FOREIGN KEY fk_assignment_id1;
      DROP INDEX fk_assignment_id1 ON recording;
      CREATE INDEX fk_assignment_id ON recording ( assignment_id );
      ALTER TABLE recording
      ADD CONSTRAINT fk_recording_assignment_id
      FOREIGN KEY ( assignment_id ) REFERENCES assignment ( id )
      ON DELETE NO ACTION ON UPDATE NO ACTION;


      -- source_survey -----------------------------------------------------------------------------
      SELECT "Processing source_survey" AS "";
      ALTER TABLE source_survey RENAME source_survey_old;
      ALTER TABLE source_survey_old
      DROP FOREIGN KEY fk_source_survey_phase_id,
      DROP FOREIGN KEY fk_source_survey_source_id;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS source_survey ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "phase_id INT UNSIGNED NOT NULL, ",
          "source_id INT UNSIGNED NOT NULL, ",
          "sid INT NOT NULL, ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_phase_id ( phase_id ASC ), ",
          "INDEX fk_source_id ( source_id ASC ), ",
          "UNIQUE INDEX uq_phase_id_source_id ( phase_id ASC, source_id ASC ), ",
          "CONSTRAINT fk_source_survey_phase_id ",
            "FOREIGN KEY ( phase_id ) ",
            "REFERENCES phase ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_source_survey_source_id ",
            "FOREIGN KEY ( source_id ) ",
            "REFERENCES ", @cenozo, ".source ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO source_survey( id, update_timestamp, create_timestamp, phase_id, source_id, sid ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, old.phase_id, csource.id, old.sid ",
        "FROM source_survey_old old ",
        "JOIN source ON old.source_id = source.id ",
        "JOIN ", @cenozo, ".source csource ON source.name = csource.name" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE source_survey_old;

      -- source_withdraw ---------------------------------------------------------------------------
      SELECT "Processing source_withdraw" AS "";
      ALTER TABLE source_withdraw RENAME source_withdraw_old;
      ALTER TABLE source_withdraw_old
      DROP FOREIGN KEY fk_source_withdraw_qnaire_id,
      DROP FOREIGN KEY fk_source_withdraw_source_id;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS source_withdraw ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "qnaire_id INT UNSIGNED NOT NULL, ",
          "source_id INT UNSIGNED NOT NULL, ",
          "sid INT NULL DEFAULT NULL, ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_source_withdraw_qnaire_id ( qnaire_id ASC ), ",
          "INDEX fk_source_withdraw_source_id ( source_id ASC ), ",
          "UNIQUE INDEX uq_qnaire_id_source_id ( qnaire_id ASC, source_id ASC ), ",
          "CONSTRAINT fk_source_withdraw_qnaire_id ",
            "FOREIGN KEY ( qnaire_id ) ",
            "REFERENCES qnaire ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_source_withdraw_source_id ",
            "FOREIGN KEY ( source_id ) ",
            "REFERENCES ", @cenozo, ".source ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO source_withdraw( id, update_timestamp, create_timestamp, qnaire_id, source_id, sid ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, old.qnaire_id, csource.id, old.sid ",
        "FROM source_withdraw_old old ",
        "JOIN source ON old.source_id = source.id ",
        "JOIN ", @cenozo, ".source csource ON source.name = csource.name" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE source_withdraw_old;

      -- user_time ---------------------------------------------------------------------------------
      SELECT "Processing user_time" AS "";
      ALTER TABLE user_time RENAME user_time_old;
      ALTER TABLE user_time_old
      DROP FOREIGN KEY fk_operator_time_role_id,
      DROP FOREIGN KEY fk_operator_time_site_id,
      DROP FOREIGN KEY fk_operator_time_user_id;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS user_time ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "user_id INT UNSIGNED NOT NULL, ",
          "site_id INT UNSIGNED NOT NULL, ",
          "role_id INT UNSIGNED NOT NULL, ",
          "date DATE NOT NULL, ",
          "total FLOAT NOT NULL, ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_user_id ( user_id ASC ), ",
          "INDEX fk_site_id ( site_id ASC ), ",
          "INDEX fk_role_id ( role_id ASC ), ",
          "UNIQUE INDEX uq_user_site_role_date ( user_id ASC, role_id ASC, site_id ASC, date ASC ), ",
          "INDEX dk_date ( date ASC ), ",
          "CONSTRAINT fk_operator_time_user_id ",
            "FOREIGN KEY ( user_id ) ",
            "REFERENCES ", @cenozo, ".user ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_operator_time_site_id ",
            "FOREIGN KEY ( site_id ) ",
            "REFERENCES ", @cenozo, ".site ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_operator_time_role_id ",
            "FOREIGN KEY ( role_id ) ",
            "REFERENCES ", @cenozo, ".role ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO user_time( id, update_timestamp, create_timestamp, ",
                               "user_id, site_id, role_id, date, total ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, ",
               "cuser.id, csite.id, crole.id, old.date, old.total ",
        "FROM user_time_old old ",
        "JOIN user ON old.user_id = user.id ",
        "JOIN ", @cenozo, ".user cuser ON user.name = cuser.name ",
        "JOIN site ON old.site_id = site.id ",
        "JOIN ", @cenozo, ".site csite ON site.name = csite.name ",
        "AND csite.service_id = ( SELECT id FROM ", @cenozo, ".service WHERE title = 'Sabretooth' ) ",
        "JOIN role ON old.role_id = role.id ",
        "JOIN ", @cenozo, ".role crole ON role.name = crole.name" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE user_time_old;

      -- opal_instance -----------------------------------------------------------------------------
      SELECT "Processing opal_instance" AS "";
      ALTER TABLE opal_instance RENAME opal_instance_old;
      ALTER TABLE opal_instance_old
      DROP FOREIGN KEY fk_opal_instance_user_id;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS opal_instance ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "user_id INT UNSIGNED NOT NULL, ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_user_id ( user_id ASC ), ",
          "UNIQUE INDEX uq_user_id ( user_id ASC ), ",
          "CONSTRAINT fk_opal_instance_user_id ",
            "FOREIGN KEY ( user_id ) ",
            "REFERENCES ", @cenozo, ".user ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO opal_instance( id, update_timestamp, create_timestamp, user_id ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, cuser.id ",
        "FROM opal_instance_old old ",
        "JOIN user ON old.user_id = user.id ",
        "JOIN ", @cenozo, ".user cuser ON user.name = cuser.name" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE opal_instance_old;

      -- callback ----------------------------------------------------------------------------------
      SELECT "Processing callback" AS "";
      ALTER TABLE callback RENAME callback_old;
      ALTER TABLE callback_old
      DROP FOREIGN KEY fk_callback_assignment_id,
      DROP FOREIGN KEY fk_callback_participant_id,
      DROP FOREIGN KEY fk_callback_phone_id;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS callback ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "participant_id INT UNSIGNED NOT NULL, ",
          "phone_id INT UNSIGNED NULL DEFAULT NULL, ",
          "assignment_id INT UNSIGNED NULL DEFAULT NULL, ",
          "datetime DATETIME NOT NULL, ",
          "reached TINYINT( 1 ) NULL DEFAULT NULL ",
          "COMMENT 'If the callback was met, whether the participant was reached.', ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_participant_id ( participant_id ASC ), ",
          "INDEX fk_assignment_id ( assignment_id ASC ), ",
          "INDEX dk_reached ( reached ASC ), ",
          "INDEX fk_phone_id ( phone_id ASC ), ",
          "INDEX dk_datetime ( datetime ASC ), ",
          "CONSTRAINT fk_callback_participant_id ",
            "FOREIGN KEY ( participant_id ) ",
            "REFERENCES ", @cenozo, ".participant ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_callback_assignment_id ",
            "FOREIGN KEY ( assignment_id ) ",
            "REFERENCES assignment ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_callback_phone_id ",
            "FOREIGN KEY ( phone_id ) ",
            "REFERENCES ", @cenozo, ".phone ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO callback( id, update_timestamp, create_timestamp, ",
                              "participant_id, phone_id, assignment_id, datetime, reached ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, ",
               "cparticipant.id, cphone.id, old.assignment_id, old.datetime, old.reached ",
        "FROM callback_old old ",
        "JOIN participant ON old.participant_id = participant.id ",
        "JOIN ", @cenozo, ".participant cparticipant ON participant.uid = cparticipant.uid ",
        "LEFT JOIN phone ON old.phone_id = phone.id ",
        "LEFT JOIN ", @cenozo, ".phone cphone ON phone.rank = cphone.rank ",
        "AND cparticipant.person_id = cphone.person_id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE callback_old;

      -- setting -----------------------------------------------------------------------------------
      SELECT "Processing setting" AS "";
      DROP INDEX category ON setting;
      CREATE INDEX dk_category ON setting ( category );
      DROP INDEX name ON setting;
      CREATE INDEX dk_name ON setting ( name );

      -- setting_value -----------------------------------------------------------------------------
      SELECT "Processing setting_value" AS "";
      ALTER TABLE setting_value RENAME setting_value_old;
      ALTER TABLE setting_value_old
      DROP FOREIGN KEY fk_setting_value_setting_id,
      DROP FOREIGN KEY fk_setting_value_site_id;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS setting_value ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "setting_id INT UNSIGNED NOT NULL, ",
          "site_id INT UNSIGNED NOT NULL, ",
          "value VARCHAR( 45 ) NOT NULL, ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_site_id ( site_id ASC ), ",
          "UNIQUE INDEX uq_setting_id_site_id ( setting_id ASC, site_id ASC ), ",
          "INDEX fk_setting_id ( setting_id ASC ), ",
          "CONSTRAINT fk_setting_value_site_id ",
            "FOREIGN KEY ( site_id ) ",
            "REFERENCES ", @cenozo, ".site ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_setting_value_setting_id ",
            "FOREIGN KEY ( setting_id ) ",
            "REFERENCES setting ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB ",
        "COMMENT = 'Site-specific setting overriding the default.'" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO setting_value( id, update_timestamp, create_timestamp, setting_id, site_id, value ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, old.setting_id, csite.id, old.value ",
        "FROM setting_value_old old ",
        "JOIN site ON old.site_id = site.id ",
        "JOIN ", @cenozo, ".site csite ON site.name = csite.name ",
        "AND csite.service_id = ( SELECT id FROM ", @cenozo, ".service WHERE title = 'Sabretooth' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE setting_value_old;
      
      -- operation ---------------------------------------------------------------------------------
      SELECT "Processing operation" AS "";
      ALTER TABLE operation MODIFY COLUMN type ENUM( 'push','pull','widget' ) NOT NULL;
      CREATE INDEX dk_type ON operation ( type );
      
      -- activity ----------------------------------------------------------------------------------
      SELECT "Processing activity" AS "";
      ALTER TABLE activity RENAME activity_old;
      ALTER TABLE activity_old
      DROP FOREIGN KEY fk_activity_operation,
      DROP FOREIGN KEY fk_activity_role,
      DROP FOREIGN KEY fk_activity_site,
      DROP FOREIGN KEY fk_activity_user;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS activity ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "user_id INT UNSIGNED NOT NULL, ",
          "site_id INT UNSIGNED NOT NULL, ",
          "role_id INT UNSIGNED NOT NULL, ",
          "operation_id INT UNSIGNED NOT NULL, ",
          "query VARCHAR( 511 ) NOT NULL, ",
          "elapsed FLOAT NOT NULL DEFAULT 0 ",
          "COMMENT 'The total time to perform the operation in seconds.', ",
          "error_code VARCHAR( 20 ) NULL DEFAULT '(incomplete)' ",
          "COMMENT 'NULL if no error occurred.', ",
          "datetime DATETIME NOT NULL, ",
          "PRIMARY KEY ( id ), ",
          "INDEX fk_user_id ( user_id ASC ), ",
          "INDEX fk_role_id ( role_id ASC ), ",
          "INDEX fk_site_id ( site_id ASC ), ",
          "INDEX fk_operation_id ( operation_id ASC ), ",
          "INDEX dk_datetime ( datetime ASC ), ",
          "CONSTRAINT fk_activity_user_id ",
            "FOREIGN KEY ( user_id ) ",
            "REFERENCES ", @cenozo, ".user ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_activity_role_id ",
            "FOREIGN KEY ( role_id ) ",
            "REFERENCES ", @cenozo, ".role ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_activity_site_id ",
            "FOREIGN KEY ( site_id ) ",
            "REFERENCES ", @cenozo, ".site ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_activity_operation_id ",
            "FOREIGN KEY ( operation_id ) ",
            "REFERENCES operation ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO activity( id, update_timestamp, create_timestamp, user_id, site_id, role_id, ",
                              "operation_id, query, elapsed, error_code, datetime ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, cuser.id, csite.id, crole.id, ",
               "old.operation_id, old.query, old.elapsed, old.error_code, old.datetime ",
        "FROM activity_old old ",
        "JOIN user ON old.user_id = user.id ",
        "JOIN ", @cenozo, ".user cuser ON user.name = cuser.name ",
        "JOIN role ON old.role_id = role.id ",
        "JOIN ", @cenozo, ".role crole ON role.name = crole.name ",
        "JOIN site ON old.site_id = site.id ",
        "JOIN ", @cenozo, ".site csite ON site.name = csite.name ",
        "AND csite.service_id = ( SELECT id FROM ", @cenozo, ".service WHERE title = 'Sabretooth' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE activity_old;
      
      -- role_has_operation ------------------------------------------------------------------------
      SELECT "Processing role_has_operation" AS "";
      ALTER TABLE role_has_operation RENAME role_has_operation_old;
      ALTER TABLE role_has_operation_old
      DROP FOREIGN KEY fk_role_has_operation_operation,
      DROP FOREIGN KEY fk_role_has_operation_role;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS role_has_operation ( ",
          "role_id INT UNSIGNED NOT NULL, ",
          "operation_id INT UNSIGNED NOT NULL, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "PRIMARY KEY ( role_id, operation_id ), ",
          "INDEX fk_operation_id ( operation_id ASC ), ",
          "INDEX fk_role_id ( role_id ASC ), ",
          "CONSTRAINT fk_role_has_operation_role_id ",
            "FOREIGN KEY ( role_id ) ",
            "REFERENCES ", @cenozo, ".role ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_role_has_operation_operation_id ",
            "FOREIGN KEY ( operation_id ) ",
            "REFERENCES operation ( id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO role_has_operation( role_id, operation_id, update_timestamp, create_timestamp ) ",
        "SELECT crole.id, old.operation_id, old.update_timestamp, old.create_timestamp ",
        "FROM role_has_operation_old old ",
        "JOIN role ON old.role_id = role.id ",
        "JOIN ", @cenozo, ".role crole ON role.name = crole.name" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE role_has_operation_old;

      -- system_message ------------------------------------------------------------------------------
      SELECT "Processing system_message" AS "";
      ALTER TABLE system_message RENAME system_message_old;
      ALTER TABLE system_message_old
      DROP FOREIGN KEY fk_system_message_role_id,
      DROP FOREIGN KEY fk_system_message_site_id;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS system_message ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT , ",
          "update_timestamp TIMESTAMP NOT NULL , ",
          "create_timestamp TIMESTAMP NOT NULL , ",
          "site_id INT UNSIGNED NULL , ",
          "role_id INT UNSIGNED NULL , ",
          "title VARCHAR(255) NOT NULL , ",
          "note TEXT NOT NULL , ",
          "PRIMARY KEY (id) , ",
          "INDEX fk_site_id (site_id ASC) , ",
          "INDEX fk_role_id (role_id ASC) , ",
          "CONSTRAINT fk_system_message_site_id ",
            "FOREIGN KEY (site_id ) ",
            "REFERENCES ", @cenozo, ".site (id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_system_message_role_id ",
            "FOREIGN KEY (role_id ) ",
            "REFERENCES ", @cenozo, ".role (id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO system_message( id, update_timestamp, create_timestamp, ",
                                    "site_id, role_id, title, note ) ",
        "SELECT old.id, old.update_timestamp, old.create_timestamp, ",
               "csite.id, crole.id, old.title, old.note ",
        "FROM system_message_old old ",
        "JOIN site ON old.site_id = site.id ",
        "JOIN ", @cenozo, ".site csite ON site.name = csite.name ",
        "AND csite.service_id = ( SELECT id FROM ", @cenozo, ".service WHERE title = 'Sabretooth' ) ",
        "JOIN role ON old.role_id = role.id ",
        "JOIN ", @cenozo, ".role crole ON role.name = crole.name" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE system_message_old;

      -- participant_last_appointment --------------------------------------------------------------
      SELECT "Processing participant_last_appointment" AS "";
      DROP VIEW participant_last_appointment;
      SET @sql = CONCAT(
        "CREATE VIEW participant_last_appointment AS ",
        "SELECT participant.id AS participant_id, t1.id AS appointment_id, t1.reached ",
        "FROM ", @cenozo, ".participant ",
        "LEFT JOIN appointment t1 ",
        "ON participant.id = t1.participant_id ",
        "AND t1.datetime = ( ",
          "SELECT MAX( t2.datetime ) FROM appointment t2 ",
          "WHERE t1.participant_id = t2.participant_id ) ",
        "GROUP BY participant.id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- drop tables which have been moved to the @cenozo database
      SELECT "Dropping old tables" AS "";
      DROP TABLE access;
      DROP TABLE phone;
      DROP VIEW participant_first_address;
      DROP VIEW participant_primary_address;
      DROP TABLE address;
      DROP TABLE availability;
      DROP TABLE consent;
      DROP VIEW participant_last_consent;
      DROP VIEW participant_last_written_consent;
      DROP VIEW participant_site;
      DROP TABLE participant;
      DROP TABLE quota;
      DROP TABLE age_group;
      DROP TABLE participant_note;
      DROP TABLE postcode;
      DROP TABLE region;
      DROP TABLE source;
      DROP TABLE user;
      DROP TABLE role;
      DROP TABLE site;

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL convert_database();
DROP PROCEDURE IF EXISTS convert_database;
