DROP PROCEDURE IF EXISTS patch_role_has_operation;
DELIMITER //
CREATE PROCEDURE patch_role_has_operation()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = CONCAT( SUBSTRING( DATABASE(), 1, LOCATE( 'sabretooth', DATABASE() ) - 1 ),
                          'cenozo' );

    SELECT "Adding new operations to roles" AS "";
    SET @sql = CONCAT(
      "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) "
      "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation "
      "WHERE subject = 'interview' AND operation.name IN( 'list', 'view', 'rescore' ) "
      "AND role.name = 'typist'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SET @sql = CONCAT(
      "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) "
      "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation "
      "WHERE type = 'widget' AND subject = 'participant' AND operation.name = 'withdraw' "
      "AND role.name IN( 'administrator', 'supervisor' )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SET @sql = CONCAT(
      "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) "
      "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation "
      "WHERE type = 'push' AND subject = 'participant' AND operation.name = 'withdraw' "
      "AND role.name IN( 'administrator', 'supervisor' )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_role_has_operation();
DROP PROCEDURE IF EXISTS patch_role_has_operation;
