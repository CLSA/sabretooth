DROP PROCEDURE IF EXISTS patch_application_has_role;
DELIMITER //
CREATE PROCEDURE patch_application_has_role()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Removing defunct roles from application_has_role (cedar and opal)" AS "";

    SET @sql = CONCAT(
      "DELETE FROM ", @cenozo, ".application_has_role WHERE role_id IN ( ",
        "SELECT id FROM ", @cenozo, ".role WHERE name IN ( 'cedar', 'opal' ) ",
      ")" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_application_has_role();
DROP PROCEDURE IF EXISTS patch_application_has_role;
