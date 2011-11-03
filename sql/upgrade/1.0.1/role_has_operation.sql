-- add the new user list pull operation to admins and supervisors
INSERT IGNORE INTO role_has_operation
 SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
     operation_id = ( SELECT id FROM operation WHERE
       type = "pull" AND subject = "user" AND name = "list" );
INSERT IGNORE INTO role_has_operation
 SET role_id = ( SELECT id FROM role WHERE name = "supervisor" ),
     operation_id = ( SELECT id FROM operation WHERE
       type = "pull" AND subject = "user" AND name = "list" );
