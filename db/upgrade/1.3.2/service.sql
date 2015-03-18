DROP PROCEDURE IF EXISTS patch_service;
DELIMITER //
CREATE PROCEDURE patch_service()
  BEGIN
    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Adding new release_event_type_id column to service table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "service"
      AND COLUMN_NAME = "release_event_type_id" );
    IF @test = 0 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".service ",
        "ADD COLUMN release_event_type_id INT UNSIGNED NOT NULL" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".service ",
        "ADD INDEX fk_release_event_type_id( release_event_type_id ASC ), ",
        "ADD CONSTRAINT fk_service_release_event_type_id ",
        "FOREIGN KEY( release_event_type_id ) REFERENCES ", @cenozo, ".interview_method( id ) ",
        "ON DELETE NO ACTION ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT "Adding a new event-type for every service" AS "";
      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".event_type( name, description ) ",
        "SELECT CONCAT( 'released to ', name ), CONCAT( 'Released the participant to ', title ) ",
        "FROM ", @cenozo, ".service" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "UPDATE ", @cenozo, ".service ",
        "JOIN ", @cenozo, ".event_type ",
        "ON event_type.name = CONCAT( 'released to ', service.name ) ", 
        "SET release_event_type_id = event_type.id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

      SELECT "Inserting the new release events for all released participants" AS "";
      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".event( participant_id, event_type_id, datetime ) ",
        "SELECT service_has_participant.participant_id, ",
               "service.release_event_type_id, ",
               "service_has_participant.datetime ",
        "FROM ", @cenozo, ".service_has_participant ",
        "JOIN ", @cenozo, ".service ON service_has_participant.service_id = service.id ",
        "WHERE service_has_participant.datetime IS NOT NULL" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_service();
DROP PROCEDURE IF EXISTS patch_service;
