-- drop the processed column
-- we need to create a procedure which only alters the recording table if the
-- processed column exists
DROP PROCEDURE IF EXISTS patch_recording;
DELIMITER //
CREATE PROCEDURE patch_recording()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "recording"
      AND COLUMN_NAME = "processed" );
    IF @test = 1 THEN
      ALTER TABLE recording
      DROP COLUMN processed;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_recording();
DROP PROCEDURE IF EXISTS patch_recording;
