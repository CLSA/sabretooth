-- add new queue state settings
INSERT IGNORE INTO setting( category, name, type, value, description )
SELECT "queue state", name, "boolean", "true",
       CONCAT( "Whether to assign participants from the \"", title, "\" queue." )
FROM queue
WHERE rank IS NOT NULL
AND name LIKE '%not available'
ORDER BY rank;
