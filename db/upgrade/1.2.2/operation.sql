DROP PROCEDURE IF EXISTS patch_operation;
DELIMITER //
CREATE PROCEDURE patch_operation()
  BEGIN
    -- add new operations
    SELECT "Adding new operations" AS "";

    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "pull", "participant", "multiedit", true,
            "Gets a summary of participants affected by a multiedit operation." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "push", "participant", "multiedit", true,
            "Edits the details of a group of participants." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "widget", "participant", "multiedit", true,
            "A form to edit details of multiple participants at once." );
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_operation();
DROP PROCEDURE IF EXISTS patch_operation;
