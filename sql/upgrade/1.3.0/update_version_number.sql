DROP PROCEDURE IF EXISTS upgrade_service_number;
DELIMITER //
CREATE PROCEDURE upgrade_service_number()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Upgrading service version number" AS "";

    SET @sql = CONCAT(
      "UPDATE ", @cenozo, ".service ",
      "SET version = '1.3.0' ",
      "WHERE '", DATABASE(), "' LIKE CONCAT( '%_', name )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL upgrade_service_number();
DROP PROCEDURE IF EXISTS upgrade_service_number;
