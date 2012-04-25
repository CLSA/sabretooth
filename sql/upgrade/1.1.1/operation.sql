-- add in the new rescore and recording operations
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview", "rescore", true, "Provides an interface to rescore interview based on recordings." );
