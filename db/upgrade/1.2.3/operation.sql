DROP PROCEDURE IF EXISTS patch_operation;
DELIMITER //
CREATE PROCEDURE patch_operation()
  BEGIN
    -- add new operations
    SELECT "Adding new operations" AS "";

    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "push", "prerecruit", "select", true,
            "Sets pre-recruit populations by quota." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "widget", "prerecruit", "select", true,
            "Provides a list of quotas for entering pre-recruit populations." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "widget", "participant", "search", true,
            "Search for participants based on partial information." );
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_operation();
DROP PROCEDURE IF EXISTS patch_operation;
