-- add the new type column
-- we need to create a procedure which only alters the appointment table if the
-- type column is missing
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
      AND COLUMN_NAME = "type" );
     IF @test = 0 THEN
       ALTER TABLE appointment
       ADD COLUMN type ENUM('full','half') NOT NULL DEFAULT 'full' AFTER datetime;
     END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_appointment();
DROP PROCEDURE IF EXISTS patch_appointment;
