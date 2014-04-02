DROP PROCEDURE IF EXISTS patch_queue_has_participant;
DELIMITER //
CREATE PROCEDURE patch_queue_has_participant()
  BEGIN
    SELECT "Adding new interview_method_id column to queue_has_participant table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "queue_has_participant"
      AND COLUMN_NAME = "interview_method_id" );
    IF @test = 0 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      ALTER TABLE queue_has_participant
      ADD COLUMN interview_method_id INT UNSIGNED NOT NULL;

      ALTER TABLE queue_has_participant
      ADD INDEX fk_interview_method_id( interview_method_id ASC ),
      ADD CONSTRAINT fk_queue_has_participant_interview_method_id
      FOREIGN KEY( interview_method_id ) REFERENCES interview_method( id )
      ON DELETE NO ACTION ON UPDATE NO ACTION;

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue_has_participant();
DROP PROCEDURE IF EXISTS patch_queue_has_participant;
