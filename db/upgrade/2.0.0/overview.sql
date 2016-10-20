DROP PROCEDURE IF EXISTS patch_overview;
  DELIMITER //
  CREATE PROCEDURE patch_overview()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Adding additional overviews" AS "";

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".overview( name, title, description ) VALUES ",
      "( 'progress', 'Progress', 'Overview of interview progress.' )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_overview();
DROP PROCEDURE IF EXISTS patch_overview;
