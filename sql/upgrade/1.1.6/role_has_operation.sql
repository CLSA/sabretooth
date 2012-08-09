-- add voip spy operation to supervisor
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "supervisor" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "voip" AND name = "spy" );
