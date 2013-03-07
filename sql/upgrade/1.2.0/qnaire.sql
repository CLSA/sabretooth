-- add the new event_type_id foreign key to the qnaire table
-- we need to create a procedure which only alters the qnaire table if the
-- the event_type_id column is missing
DROP PROCEDURE IF EXISTS patch_qnaire;
DELIMITER //
CREATE PROCEDURE patch_qnaire()
  BEGIN
    DECLARE test INT;
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "event_type_id" );
    IF @test = 0 THEN
      -- determine the @cenozo database name
      SET @cenozo = CONCAT( SUBSTRING( DATABASE(), 1, LOCATE( 'sabretooth', DATABASE() ) - 1 ),
                            'cenozo' );

      ALTER TABLE qnaire
      ADD COLUMN event_type_id INT UNSIGNED NULL DEFAULT NULL
      COMMENT 'The event type which must be present before the qnaire begins.'
      AFTER prev_qnaire_id;
      ALTER TABLE qnaire
      ADD INDEX fk_event_type_id ( event_type_id ASC );

      SET @sql = CONCAT(
        "ADD CONSTRAINT fk_qnaire_event_type_id ",
        "FOREIGN KEY ( event_type_id ) ",
        "REFERENCES ", @cenozo, ".event_type( id ) ",
        "ON DELETE NO ACTION ",
        "ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_qnaire();
DROP PROCEDURE IF EXISTS patch_qnaire;
