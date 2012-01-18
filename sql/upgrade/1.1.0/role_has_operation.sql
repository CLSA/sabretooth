INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "source_survey" AND name = "delete" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "source_survey" AND name = "edit" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "source_survey" AND name = "new" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "source_survey" AND name = "add" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "source_survey" AND name = "view" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "source_survey" AND name = "list" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "source_survey" AND name = "primary" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phase" AND name = "add_source_survey" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phase" AND name = "delete_source_survey" );

INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "clerk" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "source_survey" AND name = "delete" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "clerk" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "source_survey" AND name = "edit" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "clerk" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "source_survey" AND name = "new" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "clerk" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "source_survey" AND name = "add" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "clerk" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "source_survey" AND name = "view" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "clerk" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "source_survey" AND name = "list" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "clerk" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "source_survey" AND name = "primary" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "clerk" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phase" AND name = "add_source_survey" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "clerk" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phase" AND name = "delete_source_survey" );
