SELECT "Updating queue to reflect changes in state/hold system" AS "";

UPDATE queue
SET description = "Participants who are not eligible to answer questionnaires."
WHERE name = "ineligible";

UPDATE queue
SET name = "not enrolled",
    title = "Not enrolled participants",
    description = "Participants who cannot be enrolled in the study."
WHERE name = "inactive";

UPDATE queue
SET name = "final hold",
    title = "Participants who are in a final hold",
    description = "Participants who will likely never be called again."
WHERE name = "refused consent";

UPDATE queue
SET name = "tracing",
    title = "Participants who require tracing",
    description = "Participants who are uncontactable because of missing or invalid contact information."
WHERE name = "condition";

UPDATE queue
SET name = "temporary hold",
    title = "Participants who are in a temporary hold",
    description = "Participants who cannot currently be called but may become available in the future."
WHERE name = "no address";

UPDATE queue
SET name = "proxy",
    title = "Participants who require a proxy",
    description = "Participants who cannot currently be called because they may require a proxy."
WHERE name = "no phone";

-- fix invalid queue description
UPDATE queue
SET description = "Participants who are ready to answer an questionnaire which has been disabled."
WHERE name = "qnaire disabled";
