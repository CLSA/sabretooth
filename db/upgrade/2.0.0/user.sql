DROP PROCEDURE IF EXISTS patch_user;
DELIMITER //
CREATE PROCEDURE patch_user()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_queue_state_site_id" );

    SELECT "Setting user timezones based on single-site access" AS "";

    SET @sql = CONCAT(
      "CREATE TEMPORARY TABLE timezone ",
      "SELECT user_id, site.timezone ",
      "FROM access ",
      "JOIN ", @cenozo, ".site ON access.site_id = site.id ",
      "GROUP BY user_id ",
      "HAVING COUNT(*) = 1" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SET @sql = CONCAT(
      "UPDATE ", @cenozo, ".user ",
      "JOIN timezone ON user.id = timezone.user_id ",
      "SET user.timezone = timezone.timezone" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_user();
DROP PROCEDURE IF EXISTS patch_user;
