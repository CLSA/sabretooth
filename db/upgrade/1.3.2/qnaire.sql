DROP PROCEDURE IF EXISTS patch_qnaire;
DELIMITER //
CREATE PROCEDURE patch_qnaire()
  BEGIN
    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Renaming interview_method_id in qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "interview_method_id" );
    IF @test = 1 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      ALTER TABLE qnaire
      DROP FOREIGN KEY fk_qnaire_interview_method_id;

      ALTER TABLE qnaire
      DROP INDEX fk_interview_method_id;

      ALTER TABLE qnaire
      CHANGE interview_method_id default_interview_method_id INT UNSIGNED NOT NULL;

      ALTER TABLE qnaire
      ADD INDEX fk_default_interview_method_id( default_interview_method_id ASC ),
      ADD CONSTRAINT fk_qnaire_default_interview_method_id
      FOREIGN KEY( default_interview_method_id ) REFERENCES interview_method( id )
      ON DELETE NO ACTION ON UPDATE NO ACTION;

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;

    SELECT "Adding new first_attempt_event_type_id, reached_event_type_id and completed_event_type_id columns to qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "first_attempt_event_type_id" );
    IF @test = 0 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      SET @sql = CONCAT(
        "ALTER TABLE qnaire ",
        "ADD COLUMN first_attempt_event_type_id INT UNSIGNED NOT NULL ",
        "AFTER rank" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "ALTER TABLE qnaire ",
        "ADD COLUMN reached_event_type_id INT UNSIGNED NOT NULL ",
        "AFTER first_attempt_event_type_id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "ALTER TABLE qnaire ",
        "ADD COLUMN completed_event_type_id INT UNSIGNED NOT NULL ",
        "AFTER reached_event_type_id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "ALTER TABLE qnaire ",
        "ADD INDEX fk_first_attempt_event_type_id( first_attempt_event_type_id ASC ), ",
        "ADD INDEX fk_reached_event_type_id( reached_event_type_id ASC ), ",
        "ADD INDEX fk_completed_event_type_id( completed_event_type_id ASC ), ",
        "ADD CONSTRAINT fk_qnaire_first_attempt_event_type_id ",
        "FOREIGN KEY( first_attempt_event_type_id ) REFERENCES ", @cenozo, ".interview_method( id ) ",
        "ON DELETE NO ACTION ON UPDATE NO ACTION, ",
        "ADD CONSTRAINT fk_qnaire_reached_event_type_id ",
        "FOREIGN KEY( reached_event_type_id ) REFERENCES ", @cenozo, ".interview_method( id ) ",
        "ON DELETE NO ACTION ON UPDATE NO ACTION, ",
        "ADD CONSTRAINT fk_qnaire_completed_event_type_id ",
        "FOREIGN KEY( completed_event_type_id ) REFERENCES ", @cenozo, ".interview_method( id ) ",
        "ON DELETE NO ACTION ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT "Adding a new event-types for every qnaire" AS "";
      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".event_type( name, description ) ",
        "SELECT CONCAT( 'first attempt (', name, ')' ), ",
               "CONCAT( 'First attempt to contact (for the ', name, ' interview)' ) ",
        "FROM qnaire" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT "Adding a new event-types for every qnaire" AS "";
      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".event_type( name, description ) ",
        "SELECT CONCAT( 'reached (', name, ')' ), ",
               "CONCAT( 'The participant was first reached (for the ', name, ' interview)' ) ",
        "FROM qnaire" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT "Adding a new event-types for every qnaire" AS "";
      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".event_type( name, description ) ",
        "SELECT CONCAT( 'completed (', name, ')' ), ",
               "CONCAT( 'Interview completed (for the ', name, ' interview)' ) ",
        "FROM qnaire" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "UPDATE qnaire ",
        "JOIN ", @cenozo, ".event_type first_attempt_event_type ",
        "ON first_attempt_event_type.name = CONCAT( 'first attempt (', qnaire.name, ')' ) ", 
        "JOIN ", @cenozo, ".event_type reached_event_type ",
        "ON reached_event_type.name = CONCAT( 'reached (', qnaire.name, ')' ) ", 
        "JOIN ", @cenozo, ".event_type completed_event_type ",
        "ON completed_event_type.name = CONCAT( 'completed (', qnaire.name, ')' ) ", 
        "SET first_attempt_event_type_id = first_attempt_event_type.id, ",
            "reached_event_type_id = reached_event_type.id, ",
            "completed_event_type_id = completed_event_type.id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_qnaire();
DROP PROCEDURE IF EXISTS patch_qnaire;
