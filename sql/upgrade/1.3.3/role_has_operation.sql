DROP PROCEDURE IF EXISTS patch_role_has_operation;
DELIMITER //
CREATE PROCEDURE patch_role_has_operation()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Adding new operations to roles" AS "";

    SET @sql = CONCAT(
      "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) ",
      "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation ",
      "WHERE type = 'push' AND subject = 'language' AND operation.name = 'edit' ",
      "AND role.name IN ( 'administrator' )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SET @sql = CONCAT(
      "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) ",
      "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation ",
      "WHERE type = 'widget' AND subject = 'language' AND operation.name = 'list' ",
      "AND role.name IN ( 'administrator', 'supervisor' )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SET @sql = CONCAT(
      "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) ",
      "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation ",
      "WHERE type = 'widget' AND subject = 'language' AND operation.name = 'view' ",
      "AND role.name IN ( 'administrator' )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SET @sql = CONCAT(
      "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) ",
      "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation ",
      "WHERE type = 'widget' AND subject = 'user' AND operation.name = 'add_language' ",
      "AND role.name IN( 'administrator', 'supervisor' )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SET @sql = CONCAT(
      "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) ",
      "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation ",
      "WHERE type = 'push' AND subject = 'user' AND operation.name = 'delete_language' ",
      "AND role.name IN( 'administrator', 'supervisor' )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SET @sql = CONCAT(
      "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) ",
      "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation ",
      "WHERE type = 'push' AND subject = 'user' AND operation.name = 'new_language' ",
      "AND role.name IN( 'administrator', 'supervisor' )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_role_has_operation();
DROP PROCEDURE IF EXISTS patch_role_has_operation;
