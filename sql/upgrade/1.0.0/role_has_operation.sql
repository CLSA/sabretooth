-- add the new queue view widget to admins and supervisors
INSERT IGNORE INTO role_has_operation
 SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
     operation_id = ( SELECT id FROM operation WHERE
       type = "widget" AND subject = "queue" AND name = "view" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "interview" AND name = "view" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "interview" AND name = "list" );

INSERT IGNORE INTO role_has_operation
 SET role_id = ( SELECT id FROM role WHERE name = "supervisor" ),
     operation_id = ( SELECT id FROM operation WHERE
       type = "widget" AND subject = "queue" AND name = "view" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "supervisor" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "interview" AND name = "view" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "supervisor" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "interview" AND name = "list" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "supervisor" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "interview" AND name = "edit" );
