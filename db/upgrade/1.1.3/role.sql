-- deleting the clerk and viewer roles until they are redesigned
DELETE FROM role WHERE name = "clerk";
DELETE FROM role WHERE name = "viewer";
INSERT IGNORE INTO role( name ) VALUES( "typist" );
