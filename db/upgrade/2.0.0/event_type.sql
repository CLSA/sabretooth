DROP PROCEDURE IF EXISTS patch_event_type;
  DELIMITER //
  CREATE PROCEDURE patch_event_type()
  BEGIN

    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = 'fk_access_site_id' );
    SET @application = ( SELECT SUBSTRING( DATABASE(), LOCATE( '@', USER() )+1 ) );

    SELECT 'Changing some event_type names and descriptions' AS ''; 

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "first_attempt_event_type_id" );
    IF @test = 1 THEN
      SET @sql = CONCAT(
        "UPDATE qnaire ",
        "JOIN ", @cenozo, ".event_type ON qnaire.first_attempt_event_type_id = event_type.id ",
        "SET event_type.name = 'first attempt (", @application, ")', ",
            "event_type.description = 'First attempt to contact the participant using ", @application, ".' ",
        "WHERE qnaire.rank = 1" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "reached_event_type_id" );
    IF @test = 1 THEN
      SET @sql = CONCAT(
        "UPDATE qnaire ",
        "JOIN ", @cenozo, ".event_type ON qnaire.reached_event_type_id = event_type.id ",
        "SET event_type.name = 'reached (", @application, ")', ",
            "event_type.description = 'First time reaching the participant using ", @application, ".' ",
        "WHERE qnaire.rank = 1" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_event_type();
DROP PROCEDURE IF EXISTS patch_event_type;
