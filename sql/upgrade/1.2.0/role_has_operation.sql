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

      -- event
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "event" AND name = "delete" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "event" AND name = "edit" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "event" AND name = "new" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "event" AND name = "add" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "event" AND name = "view" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "event" AND name = "list" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "pull" AND subject = "event" AND name = "primary" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "participant" AND name = "add_event" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "participant" AND name = "delete_event" );

      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "supervisor" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "event" AND name = "list" );

      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "operator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "event" AND name = "list" );

      -- participant report
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "participant" AND name = "report" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "pull" AND subject = "participant" AND name = "report" );

      -- qnaire event types
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "qnaire" AND name = "add_event_type" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "qnaire" AND name = "new_event_type" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "qnaire" AND name = "delete_event_type" );

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_role_has_operation();
DROP PROCEDURE IF EXISTS patch_role_has_operation;
