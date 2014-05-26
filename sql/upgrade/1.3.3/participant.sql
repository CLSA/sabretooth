DROP PROCEDURE IF EXISTS patch_participant;
DELIMITER //
CREATE PROCEDURE patch_participant()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Replacing participant table's language column with foreign key to langauge table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "participant"
      AND COLUMN_NAME = "language" );
    IF @test = 1 THEN
      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".participant ",
        "ADD COLUMN language_id INT UNSIGNED NULL DEFAULT NULL ",
        "AFTER language" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".participant ",
        "ADD INDEX fk_language_id (language_id ASC)" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".participant ",
        "ADD CONSTRAINT fk_participant_language_id ",
        "FOREIGN KEY (language_id) ",
        "REFERENCES ", @cenozo, ".language (id) ",
        "ON DELETE NO ACTION ",
        "ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "UPDATE ", @cenozo, ".participant ",
        "SET language_id = ( SELECT id FROM ", @cenozo, ".language WHERE code = 'en' ) ",
        "WHERE language = 'en'" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "UPDATE ", @cenozo, ".participant ",
        "SET language_id = ( SELECT id FROM ", @cenozo, ".language WHERE code = 'fr' ) ",
        "WHERE language = 'fr'" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".participant ",
        "DROP COLUMN language" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

CALL patch_participant();
DROP PROCEDURE IF EXISTS patch_participant;
