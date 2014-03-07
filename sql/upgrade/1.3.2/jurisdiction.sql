DROP PROCEDURE IF EXISTS patch_jurisdiction;
DELIMITER //
CREATE PROCEDURE patch_jurisdiction()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Removing defunct distance column from jurisdiction table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "jurisdiction"
      AND COLUMN_NAME = "distance" );
    IF @test = 1 THEN
      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".jurisdiction ",
        "DROP COLUMN distance" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

CALL patch_jurisdiction();
DROP PROCEDURE IF EXISTS patch_jurisdiction;
