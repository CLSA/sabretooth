DROP PROCEDURE IF EXISTS patch_report_type;
  DELIMITER //
  CREATE PROCEDURE patch_report_type()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    -- determine this application's name
    SET @application = (
      SELECT REPLACE( DATABASE(),
                      CONCAT( SUBSTRING( USER(), 1, LOCATE( '@', USER() ) - 1 ), "_" ),
                      "" ) );

    SELECT "Adding records to report_type table" AS "";

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".report_type ( name, title, subject, description ) VALUES ",
      "( 'call_history', 'Call History', 'phone_call', ",
        "'This report chronologically lists call attempts.' ), ",
      "( 'productivity', 'Productivity', 'interview', ",
        "'Lists operator interviewing productivity.' ), ",
      "( 'sample', 'Sample', 'participant', ",
        "'This report contains details used to help manage participant sample.' )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_report_type();
DROP PROCEDURE IF EXISTS patch_report_type;
