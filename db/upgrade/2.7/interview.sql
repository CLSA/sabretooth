DROP PROCEDURE IF EXISTS patch_interview;
DELIMITER //
CREATE PROCEDURE patch_interview()
  BEGIN

    SELECT "Adding new current_page_rank column to interview table" AS "";
    
    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "interview"
    AND column_name = "current_page_rank";

    IF @test = 0 THEN
      ALTER TABLE interview ADD COLUMN current_page_rank INT UNSIGNED NULL DEFAULT NULL AFTER method;
    END IF;

  END //
DELIMITER ;

CALL patch_interview();
DROP PROCEDURE IF EXISTS patch_interview;


DELIMITER $$

DROP TRIGGER IF EXISTS interview_AFTER_UPDATE$$
CREATE TRIGGER interview_AFTER_UPDATE AFTER UPDATE ON interview FOR EACH ROW
BEGIN
  IF OLD.start_datetime != NEW.start_datetime THEN
    CALL update_participant_last_interview( NEW.participant_id );
    CALL update_interview_last_assignment( NEW.id );
  END IF;
END$$

DELIMITER ;
