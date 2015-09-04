DROP PROCEDURE IF EXISTS patch_event_type;
  DELIMITER //
  CREATE PROCEDURE patch_event_type()
  BEGIN

    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = 'fk_queue_state_site_id' );

    SELECT 'Changing some event_type names and descriptions' AS ''; 

      SET @sql = CONCAT(
        "UPDATE ", @cenozo, ".script ",
        "JOIN ", @cenozo, ".event_type ON script.completed_event_type_id = event_type.id ",
        "SET event_type.name = CONCAT( 'completed (', script.name, ')' ), ",
            "event_type.description = CONCAT( 'Completed the \"', script.name, '\" script.' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "UPDATE qnaire ",
        "JOIN ", @cenozo, ".event_type ON qnaire.first_attempt_event_type_id = event_type.id ",
        "JOIN ", @cenozo, ".script ON qnaire.script_id = script.id ",
        "SET event_type.name = CONCAT( 'first attempt (', script.name, ')' ), ",
            "event_type.description = CONCAT( 'First attempt to start the \"', ",
                                             "script.name, '\" questionnaire.' ) ",
        "WHERE event_type.description ",
          "NOT LIKE ( 'First attempt to start the \"%\" questionnaire.' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "UPDATE qnaire ",
        "JOIN ", @cenozo, ".event_type ON qnaire.reached_event_type_id = event_type.id ",
        "JOIN ", @cenozo, ".script ON qnaire.script_id = script.id ",
        "SET event_type.name = CONCAT( 'reached (', script.name, ')' ), ",
            "event_type.description = CONCAT( 'First time the participant was reached for the \"', ",
                                             "script.name, '\" questionnaire.' ) ",
        "WHERE event_type.description ",
          "NOT LIKE ( 'First time the participant was reached for the \"%\" questionnaire.' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_event_type();
DROP PROCEDURE IF EXISTS patch_event_type;
