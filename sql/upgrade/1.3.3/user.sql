DROP PROCEDURE IF EXISTS patch_user;
DELIMITER //
CREATE PROCEDURE patch_user()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Removing defunct language column from user table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "user"
      AND COLUMN_NAME = "language" );
    IF @test = 1 THEN
      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".user ",
        "DROP COLUMN language" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

CALL patch_user();
DROP PROCEDURE IF EXISTS patch_user;
