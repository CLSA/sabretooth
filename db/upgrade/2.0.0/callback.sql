DROP PROCEDURE IF EXISTS patch_callback;
  DELIMITER //
  CREATE PROCEDURE patch_callback()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_queue_state_site_id" );

    SELECT "Replacing participant_id with interview_id column in callback table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "callback"
      AND COLUMN_NAME = "interview_id" );
    IF @test = 0 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      ALTER TABLE callback 
      ADD COLUMN interview_id INT UNSIGNED NOT NULL
      AFTER participant_id;

      ALTER TABLE callback 
      ADD INDEX fk_interview_id( interview_id ASC ), 
      ADD CONSTRAINT fk_callback_interview_id 
      FOREIGN KEY( interview_id ) REFERENCES interview( id ) 
      ON DELETE NO ACTION ON UPDATE NO ACTION;

      -- fill in the new interview_id column using the existing participant_id column
      UPDATE callback 
      JOIN interview 
      ON callback.participant_id = interview.participant_id 
      SET interview_id = interview.id;

      -- now get rid of the participant column, index and constraint
      ALTER TABLE callback
      DROP FOREIGN KEY fk_callback_participant_id;

      ALTER TABLE callback
      DROP INDEX fk_participant_id;

      ALTER TABLE callback
      DROP COLUMN participant_id;

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_callback();
DROP PROCEDURE IF EXISTS patch_callback;
