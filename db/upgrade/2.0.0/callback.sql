DROP PROCEDURE IF EXISTS patch_callback;
  DELIMITER //
  CREATE PROCEDURE patch_callback()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

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

      -- delete any remaining callbacks which didn't have an interview
      DELETE FROM callback WHERE interview_id = 0;

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

    SELECT "Modifiying constraint delete rules in callback table" AS "";

    SET @test = (
      SELECT DELETE_RULE
      FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = "callback"
      AND REFERENCED_TABLE_NAME = "interview" );
    IF @test = "NO ACTION" THEN
      ALTER TABLE callback
      DROP FOREIGN KEY fk_callback_interview_id;

      ALTER TABLE callback
      ADD CONSTRAINT fk_callback_interview_id
      FOREIGN KEY (interview_id)
      REFERENCES interview (id)
      ON DELETE CASCADE
      ON UPDATE NO ACTION;
    END IF;

    SET @test = (
      SELECT DELETE_RULE
      FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = "callback"
      AND REFERENCED_TABLE_NAME = "assignment" );
    IF @test = "NO ACTION" THEN
      ALTER TABLE callback
      DROP FOREIGN KEY fk_callback_assignment_id;

      ALTER TABLE callback
      ADD CONSTRAINT fk_callback_assignment_id
      FOREIGN KEY (assignment_id)
      REFERENCES assignment (id)
      ON DELETE SET NULL
      ON UPDATE NO ACTION;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_callback();
DROP PROCEDURE IF EXISTS patch_callback;
