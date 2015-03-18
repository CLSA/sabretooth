-- add the new index to the sticky/datetime columns
-- we need to create a procedure which only alters the participant_note table if the
-- sticky/datetime column index is missing
DROP PROCEDURE IF EXISTS patch_participant_note;
DELIMITER //
CREATE PROCEDURE patch_participant_note()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "participant_note"
      AND COLUMN_NAME = "sticky"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE participant_note
      ADD INDEX dk_sticky_datetime (sticky ASC, datetime ASC);
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_participant_note();
DROP PROCEDURE IF EXISTS patch_participant_note;
