-- add the new error_code column and datetime index
-- we need to create a procedure which only alters the activity table if the
-- error_code column or datetime index are missing
DROP PROCEDURE IF EXISTS patch_activity;
DELIMITER //
CREATE PROCEDURE patch_activity()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "activity"
      AND COLUMN_NAME = "error_code" );
    IF @test = 0 THEN
      ALTER TABLE activity
      ADD COLUMN error_code VARCHAR(20) NULL DEFAULT '(incomplete)'
      COMMENT 'NULL if no error occurred.'
      AFTER elapsed;
      UPDATE activity SET error_code = NULL;
    END IF;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "activity"
      AND COLUMN_NAME = "datetime"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE activity
      ADD INDEX dk_datetime (datetime ASC);
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_activity();
DROP PROCEDURE IF EXISTS patch_activity;
