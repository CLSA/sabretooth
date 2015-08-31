DROP PROCEDURE IF EXISTS patch_phase;
  DELIMITER //
  CREATE PROCEDURE patch_phase()
  BEGIN

    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_queue_state_site_id" );

    SELECT "Removing phase table (moved to cenozo database)" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "phase" );
    IF @test = 1 THEN
      ALTER TABLE phase
      ADD COLUMN script_id INT UNSIGNED NOT NULL
      AFTER create_timestamp;

      ALTER TABLE phase ADD INDEX fk_script_id( script_id ASC );

      -- now transfer qnaire_id to script_id
      UPDATE phase JOIN qnaire ON phase.qnaire_id = qnaire.id
      SET phase.script_id = qnaire.script_id;

      SET @sql = CONCAT(
        "ALTER TABLE phase ",
        "ADD CONSTRAINT fk_phase_script_id ",
        "FOREIGN KEY( script_id ) REFERENCES ", @cenozo, ".script( id ) ",
        "ON DELETE CASCADE ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- and finally, remove the constraint to the qnaire table
      ALTER TABLE phase
      DROP FOREIGN KEY fk_phase_qnaire_id,
      DROP INDEX fk_qnaire_id,
      DROP INDEX uq_qnaire_id_rank;

      ALTER TABLE phase DROP COLUMN qnaire_id;

      -- and move the sid column after the rank column and add the script-rank unique key
      ALTER TABLE phase
      MODIFY COLUMN sid INT NOT NULL COMMENT 'The limesurvey SID to use by this phase.' AFTER rank,
      ADD UNIQUE KEY uq_script_id_rank (script_id,rank);

      -- now move phases to cenozo's phase table
      SET @sql = CONCAT(
        "INSERT INTO ", @cenozo, ".phase( update_timestamp, create_timestamp, script_id, rank, sid, repeated ) ",
        "SELECT update_timestamp, create_timestamp, script_id, rank, sid, repeated from phase" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- and finally, delete sabretooth's copy of the phase table
      DROP TABLE phase;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_phase();
DROP PROCEDURE IF EXISTS patch_phase;
