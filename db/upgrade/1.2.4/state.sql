DROP PROCEDURE IF EXISTS patch_state;
DELIMITER //
CREATE PROCEDURE patch_state()
  BEGIN
    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Adding new state table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.tables
      WHERE table_schema = @cenozo
      AND table_name = "state" );

    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS ", @cenozo, ".state ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "name VARCHAR(45) NOT NULL, ",
          "rank INT NOT NULL, ",
          "description VARCHAR(512) NOT NULL, ",
          "PRIMARY KEY (id), ",
          "UNIQUE INDEX uq_name (name ASC), ",
          "UNIQUE INDEX uq_rank (rank ASC)) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO ", @cenozo, ".state( name, rank, description ) VALUES ",
        "( 'deceased', 1, 'The participant is deceased.' ), ",
        "( 'deaf', 2, 'The participant is deaf.' ), ",
        "( 'mentally unfit', 3, 'The participant is mentally unfit.' ), ",
        "( 'language barrier', 4, 'The participant does not adequately speak one of the study\\'s languages.' ), ",
        "( 'age range', 5, 'The participant falls outside of the age range criteria.' ), ",
        "( 'not canadian', 6, 'The participant is not Canadian.' ), ",
        "( 'federal reserve', 7, 'The participant\\'s residence is on a federal reserve.' ), ",
        "( 'armed forces', 8, 'The participant is a member of the armed forces or is a veteran.' ), ",
        "( 'institutionalized', 9, 'The participant is institutionalized.' ), ",
        "( 'noncompliant', 10, 'The participant is unable to comply with the study\\'s policies.' ), ",
        "( 'sourcing required', 11, 'Unable to reach the participant, further sourcing is required.' ), ",
        "( 'unreachable', 12, 'Unable to reach the participant even after sourcing.' ), ",
        "( 'consent unavailable', 13, 'Unable to receive written consent from the participant.' ), ",
        "( 'duplicate', 14, 'The participant already exists under a different record.' ), ",
        "( 'other', 15, 'Other state.' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_state();
DROP PROCEDURE IF EXISTS patch_state;
