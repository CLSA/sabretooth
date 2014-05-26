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

    SELECT "Adding new language_id column to service table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "service"
      AND COLUMN_NAME = "language_id" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".service ",
        "ADD COLUMN language_id INT UNSIGNED NULL DEFAULT NULL" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "UPDATE ", @cenozo, ".service ",
        "SET language_id = ( SELECT id FROM ", @cenozo, ".language WHERE code = 'en' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".service ",
        "ADD INDEX fk_language_id (language_id ASC)" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".service ",
        "ADD CONSTRAINT fk_service_language_id ",
        "FOREIGN KEY (language_id) ",
        "REFERENCES ", @cenozo, ".language (id) ",
        "ON DELETE NO ACTION ",
        "ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

    END IF;

  END //
DELIMITER ;

CALL patch_service();
DROP PROCEDURE IF EXISTS patch_service;
