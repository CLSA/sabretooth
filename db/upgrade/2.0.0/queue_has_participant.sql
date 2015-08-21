DROP PROCEDURE IF EXISTS patch_queue_has_participant;
  DELIMITER //
  CREATE PROCEDURE patch_queue_has_participant()
  BEGIN

    SELECT "Dropping interview_method_id column from queue_has_participant table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "queue_has_participant"
      AND COLUMN_NAME = "interview_method_id" );
    IF @test = 1 THEN
      ALTER TABLE queue_has_participant
      DROP FOREIGN KEY fk_queue_has_participant_interview_method_id,
      DROP INDEX fk_interview_method_id;

      ALTER TABLE queue_has_participant DROP COLUMN interview_method_id;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue_has_participant();
DROP PROCEDURE IF EXISTS patch_queue_has_participant;
