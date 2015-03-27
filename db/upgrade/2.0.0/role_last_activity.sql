DROP PROCEDURE IF EXISTS patch_role_last_activity;
  DELIMITER //
  CREATE PROCEDURE patch_role_last_activity()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_system_message_site_id" );

    SELECT "Creating new role_last_activity view" AS "";

    SET @sql = CONCAT(
      "CREATE OR REPLACE VIEW role_last_activity AS ",
      "SELECT role.id AS role_id, ",
             "activity.id AS activity_id ",
      "FROM ", @cenozo, ".role ",
      "JOIN activity on role.id = activity.role_id ",
      "WHERE activity.datetime = ( ",
        "SELECT MAX( a.datetime ) ",
        "FROM activity a ",
        "WHERE a.role_id = role.id ",
      ")" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_role_last_activity();
DROP PROCEDURE IF EXISTS patch_role_last_activity;
