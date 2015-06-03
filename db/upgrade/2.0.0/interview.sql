DROP PROCEDURE IF EXISTS patch_interview;
  DELIMITER //
  CREATE PROCEDURE patch_interview()
  BEGIN

    SELECT "Replacing completed with start_datetime and end_datetime columns in interview table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "interview"
      AND COLUMN_NAME = "completed" );
    IF @test = 1 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      ALTER TABLE interview 
      ADD COLUMN start_datetime DATETIME NOT NULL,
      ADD COLUMN end_datetime DATETIME NULL DEFAULT NULL,
      ADD INDEX dk_start_datetime ( start_datetime ASC ),
      ADD INDEX dk_end_datetime ( end_datetime ASC );

      -- fill in the new start_datetime and end_datetime columns
      UPDATE interview 
      LEFT JOIN assignment ON interview.id = assignment.interview_id
      SET interview.start_datetime = assignment.start_datetime
      WHERE assignment.start_datetime = (
        SELECT MIN( start_datetime )
        FROM assignment
        WHERE assignment.interview_id = interview.id
        GROUP BY interview_id
        LIMIT 1
      );

      UPDATE interview 
      LEFT JOIN assignment ON interview.id = assignment.interview_id
      SET interview.end_datetime = IF( interview.completed, assignment.end_datetime, NULL )
      WHERE assignment.end_datetime = (
        SELECT MAX( end_datetime )
        FROM assignment
        WHERE assignment.interview_id = interview.id
        GROUP BY interview_id
        LIMIT 1
      );

      -- now get rid of the completed column and index
      ALTER TABLE interview
      DROP INDEX dk_completed,
      DROP COLUMN completed;

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_interview();
DROP PROCEDURE IF EXISTS patch_interview;


SELECT "Adding new triggers to interview table" AS "";

DELIMITER $$

DROP TRIGGER IF EXISTS interview_AFTER_INSERT $$
CREATE DEFINER = CURRENT_USER TRIGGER interview_AFTER_INSERT AFTER INSERT ON interview FOR EACH ROW
BEGIN
  CALL update_participant_last_interview( NEW.participant_id );
  CALL update_interview_last_assignment( NEW.id );
END;$$


DROP TRIGGER IF EXISTS interview_AFTER_UPDATE $$
CREATE DEFINER = CURRENT_USER TRIGGER interview_AFTER_UPDATE AFTER UPDATE ON interview FOR EACH ROW
BEGIN
  CALL update_participant_last_interview( NEW.participant_id );
  CALL update_interview_last_assignment( NEW.id );
END;$$


DROP TRIGGER IF EXISTS interview_AFTER_DELETE $$
CREATE DEFINER = CURRENT_USER TRIGGER interview_AFTER_DELETE AFTER DELETE ON interview FOR EACH ROW
BEGIN
  CALL update_participant_last_interview( OLD.participant_id );
END;$$

DELIMITER ;
