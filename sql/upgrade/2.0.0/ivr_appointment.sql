DROP PROCEDURE IF EXISTS patch_ivr_appointment;
  DELIMITER //
  CREATE PROCEDURE patch_ivr_appointment()
  BEGIN

    SELECT "Replacing participant_id with interview_id column in ivr_appointment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "ivr_appointment"
      AND COLUMN_NAME = "interview_id" );
    IF @test = 0 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      ALTER TABLE ivr_appointment 
      ADD COLUMN interview_id INT UNSIGNED NOT NULL
      AFTER participant_id;

      ALTER TABLE ivr_appointment 
      ADD INDEX fk_interview_id( interview_id ASC ), 
      ADD CONSTRAINT fk_ivr_appointment_interview_id 
      FOREIGN KEY( interview_id ) REFERENCES interview( id ) 
      ON DELETE NO ACTION ON UPDATE NO ACTION;

      -- fill in the new interview_id column using the existing participant_id column
      UPDATE ivr_appointment 
      JOIN interview 
      ON ivr_appointment.participant_id = interview.participant_id 
      SET interview_id = interview.id;

      -- now get rid of the participant column, index and constraint
      ALTER TABLE ivr_appointment
      DROP FOREIGN KEY fk_ivr_appointment_participant_id;

      ALTER TABLE ivr_appointment
      DROP INDEX fk_participant_id;

      ALTER TABLE ivr_appointment
      DROP COLUMN participant_id;

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_ivr_appointment();
DROP PROCEDURE IF EXISTS patch_ivr_appointment;
