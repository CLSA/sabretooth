SELECT "Removing write access to report_restriction services" AS "";

DELETE FROM service WHERE subject = "report_restriction" and method != "GET";
