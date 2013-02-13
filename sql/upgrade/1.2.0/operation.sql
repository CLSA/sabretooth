-- remove participant_sync operations
DELETE FROM operation
WHERE subject = "participant"
AND name = "sync";
