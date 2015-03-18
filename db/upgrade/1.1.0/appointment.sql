-- add the new index to the datetime column
-- we need to create a procedure which only alters the appointment table if the
-- datetime column index is missing
DROP PROCEDURE IF EXISTS patch_appointment;
DELIMITER //
CREATE PROCEDURE patch_appointment()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "appointment"
      AND COLUMN_NAME = "datetime"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE appointment
      ADD INDEX dk_datetime (datetime ASC);
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_appointment();
DROP PROCEDURE IF EXISTS patch_appointment;
