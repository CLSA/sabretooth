-- participant list
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "participant" AND name = "list" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "supervisor" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "participant" AND name = "list" );
