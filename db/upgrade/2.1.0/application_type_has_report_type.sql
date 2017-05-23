DROP PROCEDURE IF EXISTS patch_application_type_has_report_type;
  DELIMITER //
  CREATE PROCEDURE patch_application_type_has_report_type()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Adding records to application_type_has_report_type table" AS "";

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".application_type_has_report_type( application_type_id, report_type_id ) ",
      "SELECT application_type.id, report_type.id ",
      "FROM ", @cenozo, ".application_type, ", @cenozo, ".report_type ",
      "WHERE application_type.name = 'sabretooth' ",
      "AND report_type.name = 'appointment'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_application_type_has_report_type();
DROP PROCEDURE IF EXISTS patch_application_type_has_report_type;
