DROP PROCEDURE IF EXISTS patch_source;
DELIMITER //
CREATE PROCEDURE patch_source()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Adding the new override_quota column to the source table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "source"
      AND COLUMN_NAME = "override_quota" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".source ",
        "ADD COLUMN override_quota TINYINT(1) NOT NULL DEFAULT 0 ",
        "AFTER name" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

    SELECT "Adding the new description column to the source table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "source"
      AND COLUMN_NAME = "description" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".source ",
        "ADD COLUMN description TEXT NULL DEFAULT NULL ",
        "AFTER override_quota" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

CALL patch_source();
DROP PROCEDURE IF EXISTS patch_source;
