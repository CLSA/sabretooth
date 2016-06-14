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

    SELECT "Removing defunct roles from application_has_role (cedar)" AS "";

    SET @sql = CONCAT(
      "DELETE FROM ", @cenozo, ".application_has_role WHERE role_id IN ( ",
        "SELECT id FROM ", @cenozo, ".role WHERE name IN ( 'cedar' ) ",
      ")" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SELECT "Adding new operator+ role to application_has_role" AS "";

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".application_has_role ( application_id, role_id ) ",
      "SELECT application.id, role.id ",
      "FROM ", @cenozo, ".role, ", @cenozo, ".application ",
      "JOIN ", @cenozo, ".application_type ON application.application_type_id = application_type.id ",
      "WHERE application_type.name = 'sabretooth' ",
      "AND role.name = 'operator+'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_application_has_role();
DROP PROCEDURE IF EXISTS patch_application_has_role;
