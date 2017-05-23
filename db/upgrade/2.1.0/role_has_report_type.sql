DROP PROCEDURE IF EXISTS patch_role_has_report_type;
DELIMITER //
CREATE PROCEDURE patch_role_has_report_type()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Adding productivity report to operator+ role" AS "";

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".role_has_report_type( role_id, report_type_id ) ",
      "SELECT role.id, report_type.id ",
      "FROM ", @cenozo, ".role, ", @cenozo, ".report_type ",
      "WHERE role.name IN( 'administrator', 'supervisor' ) ",
      "AND report_type.name = 'appointment'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".role_has_report_type( role_id, report_type_id ) ",
      "SELECT role.id, report_type.id ",
      "FROM ", @cenozo, ".role, ", @cenozo, ".report_type ",
      "WHERE role.name = 'operator+' ",
      "AND report_type.name = 'productivity'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_role_has_report_type();
DROP PROCEDURE IF EXISTS patch_role_has_report_type;
