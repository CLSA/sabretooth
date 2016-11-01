DROP PROCEDURE IF EXISTS patch_interview_last_appointment;
DELIMITER //
CREATE PROCEDURE patch_interview_last_appointment()
  BEGIN

    SELECT "Adding new interview_last_appointment caching table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "interview_last_appointment" );
    IF @test = 0 THEN

      CREATE TABLE IF NOT EXISTS interview_last_appointment (
        interview_id INT UNSIGNED NOT NULL,
        appointment_id INT UNSIGNED NULL,
        update_timestamp TIMESTAMP NOT NULL,
        create_timestamp TIMESTAMP NOT NULL,
        PRIMARY KEY (interview_id),
        INDEX fk_appointment_id (appointment_id ASC),
        CONSTRAINT fk_interview_last_appointment_interview_id
          FOREIGN KEY (interview_id)
          REFERENCES interview (id)
          ON DELETE CASCADE
          ON UPDATE CASCADE,
        CONSTRAINT fk_interview_last_appointment_appointment_id
          FOREIGN KEY (appointment_id)
          REFERENCES appointment (id)
          ON DELETE SET NULL
          ON UPDATE NO ACTION)
      ENGINE = InnoDB;

      SELECT "Populating interview_last_appointment table" AS "";

      REPLACE INTO interview_last_appointment( interview_id, appointment_id )
      SELECT interview.id, appointment.id
      FROM interview
      LEFT JOIN appointment ON interview.id = appointment.interview_id
      AND appointment.datetime <=> (
        SELECT MAX( datetime )
        FROM appointment
        WHERE interview.id = appointment.interview_id
        GROUP BY appointment.interview_id
        LIMIT 1
      );

    END IF;

  END //
DELIMITER ;

CALL patch_interview_last_appointment();
DROP PROCEDURE IF EXISTS patch_interview_last_appointment;
