-- participant report
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "report", true, "Set up a participant report." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "participant", "report", true, "Download a participant report." );
