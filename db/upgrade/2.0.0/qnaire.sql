DROP PROCEDURE IF EXISTS create_events;
DELIMITER //
CREATE PROCEDURE create_events()
  BEGIN

    -- Declare '_val' variables to read in each record from the cursor
    DECLARE sid_val INT(11);

    -- Declare variables used just for cursor and loop control
    DECLARE no_more_rows BOOLEAN;
    DECLARE loop_cntr INT DEFAULT 0;
    DECLARE num_rows INT DEFAULT 0;

    -- Declare the cursor
    DECLARE the_cursor CURSOR FOR
    SELECT sid FROM phase WHERE repeated = false;

    -- Declare 'handlers' for exceptions
    DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET no_more_rows = TRUE;

    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SET @limesurvey = ( SELECT CONCAT( SUBSTRING( USER(), 1, LOCATE( '@', USER() )-1 ), "_limesurvey2" ) );
    SET @application = ( SELECT SUBSTRING( DATABASE(), LOCATE( '@', USER() )+1 ) );

    -- 'open' the cursor and capture the number of rows returned
    -- (the 'select' gets invoked when the cursor is 'opened')
    OPEN the_cursor;
    select FOUND_ROWS() into num_rows;

    the_loop: LOOP

      FETCH  the_cursor
      INTO   sid_val;

      -- break out of the loop if
        -- 1) there were no records, or
        -- 2) we've processed them all
      IF no_more_rows THEN
          CLOSE the_cursor;
          LEAVE the_loop;
      END IF;

      SELECT CONCAT( "Creating new started events based on the script SID ", sid_val ) AS "";
      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".event( participant_id, event_type_id, datetime ) ",
        "SELECT participant.id, event_type.id, CONVERT_TZ( startdate, 'Canada/Eastern', 'UTC' ) ",
        "FROM ", @cenozo, ".script ",
        "JOIN ", @cenozo, ".event_type ON event_type.name = CONCAT( 'started (', script.name, ')' ) ",
        "CROSS JOIN ", @limesurvey, ".survey_", sid_val, " AS survey ",
        "JOIN ", @cenozo, ".participant ON survey.token = participant.uid ",
        "JOIN ", @cenozo, ".application_has_participant ",
        "ON participant.id = application_has_participant.participant_id ",
        "JOIN ", @cenozo, ".application ON application_has_participant.application_id = application_id ",
        "WHERE script.sid = ", sid_val, " ",
        "AND application.name = '", @application, "' ",
        "AND application_has_participant.datetime IS NOT NULL" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT CONCAT( "Creating new finished events based on the script SID ", sid_val ) AS "";
      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".event( participant_id, event_type_id, datetime ) ",
        "SELECT participant.id, event_type.id, CONVERT_TZ( submitdate, 'Canada/Eastern', 'UTC' ) ",
        "FROM ", @cenozo, ".script ",
        "JOIN ", @cenozo, ".event_type ON event_type.name = CONCAT( 'finished (', script.name, ')' ) ",
        "CROSS JOIN ", @limesurvey, ".survey_", sid_val, " AS survey ",
        "JOIN ", @cenozo, ".participant ON survey.token = participant.uid ",
        "JOIN ", @cenozo, ".application_has_participant ",
        "ON participant.id = application_has_participant.participant_id ",
        "JOIN ", @cenozo, ".application ON application_has_participant.application_id = application_id ",
        "WHERE script.sid = ", sid_val, " ",
        "AND survey.submitdate IS NOT NULL ",
        "AND application.name = '", @application, "' ",
        "AND application_has_participant.datetime IS NOT NULL" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- count the number of times looped
      SET loop_cntr = loop_cntr + 1;

    END LOOP the_loop;

  END //
DELIMITER ;

DROP PROCEDURE IF EXISTS patch_qnaire;
  DELIMITER //
  CREATE PROCEDURE patch_qnaire()
  BEGIN

    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SET @limesurvey = ( SELECT CONCAT( SUBSTRING( USER(), 1, LOCATE( '@', USER() )-1 ), "_limesurvey2" ) );

    SELECT "Dropping prev_qnaire_id column from qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "prev_qnaire_id" );
    IF @test = 1 THEN
      ALTER TABLE qnaire
      DROP FOREIGN KEY fk_qnaire_prev_qnaire_id,
      DROP INDEX fk_prev_qnaire_id;

      ALTER TABLE qnaire DROP COLUMN prev_qnaire_id;
    END IF;

    SELECT "Dropping first_attempt_event_type_id column from qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "first_attempt_event_type_id" );
    IF @test = 1 THEN
      ALTER TABLE qnaire
      DROP FOREIGN KEY fk_qnaire_first_attempt_event_type_id,
      DROP INDEX fk_first_attempt_event_type_id;

      ALTER TABLE qnaire DROP COLUMN first_attempt_event_type_id;
    END IF;

    SELECT "Dropping reached_event_type_id column from qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "reached_event_type_id" );
    IF @test = 1 THEN
      ALTER TABLE qnaire
      DROP FOREIGN KEY fk_qnaire_reached_event_type_id,
      DROP INDEX fk_reached_event_type_id;

      ALTER TABLE qnaire DROP COLUMN reached_event_type_id;
    END IF;

    SELECT "Dropping default_interview_method_id column from qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "default_interview_method_id" );
    IF @test = 1 THEN
      ALTER TABLE qnaire
      DROP FOREIGN KEY fk_qnaire_default_interview_method_id,
      DROP INDEX fk_default_interview_method_id;

      ALTER TABLE qnaire DROP COLUMN default_interview_method_id;
    END IF;

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "script_id" );
    IF @test = 0 THEN
      SELECT "Adding new constraint to script table in qnaire table" AS "";

      ALTER TABLE qnaire
      ADD COLUMN script_id INT UNSIGNED NOT NULL
      AFTER rank;

      ALTER TABLE qnaire
      ADD INDEX fk_script_id( script_id ASC );

      -- eliminate any qnaires without phases
      DELETE FROM qnaire
      WHERE id IN ( SELECT id FROM (
        SELECT qnaire.id
        FROM qnaire
        LEFT JOIN phase ON qnaire.id = phase.qnaire_id
        WHERE phase.id IS NULL ) AS t
      );

      SELECT "Creating new script-based event_types" AS "";

      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".event_type( name, description ) ",
        "SELECT CONCAT( type.name, ' (', surveyls_title, ')' ), ",
               "CONCAT( type.description, ' \"', surveyls_title, '\" script.' ) ",
        "FROM ( SELECT 'started' AS name, 'Started the' AS description UNION ",
               "SELECT 'finished' AS name, 'Finished the' AS description ) AS type, qnaire ",
        "JOIN phase ON qnaire.id = phase.qnaire_id ",
        "AND phase.repeated = 0 ",
        "JOIN ", @limesurvey, ".surveys_languagesettings ON phase.sid = surveyls_survey_id ",
        "AND surveyls_language = 'en' "
        "ORDER BY qnaire.rank, phase.rank, type.name DESC" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT "Creating scripts based on existing non-repeating qnaire phases" AS "";

      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".script( ",
          "name, started_event_type_id, finished_event_type_id, sid, repeated, description ) ",
        "SELECT surveyls_title, started_event_type.id, finished_event_type.id, phase.sid, 0, qnaire.description ",
        "FROM qnaire ",
        "JOIN phase ON qnaire.id = phase.qnaire_id ",
        "AND phase.repeated = 0 ",
        "JOIN ", @limesurvey, ".surveys_languagesettings ON phase.sid = surveyls_survey_id ",
        "AND surveyls_language = 'en' "
        "JOIN ", @cenozo, ".event_type AS started_event_type ",
        "ON started_event_type.name = CONVERT( CONCAT( 'started (', surveyls_title, ')' ) USING utf8 ) ",
        "JOIN ", @cenozo, ".event_type AS finished_event_type ",
        "ON finished_event_type.name = CONVERT( CONCAT( 'finished (', surveyls_title, ')' ) USING utf8 ) ",
        "ORDER BY qnaire.rank" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT "Creating scripts based on existing repeating qnaire phases" AS "";

      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".script( ",
          "name, started_event_type_id, finished_event_type_id, sid, repeated, description ) ",
        "SELECT surveyls_title, NULL, NULL, phase.sid, 1, qnaire.description ",
        "FROM qnaire ",
        "JOIN phase ON qnaire.id = phase.qnaire_id ",
        "AND phase.repeated = 1 ",
        "JOIN ", @limesurvey, ".surveys_languagesettings ON phase.sid = surveyls_survey_id ",
        "AND surveyls_language = 'en' "
        "ORDER BY qnaire.rank" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT "Creating qnaires linked to newly created scripts" AS "";

      SET @sql = CONCAT(
        "UPDATE qnaire JOIN ", @cenozo, ".script USING( name ) ",
        "SET qnaire.script_id = script.id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".application_has_script( application_id, script_id ) ",
        "SELECT application.id, script.id ",
        "FROM ", @cenozo, ".application, ", @cenozo, ".script ",
        "WHERE sid IN ( SELECT DISTINCT sid FROM phase ) ",
        "AND DATABASE() LIKE CONCAT( '%_', application.name )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "ALTER TABLE qnaire ",
        "ADD CONSTRAINT fk_qnaire_script_id ",
        "FOREIGN KEY( script_id ) REFERENCES ", @cenozo, ".script( id ) ",
        "ON DELETE NO ACTION ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

    SELECT "Dropping name column from qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "name" );
    IF @test = 1 THEN
      ALTER TABLE qnaire DROP INDEX uq_name;
      ALTER TABLE qnaire DROP COLUMN name;
    END IF;

    SELECT "Dropping completed_event_type_id column from qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "completed_event_type_id" );
    IF @test = 1 THEN
      ALTER TABLE qnaire DROP FOREIGN KEY fk_qnaire_completed_event_type_id;

      SET @sql = CONCAT(
        "DELETE FROM ", @cenozo, ".event_type ",
        "WHERE id IN ( ",
          "SELECT completed_event_type_id FROM qnaire ",
        ")" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      ALTER TABLE qnaire DROP INDEX fk_completed_event_type_id;

      ALTER TABLE qnaire DROP COLUMN completed_event_type_id;
    END IF;

    SELECT "Dropping withdraw_sid column from qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "withdraw_sid" );
    IF @test = 1 THEN
      ALTER TABLE qnaire DROP COLUMN withdraw_sid;
    END IF;

    SELECT "Dropping description column from qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "description" );
    IF @test = 1 THEN
      ALTER TABLE qnaire DROP COLUMN description;
    END IF;

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "phase" );
    IF @test = 1 THEN
      CALL create_events();
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_qnaire();
DROP PROCEDURE IF EXISTS patch_qnaire;
DROP PROCEDURE IF EXISTS create_events;
