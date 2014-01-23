DELETE FROM setting
WHERE category = "queue state"
AND name LIKE "% not available";

UPDATE setting SET
name = REPLACE( name, "available", "ready" ),
description = REPLACE( description, "available", "ready" )
WHERE category = "queue state"
AND name LIKE "% available";

UPDATE setting SET name = "new participant"
WHERE name = "new participant ready";
