-- only add the new role_has_operation entries if the database hasn't yet been converted
DROP PROCEDURE IF EXISTS patch_role_has_operation;
DELIMITER //
CREATE PROCEDURE patch_role_has_operation()
  BEGIN
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "role" );
    IF @test = 1 THEN

      -- participant report
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "participant" AND name = "report" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "pull" AND subject = "participant" AND name = "report" );

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_role_has_operation();
DROP PROCEDURE IF EXISTS patch_role_has_operation;
