-- add the new source_id column
-- we need to create a procedure which only alters the participant table if the source_id
-- column is missing
DROP PROCEDURE IF EXISTS patch_participant;
DELIMITER //
CREATE PROCEDURE patch_participant()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "participant"
      AND COLUMN_NAME = "source_id" );
     IF @test = 0 THEN
       ALTER TABLE participant
       ADD COLUMN source_id INT UNSIGNED NULL DEFAULT NULL
       AFTER uid;
       ALTER TABLE participant
       ADD INDEX fk_source_id (source_id ASC);
       ALTER TABLE participant
       ADD CONSTRAINT fk_participant_source_id
       FOREIGN KEY (source_id)
       REFERENCES source (id)
       ON DELETE NO ACTION
       ON UPDATE NO ACTION;
     END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_participant();
DROP PROCEDURE IF EXISTS patch_participant;
