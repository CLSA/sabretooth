DROP PROCEDURE IF EXISTS patch_appointment;
  DELIMITER //
  CREATE PROCEDURE patch_appointment()
  BEGIN

    SELECT "Adding vacancy_id column to appointment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND COLUMN_NAME = "vacancy_id" );
    IF @test = 0 THEN
      ALTER TABLE appointment
      ADD COLUMN vacancy_id INT UNSIGNED NULL DEFAULT NULL AFTER create_timestamp,
      ADD INDEX fk_vacancy_id (vacancy_id ASC),
      ADD CONSTRAINT fk_appointment_vacancy_id
      FOREIGN KEY (vacancy_id)
      REFERENCES sabretooth.vacancy (id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_appointment();
DROP PROCEDURE IF EXISTS patch_appointment;
