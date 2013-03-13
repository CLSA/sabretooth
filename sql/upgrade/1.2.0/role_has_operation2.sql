-- only remove role_has_operation entries if the database hasn't yet been converted
DROP PROCEDURE IF EXISTS patch_role_has_operation2;
DELIMITER //
CREATE PROCEDURE patch_role_has_operation2()
  BEGIN
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "user" );
    IF @test = 1 THEN

      -- remove all "primary" operations
      DELETE FROM role_has_operation WHERE operation_id IN (
        SELECT id FROM operation
        WHERE name = "primary" );

      -- remove typist access to consent new push operation
      DELETE FROM role_has_operation
      WHERE role_id = (
        SELECT id FROM role
        WHERE name = "typist" )
      AND operation_id = (
        SELECT id FROM operation
        WHERE type = "push"
        AND subject = "consent"
        AND name = "new" );

      -- remove consent outstanding report
      DELETE FROM role_has_operation WHERE operation_id IN (
        SELECT id FROM operation
        WHERE subject = "consent_outstanding" );

      -- remove operation list
      DELETE FROM role_has_operation WHERE operation_id IN (
        SELECT id FROM operation
        WHERE subject = "operation"
        AND name = "list" );

      -- remove participant_sync operations
      DELETE FROM role_has_operation WHERE operation_id IN (
        SELECT id FROM operation
        WHERE subject = "participant"
        AND name = "sync" );

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_role_has_operation2();
DROP PROCEDURE IF EXISTS patch_role_has_operation2;
