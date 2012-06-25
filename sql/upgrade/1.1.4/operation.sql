-- add the new participant list pull
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "participant", "list", true, "Retrieves base information for a list of participant." );
