DROP PROCEDURE IF EXISTS patch_callback;
  DELIMITER //
  CREATE PROCEDURE patch_callback()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Replacing participant_id with interview_id column in callback table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "callback" );
    IF @test = 1 THEN
      SELECT "Copy future callbacks for participants who have not completed all interviews and do not have unassigned appointments" AS "";

      SET @sql = CONCAT(
        "UPDATE ", @cenozo, ".participant ",
        "JOIN callback ON participant.id = callback.participant_id ",
        "AND callback.datetime = ( ",
          "SELECT MAX( c1.datetime ) ",
          "FROM callback AS c1 ",
          "WHERE c1.participant_id = participant.id ",
        ") ",
        "SET participant.callback = callback.datetime ",
        "WHERE callback.reached IS NULL ",
        "AND ( participant.callback IS NULL OR callback.datetime > participant.callback )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT "Delete defunct callback table" AS "";
      DROP TABLE callback;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_callback();
DROP PROCEDURE IF EXISTS patch_callback;
