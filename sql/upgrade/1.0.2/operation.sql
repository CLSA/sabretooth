-- Adding a new operations
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "recording", "list", true, "Lists recordings." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "recording", "play", true, "Plays a recording." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "recording", "pause", true, "Pauses a recording." );
