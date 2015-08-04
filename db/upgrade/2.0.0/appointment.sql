DROP PROCEDURE IF EXISTS patch_appointment;
  DELIMITER //
  CREATE PROCEDURE patch_appointment()
  BEGIN

    SELECT "Replacing participant_id with interview_id column in appointment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND COLUMN_NAME = "interview_id" );
    IF @test = 0 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      ALTER TABLE appointment 
      ADD COLUMN interview_id INT UNSIGNED NOT NULL
      AFTER participant_id;

      ALTER TABLE appointment 
      ADD INDEX fk_interview_id( interview_id ASC ), 
      ADD CONSTRAINT fk_appointment_interview_id 
      FOREIGN KEY( interview_id ) REFERENCES interview( id ) 
      ON DELETE NO ACTION ON UPDATE NO ACTION;

      -- fill in the new interview_id column using the existing participant_id column
      UPDATE appointment 
      JOIN interview 
      ON appointment.participant_id = interview.participant_id 
      SET interview_id = interview.id;

      -- now get rid of the participant column, index and constraint
      ALTER TABLE appointment
      DROP FOREIGN KEY fk_appointment_participant_id;

      ALTER TABLE appointment
      DROP INDEX fk_participant_id;

      ALTER TABLE appointment
      DROP COLUMN participant_id;

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;

    SELECT "Changing appointment types to long/short" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND COLUMN_TYPE = "enum('full','half')" );
    IF @test = 1 THEN
      ALTER TABLE appointment
      MODIFY COLUMN type CHAR(5) NOT NULL DEFAULT 'long';
      UPDATE appointment SET type = IF( type = "full", "long", "short" );
      ALTER TABLE appointment
      MODIFY COLUMN type enum('long','short') NOT NULL DEFAULT 'long';
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_appointment();
DROP PROCEDURE IF EXISTS patch_appointment;
