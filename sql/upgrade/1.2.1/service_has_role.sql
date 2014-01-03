DROP PROCEDURE IF EXISTS patch_service_has_role;
DELIMITER //
CREATE PROCEDURE patch_service_has_role()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Adding typist role to Sabretooth services" AS "";
    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".service_has_role( service_id, role_id ) "
      "SELECT service.id, role.id FROM ", @cenozo, ".service, ", @cenozo, ".role "
      "WHERE service.name LIKE 'sabretooth%' "
      "AND role.name = 'typist'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_service_has_role();
DROP PROCEDURE IF EXISTS patch_service_has_role;
