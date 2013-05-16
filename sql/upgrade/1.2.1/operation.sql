DROP PROCEDURE IF EXISTS patch_operation;
DELIMITER //
CREATE PROCEDURE patch_operation()
  BEGIN
    -- add new operations
    SELECT "Adding new operations" AS "";

    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "widget", "participant", "withdraw", true, "Pseudo-assignment to handle participant withdraws." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "pull", "consent_required", "report", true, "Download a consent required report." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "widget", "consent_required", "report", true, "Set up a consent required report." );
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_operation();
DROP PROCEDURE IF EXISTS patch_operation;
