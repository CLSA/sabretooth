DROP PROCEDURE IF EXISTS patch_participant;
DELIMITER //
CREATE PROCEDURE patch_participant()
  BEGIN
    DECLARE test INT;
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "participant"
      AND COLUMN_NAME = "scheduled_call_datetime" );
    IF @test = 0 THEN
      ALTER TABLE participant
      ADD COLUMN scheduled_call_datetime DATETIME NULL default NULL
      AFTER prior_contact_date;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_participant();
DROP PROCEDURE IF EXISTS patch_participant;
