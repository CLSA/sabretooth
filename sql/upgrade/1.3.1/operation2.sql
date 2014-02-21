SELECT "Removing defunct operations" AS "";
DELETE FROM operation WHERE subject = "interview" AND name = "rescore";
