-- remove participant_sync operations
DELETE FROM operation
WHERE subject = "participant"
AND name = "sync";

-- remove consent outstanding report
DELETE FROM operation
WHERE subject = "consent_outstanding";
