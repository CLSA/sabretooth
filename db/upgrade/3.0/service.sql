SELECT 'Updating services' AS '';

UPDATE service SET restricted = 0
WHERE subject = "site"
AND method = "GET";
