-- add the new index to the start_time, end_time, start_date and end_date columns
-- we need to create a procedure which only alters the shift_template table if the
-- start_time, end_time, start_date or end_date column indices are missing
DROP PROCEDURE IF EXISTS patch_shift_template;
DELIMITER //
CREATE PROCEDURE patch_shift_template()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "shift_template"
      AND COLUMN_NAME = "start_time"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE shift_template
      ADD INDEX dk_start_time (start_time ASC);
    END IF;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "shift_template"
      AND COLUMN_NAME = "end_time"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE shift_template
      ADD INDEX dk_end_time (end_time ASC);
    END IF;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "shift_template"
      AND COLUMN_NAME = "start_date"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE shift_template
      ADD INDEX dk_start_date (start_date ASC);
    END IF;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "shift_template"
      AND COLUMN_NAME = "end_date"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE shift_template
      ADD INDEX dk_end_date (end_date ASC);
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_shift_template();
DROP PROCEDURE IF EXISTS patch_shift_template;
