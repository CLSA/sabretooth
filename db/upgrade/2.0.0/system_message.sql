DROP PROCEDURE IF EXISTS patch_system_message;
  DELIMITER //
  CREATE PROCEDURE patch_system_message()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_queue_state_site_id" );

    -- determine the application name
    SET @application = (
    SELECT RIGHT( DATABASE(),
                  CHAR_LENGTH( DATABASE() ) -
                  CHAR_LENGTH( LEFT( USER(), LOCATE( '@', USER() ) ) ) ) );

    SELECT "Moving system_message table to cenozo" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "system_message" );
    IF @test = 1 THEN
      SET @sql = CONCAT(
        "INSERT INTO ", @cenozo, ".system_message( application_id, site_id, role_id, title, note ) ",
        "SELECT application.id, site_id, role_id, system_message.title, note ",
        "FROM ", @cenozo, ".application, system_message ",
        "WHERE application.name = '", @application, "'" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE system_message;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_system_message();
DROP PROCEDURE IF EXISTS patch_system_message;
