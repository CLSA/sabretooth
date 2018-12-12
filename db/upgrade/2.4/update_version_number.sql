DROP PROCEDURE IF EXISTS upgrade_application_number;
DELIMITER //
CREATE PROCEDURE upgrade_application_number()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Upgrading application version number" AS "";

    SET @sql = CONCAT(
      "UPDATE ", @cenozo, ".application ",
      "SET version = '2.4' ",
      "WHERE '", DATABASE(), "' LIKE CONCAT( '%_', name )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL upgrade_application_number();
DROP PROCEDURE IF EXISTS upgrade_application_number;
