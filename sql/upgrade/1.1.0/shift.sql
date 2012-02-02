-- add the new index to the start_datetime and end_datetime columns
-- we need to create a procedure which only alters the shift table if the
-- start_datetime or end_datetime column indices are missing
DROP PROCEDURE IF EXISTS patch_shift;
DELIMITER //
CREATE PROCEDURE patch_shift()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "shift"
      AND COLUMN_NAME = "start_datetime"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE shift
      ADD INDEX dk_start_datetime (start_datetime ASC);
    END IF;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "shift"
      AND COLUMN_NAME = "end_datetime"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE shift
      ADD INDEX dk_end_datetime (end_datetime ASC);
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_shift();
DROP PROCEDURE IF EXISTS patch_shift;
