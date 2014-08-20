SELECT "Removing defunct operations" AS "";

DELETE FROM operation WHERE subject = "call_attempts";
