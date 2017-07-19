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

    SELECT "Adding start_vacancy_id column to appointment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND COLUMN_NAME = "start_vacancy_id" );
    IF @test = 0 THEN
      ALTER TABLE appointment
      ADD COLUMN start_vacancy_id INT UNSIGNED NULL DEFAULT NULL COMMENT 'Do not edit, determined by trigger.',
      ADD KEY fk_start_vacancy_id (start_vacancy_id ASC),
      ADD CONSTRAINT fk_appointment_start_vacancy_id
        FOREIGN KEY (start_vacancy_id)
        REFERENCES vacancy (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION;

      UPDATE appointment
      SET start_vacancy_id = (
        SELECT vacancy.id
        FROM appointment_has_vacancy
        JOIN vacancy ON appointment_has_vacancy.vacancy_id = vacancy.id
        WHERE appointment_id = appointment.id
        ORDER BY vacancy.datetime LIMIT 1
      );
    END IF;

    SELECT "Adding end_vacancy_id column to appointment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND COLUMN_NAME = "end_vacancy_id" );
    IF @test = 0 THEN
      ALTER TABLE appointment
      ADD COLUMN end_vacancy_id INT UNSIGNED NULL DEFAULT NULL COMMENT 'Do not edit, determined by trigger.',
      ADD KEY fk_end_vacancy_id (end_vacancy_id ASC),
      ADD CONSTRAINT fk_appointment_end_vacancy_id
        FOREIGN KEY (end_vacancy_id)
        REFERENCES vacancy (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION;

      UPDATE appointment
      SET end_vacancy_id = (
        SELECT vacancy.id
        FROM appointment_has_vacancy
        JOIN vacancy ON appointment_has_vacancy.vacancy_id = vacancy.id
        WHERE appointment_id = appointment.id
        ORDER BY vacancy.datetime DESC LIMIT 1
      );
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_appointment();
DROP PROCEDURE IF EXISTS patch_appointment;


DELIMITER $$

DROP TRIGGER IF EXISTS appointment_BEFORE_DELETE $$
CREATE DEFINER = CURRENT_USER TRIGGER appointment_BEFORE_DELETE BEFORE DELETE ON appointment FOR EACH ROW
BEGIN
  -- Delete all vacancies
  -- Note that we can't do this as part of a cascade operation since the appointment_has_vacancy delete trigger won't fire if we do
  DELETE FROM appointment_has_vacancy WHERE appointment_id = OLD.id;
END;$$

DROP TRIGGER IF EXISTS appointment_AFTER_DELETE $$
CREATE DEFINER = CURRENT_USER TRIGGER appointment_AFTER_DELETE AFTER DELETE ON appointment FOR EACH ROW
BEGIN
  CALL update_interview_last_appointment( OLD.interview_id );
  
  -- remove any vacancies that have no operators or appointments
  DELETE FROM vacancy
  WHERE operators = 0
  AND ID NOT IN( SELECT DISTINCT vacancy_id FROM appointment_has_vacancy );
END;$$

DELIMITER ;
