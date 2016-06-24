DROP PROCEDURE IF EXISTS patch_report_restriction;
  DELIMITER //
  CREATE PROCEDURE patch_report_restriction()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Adding records to report_restriction table" AS "";

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".report_restriction ( ",
        "report_type_id, rank, name, title, mandatory, restriction_type, custom, ",
        "subject, operator, enum_list, description ) ",
      "SELECT report_type.id, rank, restriction.name, restriction.title, mandatory, type, custom, ",
             "restriction.subject, operator, enum_list, restriction.description ",
      "FROM ", @cenozo, ".report_type, ( ",
        "SELECT ",
          "1 AS rank, ",
          "'collection' AS name, ",
          "'Collection' AS title, ",
          "0 AS mandatory, ",
          "'table' AS type, ",
          "0 AS custom, ",
          "'collection' AS subject, ",
          "NULL AS operator, ",
          "NULL AS enum_list, ",
          "'Restrict to a particular collection.' AS description ",
        "UNION SELECT ",
          "2 AS rank, ",
          "'qnaire' AS name, ",
          "'Questionnaire' AS title, ",
          "0 AS mandatory, ",
          "'table' AS type, ",
          "0 AS custom, ",
          "'qnaire' AS subject, ",
          "NULL AS operator, ",
          "NULL AS enum_list, ",
          "'Restrict to calls from a particular questionnaire.' AS description ",
        "UNION SELECT ",
          "3 AS rank, ",
          "'complete' AS name, ",
          "'Completed Interviews' AS title, ",
          "0 AS mandatory, ",
          "'boolean' AS type, ",
          "0 AS custom, ",
          "'interview.end_datetime IS NOT NULL' AS subject, ",
          "NULL AS operator, ",
          "NULL AS enum_list, ",
          "'Restrict to calls from complete or incomplete interviews.' AS description ",
        "UNION SELECT ",
          "4 AS rank, ",
          "'site' AS name, ",
          "'Site' AS title, ",
          "0 AS mandatory, ",
          "'table' AS type, ",
          "0 AS custom, ",
          "'site' AS subject, ",
          "NULL AS operator, ",
          "NULL AS enum_list, ",
          "'Restrict to a particular site.' AS description ",
        "UNION SELECT ",
          "5 AS rank, ",
          "'last_call' AS name, ",
          "'Last Call Only' AS title, ",
          "1 AS mandatory, ",
          "'boolean' AS type, ",
          "1 AS custom, ",
          "NULL AS subject, ",
          "NULL AS operator, ",
          "NULL AS enum_list, ",
          "'Only include the last call made to the participant.' AS description ",
        "UNION SELECT ",
          "6 AS rank, ",
          "'start_date' AS name, ",
          "'Start Date' AS title, ",
          "0 AS mandatory, ",
          "'date' AS type, ",
          "0 AS custom, ",
          "'DATE( phone_call.start_datetime )' AS subject, ",
          "'>=' AS operator, ",
          "NULL AS enum_list, ",
          "'Phone calls made on or after the given date.' AS description ",
        "UNION SELECT ",
          "7 AS rank, ",
          "'end_date' AS name, ",
          "'End Date' AS title, ",
          "0 AS mandatory, ",
          "'date' AS type, ",
          "0 AS custom, ",
          "'DATE( phone_call.start_datetime )' AS subject, ",
          "'<=' AS operator, ",
          "NULL AS enum_list, ",
          "'Phone calls made on or before the given date.' AS description ",
      ") AS restriction ",
      "WHERE report_type.name = 'call_history'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_report_restriction();
DROP PROCEDURE IF EXISTS patch_report_restriction;