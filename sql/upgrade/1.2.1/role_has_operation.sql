DROP PROCEDURE IF EXISTS patch_role_has_operation;
DELIMITER //
CREATE PROCEDURE patch_role_has_operation()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = CONCAT( SUBSTRING( DATABASE(), 1, LOCATE( 'sabretooth', DATABASE() ) - 1 ),
                          'cenozo' );

    SELECT "Adding rescore operations to typist role" AS "";
    SET @sql = CONCAT(
      "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) "
      "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation "
      "WHERE subject = 'interview' AND operation.name IN( 'list', 'view', 'rescore' ) "
      "AND role.name = 'typist'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_role_has_operation();
DROP PROCEDURE IF EXISTS patch_role_has_operation;
