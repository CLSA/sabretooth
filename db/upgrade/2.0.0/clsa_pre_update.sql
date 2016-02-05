-- Script used by the CLSA when upgrading to Sabretooth2

DROP PROCEDURE IF EXISTS interview_update;
DELIMITER //
CREATE PROCEDURE interview_update()
  BEGIN

    -- Declare '_val' variables to read in each record from the cursor
    DECLARE sid_val INT(11);
    DECLARE qnaire_id_val INT(11) UNSIGNED;

    -- Declare variables used just for cursor and loop control
    DECLARE no_more_rows BOOLEAN;
    DECLARE loop_cntr INT DEFAULT 0;
    DECLARE num_rows INT DEFAULT 0;

    -- Declare the cursor
    DECLARE the_cursor CURSOR FOR 
    SELECT sid, qnaire_id
    FROM phase
    WHERE repeated = false;

    -- Declare 'handlers' for exceptions
    DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET no_more_rows = TRUE;

    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_queue_state_site_id" );

    SET @limesurvey = ( SELECT CONCAT( SUBSTRING( USER(), 1, LOCATE( '@', USER() )-1 ), "_limesurvey2" ) );

    SELECT "Creating interview records coinciding with the new qnaire records" AS "";

    -- 'open' the cursor and capture the number of rows returned
    -- (the 'select' gets invoked when the cursor is 'opened')
    OPEN the_cursor;
    select FOUND_ROWS() into num_rows;

    the_loop: LOOP

      FETCH  the_cursor
      INTO   sid_val, qnaire_id_val;

      -- break out of the loop if
        -- 1) there were no records, or
        -- 2) we've processed them all
      IF no_more_rows THEN
          CLOSE the_cursor;
          LEAVE the_loop;
      END IF; 

      SELECT CONCAT( "Converting/creating interview records for SID ", sid_val ) AS ""; 

      SET @sql = CONCAT(
        "INSERT INTO interview( qnaire_id, interview_method_id, participant_id, completed ) ",
        "SELECT qnaire.id, qnaire.default_interview_method_id, participant.id, submitdate IS NOT NULL ",
        "FROM phase ",
        "JOIN qnaire ON phase.qnaire_id = qnaire.id ",
        "CROSS JOIN ", @limesurvey, ".survey_", sid_val, " AS survey "
        "JOIN ", @cenozo, ".participant ON survey.token = participant.uid ",
        "WHERE phase.sid = '", sid_val, "' ",
        "ON DUPLICATE KEY UPDATE completed = submitdate IS NOT NULL" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- count the number of times looped
      SET loop_cntr = loop_cntr + 1;

    END LOOP the_loop;

  END //
DELIMITER ;

DROP PROCEDURE IF EXISTS clsa_pre_update;
DELIMITER //
CREATE PROCEDURE clsa_pre_update()
  BEGIN

    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_queue_state_site_id" );

    SET @limesurvey = ( SELECT CONCAT( SUBSTRING( USER(), 1, LOCATE( '@', USER() )-1 ), "_limesurvey2" ) );

    SET @test = (
      SELECT COUNT(*) FROM (
        SELECT COUNT(*)
        FROM phase
        WHERE repeated = false
        GROUP BY qnaire_id
        HAVING COUNT(*) > 1
      ) t
    );
    IF @test = 0 THEN

      SELECT "No qnaires to convert, doing nothing" AS "";

    ELSE

      SET @sql = CONCAT(
        "CREATE TEMPORARY TABLE phase_no_repeat ",
        "SELECT phase.*, surveyls_title AS title, @new_rank := @new_rank+1 AS new_rank ",
        "FROM ( SELECT @new_rank := 0 ) t, phase ",
        "JOIN ", @limesurvey, ".surveys_languagesettings ON phase.sid = surveyls_survey_id ",
          "AND surveyls_language = 'en' ",
        "WHERE repeated = false ",
        "ORDER BY rank" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT "Renaming qnaire after the first phase belonging to that qnaire" AS "";

      UPDATE qnaire
      JOIN phase_no_repeat ON qnaire.id = phase_no_repeat.qnaire_id
      SET name = title
      WHERE phase_no_repeat.rank = (
        SELECT MIN(rank) FROM phase WHERE repeated = false
      );

      DELETE FROM phase_no_repeat WHERE rank = (
        SELECT MIN(rank) FROM phase WHERE repeated = false
      );

      SELECT "Splitting remaining phases into distinct qnaires" AS "";

      INSERT INTO qnaire( name, rank,
        first_attempt_event_type_id, reached_event_type_id, completed_event_type_id,
        default_interview_method_id, delay, withdraw_sid )
      SELECT title, new_rank,
        first_attempt_event_type_id, reached_event_type_id, completed_event_type_id,
        default_interview_method_id, 0, withdraw_sid
      FROM qnaire
      JOIN phase_no_repeat AS phase ON qnaire.id = phase.qnaire_id;

      UPDATE phase
      JOIN phase_no_repeat USING( id )
      JOIN qnaire ON qnaire.name = phase_no_repeat.title
      SET phase.qnaire_id = qnaire.id, phase.rank = 1;

      CALL interview_update();

    END IF;

  END //
DELIMITER ;

CALL clsa_pre_update();

DROP PROCEDURE IF EXISTS interview_update;
DROP PROCEDURE IF EXISTS clsa_pre_update;
