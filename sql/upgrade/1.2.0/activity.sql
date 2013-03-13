-- only patch the activity table if the database hasn't yet been converted
DROP PROCEDURE IF EXISTS patch_activity;
DELIMITER //
CREATE PROCEDURE patch_activity()
  BEGIN
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "user" );
    IF @test = 1 THEN

      -- censor passwords
      UPDATE activity SET query = "(censored)"
      WHERE operation_id IN (
        SELECT id FROM operation
        WHERE name = "set_password"
        OR ( subject = "opal_instance" AND name = "new" ) )
      AND query != "(censored)";

      -- remove consent outstanding report
      DELETE FROM activity WHERE operation_id IN (
        SELECT id FROM operation
        WHERE subject = "consent_outstanding" );

      -- remove operation list
      DELETE FROM activity WHERE operation_id IN (
        SELECT id FROM operation
        WHERE subject = "operation"
        AND name = "list" );

      -- remove participant_sync activity
      DELETE FROM activity WHERE operation_id IN (
        SELECT id FROM operation
        WHERE subject = "participant"
        AND name = "sync" );

      -- remove all "primary" operations
      DELETE FROM activity WHERE operation_id IN (
        SELECT id FROM operation
        WHERE name = "primary" );

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_activity();
DROP PROCEDURE IF EXISTS patch_activity;
