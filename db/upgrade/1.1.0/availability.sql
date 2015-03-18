-- add the new index to the start_time and end_time columns
-- we need to create a procedure which only alters the availability table if the
-- start_time or end_time column indices are missing
DROP PROCEDURE IF EXISTS patch_availability;
DELIMITER //
CREATE PROCEDURE patch_availability()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "availability"
      AND COLUMN_NAME = "start_time"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE availability
      ADD INDEX dk_start_time (start_time ASC);
    END IF;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "availability"
      AND COLUMN_NAME = "end_time"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE availability
      ADD INDEX dk_end_time (end_time ASC);
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_availability();
DROP PROCEDURE IF EXISTS patch_availability;
