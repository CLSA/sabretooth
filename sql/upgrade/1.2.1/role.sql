DROP PROCEDURE IF EXISTS patch_role;
DELIMITER //
CREATE PROCEDURE patch_role()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = CONCAT( SUBSTRING( DATABASE(), 1, LOCATE( 'sabretooth', DATABASE() ) - 1 ),
                          'cenozo' );

    -- add the all_sites column if it is missing
    SELECT "Making sure role table has all_sites column" AS "";
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "role"
      AND COLUMN_NAME = "all_sites" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".role ",
        "ADD COLUMN all_sites TINYINT(1) NOT NULL DEFAULT 0" );
      PREPARE statement FROM @sql;
      EXECUTE statement;

      DEALLOCATE PREPARE statement;
      SET @sql = CONCAT(
        "UPDATE ", @cenozo, ".role ",
        "SET all_sites = 1 ",
        "WHERE name IN ( 'administrator', 'opal', 'typist' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

    -- add new roles
    SELECT "Adding new roles" AS ""; 

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".role ( name, tier, all_sites ) VALUES "
      "( 'curator', 2, true ), "
      "( 'helpline', 2, true )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;
  END //
DELIMITER ;

CALL patch_role();
DROP PROCEDURE IF EXISTS patch_role;
