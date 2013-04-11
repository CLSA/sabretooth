DELETE FROM activity WHERE operation_id IN (
  SELECT id FROM operation WHERE subject = "demographics" AND name = "report"
);
DELETE FROM activity WHERE operation_id IN (
  SELECT id FROM operation WHERE subject = "sourcing_required" AND name = "report"
);
