DROP PROCEDURE IF EXISTS patch_operation;
DELIMITER //
CREATE PROCEDURE patch_operation()
  BEGIN
    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    -- add new operations
    SELECT "Adding new operations" AS "";

    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "widget", "state", "add", true, "View a form for creating a new state." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "widget", "state", "add_role", true, "A form to add a role to a state." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "push", "state", "delete", true, "Removes a state from the system." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "push", "state", "delete_role", true, "Remove a state's role." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "push", "state", "edit", true, "Edits a state's details." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "widget", "state", "list", true, "List states in the system." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "push", "state", "new", true, "Add a new state to the system." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "push", "state", "new_role", true, "Add a role to a state." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "widget", "state", "view", true, "View a state's details." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "pull", "email", "report", true, "Download a email report." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "widget", "email", "report", true, "Set up a email report." );

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_operation();
DROP PROCEDURE IF EXISTS patch_operation;
