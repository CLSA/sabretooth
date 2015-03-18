-- add the site calendar and feed to the adminstrator and clerk
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "site" AND name = "calendar" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "site" AND name = "feed" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "clerk" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "site" AND name = "calendar" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "clerk" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "site" AND name = "feed" );
