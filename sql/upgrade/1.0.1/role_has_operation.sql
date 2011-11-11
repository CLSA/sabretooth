-- add the new operations to admins and supervisors
INSERT IGNORE INTO role_has_operation
 SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
     operation_id = ( SELECT id FROM operation WHERE
       type = "pull" AND subject = "user" AND name = "list" );
INSERT IGNORE INTO role_has_operation
 SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
     operation_id = ( SELECT id FROM operation WHERE
       type = "push" AND subject = "user" AND name = "set_password" );
INSERT IGNORE INTO role_has_operation
 SET role_id = ( SELECT id FROM role WHERE name = "supervisor" ),
     operation_id = ( SELECT id FROM operation WHERE
       type = "pull" AND subject = "user" AND name = "list" );
INSERT IGNORE INTO role_has_operation
 SET role_id = ( SELECT id FROM role WHERE name = "supervisor" ),
     operation_id = ( SELECT id FROM operation WHERE
       type = "push" AND subject = "user" AND name = "set_password" );

-- operations need to be able to delete consent (see push/participant_withdraw)
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "operator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "consent" AND name = "delete" );
