SELECT "Removing defunct operations from activity" AS "";

DELETE FROM activity
WHERE operation_id IN (
  SELECT id FROM operation WHERE subject = "call_attempts"
);

SELECT "Converting reverse_withdraw activities to withdraw" AS "";

UPDATE activity SET
operation_id = (
  SELECT id
  FROM operation
  WHERE type = "push"
  AND subject = "participant"
  AND name = "withdraw"
),
query = concat(
  substring( query, 1, char_length( query ) - 1 ),
  's:6:"cancel";s:1:"1";',
  "}"
)
WHERE operation_id = (
  SELECT id FROM operation WHERE name = "reverse_withdraw"
);
