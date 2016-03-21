DROP PROCEDURE IF EXISTS patch_site;
DELIMITER //
CREATE PROCEDURE patch_site()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
      GROUP BY unique_constraint_schema );

    SELECT "Removing application site relationship if there are no access records for that site" AS "";

    SET @sql = CONCAT(
      "DELETE FROM ", @cenozo, ".application_has_site ",
      "WHERE site_id IN ( ",
        "SELECT id FROM ( ",
          "SELECT site.id FROM ", @cenozo, ".site ",
          "LEFT JOIN access on site.id = access.site_id ",
          "WHERE access.id IS NULL "
        ") AS temp ",
      ")" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_site();
DROP PROCEDURE IF EXISTS patch_site;
