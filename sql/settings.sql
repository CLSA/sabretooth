-- -----------------------------------------------------
-- Settings
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- settings
DELETE FROM setting;
INSERT INTO setting( category, name, value )
VALUES( "queue", "missed enabled", "1" );
INSERT INTO setting( category, name, value )
VALUES( "queue", "appointment enabled", "1" );
INSERT INTO setting( category, name, value )
VALUES( "queue", "available enabled", "1" );
INSERT INTO setting( category, name, value )
VALUES( "queue", "previously busy enabled", "1" );
INSERT INTO setting( category, name, value )
VALUES( "queue", "previously no answer enabled", "1" );
INSERT INTO setting( category, name, value )
VALUES( "queue", "previously answering machine enabled", "1" );
INSERT INTO setting( category, name, value )
VALUES( "queue", "previously fax enabled", "1" );
INSERT INTO setting( category, name, value )
VALUES( "queue", "general enabled", "1" );

INSERT INTO setting( category, name, value )
VALUES( "appointments", "allow overflow", "1" );

INSERT INTO setting( category, name, value )
VALUES( "callback limit", "consecutive failed attempts", "10" );
INSERT INTO setting( category, name, value )
VALUES( "callback delay", "answering machine", "4320" );
INSERT INTO setting( category, name, value )
VALUES( "callback delay", "busy", "15" );
INSERT INTO setting( category, name, value )
VALUES( "callback delay", "fax", "15" );
INSERT INTO setting( category, name, value )
VALUES( "callback delay", "no answer", "2160" );

COMMIT;
