DROP PROCEDURE IF EXISTS patch_service;
DELIMITER //
CREATE PROCEDURE patch_service()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = REPLACE( DATABASE(), 'sabretooth', 'cenozo' );

    SELECT "Adding new service.release_based column" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "service"
      AND COLUMN_NAME = "release_based" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".service ",
        "ADD COLUMN release_based TINYINT(1) NOT NULL DEFAULT 1" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;
  END //
DELIMITER ;

CALL patch_service();
DROP PROCEDURE IF EXISTS patch_service;
