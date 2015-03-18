-- add the new indeces to the type, subject and name columns
-- we need to create a procedure which only alters the operation table if these
-- indeces are missing
DROP PROCEDURE IF EXISTS patch_operation;
DELIMITER //
CREATE PROCEDURE patch_operation()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "operation"
      AND COLUMN_NAME = "type"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE operation
      ADD INDEX dk_type (type ASC);
    END IF;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "operation"
      AND COLUMN_NAME = "subject"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE operation
      ADD INDEX dk_subject (subject ASC);
    END IF;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "operation"
      AND COLUMN_NAME = "name"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE operation
      ADD INDEX dk_name (name ASC);
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_operation();
DROP PROCEDURE IF EXISTS patch_operation;

-- Adding the recording functionality
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "recording", "list", true, "Lists recordings." );

-- Adding the new tree functionality
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "participant", "tree", true, "Returns the number of participants for every node of the participant tree." );

-- Adding the source survey functionality
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_survey", "delete", true, "Removes a phase's source-specific survey from the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_survey", "edit", true, "Edits the details of a phase's source-specific survey." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_survey", "new", true, "Creates a new source-specific survey for a phase." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_survey", "add", true, "View a form for creating new source-specific survey for a phase." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_survey", "view", true, "View the details of a phase's particular source-specific survey." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_survey", "list", true, "Lists a phase's source-specific survey entries." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "source_survey", "primary", true, "Retrieves base source-specific survey information." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phase", "add_source_survey", true, "A form to add a new source-specific survey to the phase." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phase", "delete_source_survey", true, "Remove a phase's source-specific survey." );

-- Adding the source withdraw functionality
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_withdraw", "delete", true, "Removes a questionnaire's source-specific withdraw survey from the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_withdraw", "edit", true, "Edits the details of a questionnaire's source-specific withdraw survey." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_withdraw", "new", true, "Creates a new source-specific withdraw survey for a questionnaire." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_withdraw", "add", true, "View a form for creating new source-specific withdraw survey for a questionnaire." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_withdraw", "view", true, "View the details of a questionnaire's particular source-specific withdraw survey." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_withdraw", "list", true, "Lists a questionnaire's source-specific withdraw survey entries." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "source_withdraw", "primary", true, "Retrieves base source-specific withdraw survey information." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "add_source_withdraw", true, "A form to add a new source-specific withdraw survey to the questionnaire." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "delete_source_withdraw", true, "Remove a questionnaire's source-specific withdraw survey." );
