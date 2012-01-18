-- add the new error_code column
-- we need to create a procedure which only alters the activity table if the
-- error_code column is missing
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
       ADD COLUMN error_code VARCHAR(20) NOT NULL DEFAULT '(incomplete)' AFTER elapsed;
     END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_activity();
DROP PROCEDURE IF EXISTS patch_activity;
