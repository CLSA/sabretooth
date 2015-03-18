DROP PROCEDURE IF EXISTS patch_participant;
DELIMITER //
CREATE PROCEDURE patch_participant()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    -- add the 'duplicate' option to the participant.status enum column
    SELECT "Adding new values to participant.status enum column" AS "";
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "participant"
      AND COLUMN_NAME = "status"
      AND COLUMN_TYPE NOT LIKE "%consent unavailable%" );
    IF @test = 1 THEN
      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".participant ",
        "MODIFY COLUMN status ENUM('deceased','deaf','mentally unfit','language barrier',",
          "'age range','not canadian','federal reserve','armed forces','institutionalized',",
          "'noncompliant','sourcing required','unreachable','consent unavailable','duplicate',",
          "'other') NULL DEFAULT NULL" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

    SELECT "Adding new participant.withdraw_letter and participant.grouping columns" AS "";
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "participant"
      AND COLUMN_NAME = "withdraw_letter" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".participant ",
        "ADD COLUMN withdraw_letter CHAR(1) NULL DEFAULT NULL" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "participant"
      AND COLUMN_NAME = "grouping" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".participant ",
        "ADD COLUMN grouping VARCHAR(45) NULL DEFAULT NULL ",
        "AFTER cohort_id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- fill in the grouping based on cohort
      SET @sql = CONCAT(
        "UPDATE ", @cenozo, ".participant ",
        "JOIN ", @cenozo, ".cohort ON participant.cohort_id = cohort.id ",
        "SET grouping = UPPER( SUBSTR( cohort.name, 1, 1 ) ) " );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

CALL patch_participant();
DROP PROCEDURE IF EXISTS patch_participant;
