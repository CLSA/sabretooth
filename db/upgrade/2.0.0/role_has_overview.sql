DROP PROCEDURE IF EXISTS patch_role_has_overview;
  DELIMITER //
  CREATE PROCEDURE patch_role_has_overview()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Adding overviews to roles" AS "";

    SET @sql = CONCAT( 
      "INSERT IGNORE INTO ", @cenozo, ".role_has_overview( role_id, overview_id ) ",
      "SELECT role.id, overview.id ",
      "FROM ", @cenozo, ".role, ", @cenozo, ".overview ",
      "WHERE role.name IN ( 'administrator', 'supervisor' ) ",
      "AND overview.name IN ( 'progress' )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_role_has_overview();
DROP PROCEDURE IF EXISTS patch_role_has_overview;
