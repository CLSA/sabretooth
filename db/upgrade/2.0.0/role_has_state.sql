DROP PROCEDURE IF EXISTS patch_role_has_state;
DELIMITER //
CREATE PROCEDURE patch_role_has_state()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Adding new operator+ role to role_has_state" AS "";

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".role_has_state ( role_id, state_id ) ",
      "SELECT role.id, role_has_state.state_id ",
      "FROM ", @cenozo, ".role, ", @cenozo, ".role_has_state ",
      "JOIN ", @cenozo, ".role AS operator_role ON role_has_state.role_id = operator_role.id ",
      "WHERE role.name = 'operator+' ",
      "AND operator_role.name = 'operator'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_role_has_state();
DROP PROCEDURE IF EXISTS patch_role_has_state;
