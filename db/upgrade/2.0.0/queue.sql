SELECT "Removing extraneous whitespace in queue descriptions" AS "";

UPDATE queue SET description = REPLACE( description, "\n      ", " " );
