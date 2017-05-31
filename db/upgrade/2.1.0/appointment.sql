DROP PROCEDURE IF EXISTS patch_appointment;
  DELIMITER //
  CREATE PROCEDURE patch_appointment()
  BEGIN

    SELECT "Removing datetime column from appointment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND COLUMN_NAME = "datetime" );
    IF @test = 1 THEN
      ALTER TABLE appointment
      DROP KEY dk_datetime,
      DROP COLUMN datetime;
    END IF;

    SELECT "Removing type column from appointment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND COLUMN_NAME = "type" );
    IF @test = 1 THEN
      ALTER TABLE appointment
      DROP COLUMN type;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_appointment();
DROP PROCEDURE IF EXISTS patch_appointment;
