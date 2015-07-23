DROP PROCEDURE IF EXISTS patch_setting_value;
  DELIMITER //
  CREATE PROCEDURE patch_setting_value()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_queue_state_site_id" );

    -- determine this application's name
    SET @application = (
      SELECT REPLACE( DATABASE(),
                      CONCAT( SUBSTRING( USER(), 1, LOCATE( '@', USER() ) - 1 ), "_" ),
                      "" ) );

    SELECT "Removing defunct setting_value table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "setting_value" );
    IF @test = 1 THEN

      -- save the setting data to use later
      SET @sql = CONCAT(
        "CREATE TABLE old_setting_value AS ",
        "SELECT setting.category, setting.name, site.id AS site_id, ",
               "IFNULL( setting_value.value, setting.value ) AS value ",
        "FROM ", @cenozo, ".site ",
        "JOIN ", @cenozo, ".application_has_site ON site.id = application_has_site.site_id ",
        "JOIN ", @cenozo, ".application ON application_has_site.application_id = application.id ",
        "CROSS JOIN setting ",
        "LEFT JOIN setting_value ",
        "ON setting.id = setting_value.setting_id ",
        "AND site.id = setting_value.site_id ",
        "WHERE application.name = '", @application, "' ",
        "ORDER BY site.id, setting.id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE setting_value;

    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_setting_value();
DROP PROCEDURE IF EXISTS patch_setting_value;
