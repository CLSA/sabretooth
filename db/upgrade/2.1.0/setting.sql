DROP PROCEDURE IF EXISTS patch_setting;
  DELIMITER //
  CREATE PROCEDURE patch_setting()
  BEGIN

    SELECT "Removing short_appointment column from setting table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "setting"
      AND COLUMN_NAME = "short_appointment" );
    IF @test = 1 THEN
      ALTER TABLE setting DROP COLUMN short_appointment;
    END IF;

    SELECT "Removing long_appointment column from setting table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "setting"
      AND COLUMN_NAME = "long_appointment" );
    IF @test = 1 THEN
      ALTER TABLE setting DROP COLUMN long_appointment;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_setting();
DROP PROCEDURE IF EXISTS patch_setting;
