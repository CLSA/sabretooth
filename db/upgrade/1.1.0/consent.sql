-- add the new index to the event and date columns
-- we need to create a procedure which only alters the consent table if the
-- event or date column indices are missing
DROP PROCEDURE IF EXISTS patch_consent;
DELIMITER //
CREATE PROCEDURE patch_consent()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "consent"
      AND COLUMN_NAME = "event"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE consent
      ADD INDEX dk_event (event ASC);
    END IF;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "consent"
      AND COLUMN_NAME = "date"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE consent
      ADD INDEX dk_date (date ASC);
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_consent();
DROP PROCEDURE IF EXISTS patch_consent;
