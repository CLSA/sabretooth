DROP PROCEDURE IF EXISTS patch_participant;
DELIMITER //
CREATE PROCEDURE patch_participant()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = REPLACE( DATABASE(), 'sabretooth', 'cenozo' );

    SELECT "Adding new participant.override_quota column" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "participant"
      AND COLUMN_NAME = "override_quota" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".participant ",
        "ADD COLUMN override_quota TINYINT(1) NOT NULL DEFAULT 0 ",
        "AFTER use_informant" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;
  END //
DELIMITER ;

CALL patch_participant();
DROP PROCEDURE IF EXISTS patch_participant;
