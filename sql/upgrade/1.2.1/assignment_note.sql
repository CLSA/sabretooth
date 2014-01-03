DROP PROCEDURE IF EXISTS patch_assignment_note;
DELIMITER //
CREATE PROCEDURE patch_assignment_note()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    -- transfer assignment notes to person notes and remove the assignment_note table
    SELECT "Transfering assignment notes to person notes" AS "";
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = ( SELECT database() )
      AND TABLE_NAME = "assignment_note" );
    IF @test = 1 THEN
      SET @sql = CONCAT(
        "INSERT INTO ", @cenozo, ".person_note ( update_timestamp, create_timestamp, ",
          "person_id, user_id, sticky, datetime, note ) ",
        "SELECT an.update_timestamp, an.create_timestamp, ",
          "participant.person_id, an.user_id, an.sticky, an.datetime, an.note ",
        "FROM assignment_note an ",
        "JOIN assignment ON an.assignment_id = assignment.id ",
        "JOIN interview ON assignment.interview_id = interview.id ",
        "JOIN ", @cenozo, ".participant ON interview.participant_id = participant.id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;

      DROP TABLE assignment_note;
    END IF;
  END //
DELIMITER ;

CALL patch_assignment_note();
DROP PROCEDURE IF EXISTS patch_assignment_note;
