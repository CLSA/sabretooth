-- proceedure used by patch_queue
DROP PROCEDURE IF EXISTS set_queue_id;
DELIMITER //
CREATE PROCEDURE set_queue_id( old_id INT, new_id INT )
  BEGIN
    UPDATE queue SET id = new_id WHERE id = old_id;
    UPDATE queue SET parent_queue_id = new_id WHERE parent_queue_id = old_id;
    UPDATE assignment SET queue_id = new_id WHERE queue_id = old_id;
  END //
DELIMITER ;

DROP PROCEDURE IF EXISTS patch_queue;
DELIMITER //
CREATE PROCEDURE patch_queue()
  BEGIN
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "queue"
      AND COLUMN_NAME = "qnaire_specific" );
    IF @test = 1 THEN
      ALTER TABLE queue DROP COLUMN qnaire_specific;
    END IF;

    SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
    SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

    SELECT "Removing no-active-address queue" AS "";

    SET @test = ( SELECT COUNT(*) FROM queue WHERE name = "no active address" );
    IF @test = 1 THEN
      -- remove the no-active-adress queue
      DELETE FROM queue WHERE name = "no active address";

      -- decrement all queue ids by 1 from the no-site queue onward
      SET @id = ( SELECT id FROM queue WHERE name = "no site" );
      SET @max_id = ( SELECT MAX( id ) FROM queue );
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;
    END IF;

    SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
    SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue();
DROP PROCEDURE IF EXISTS patch_queue;
DROP PROCEDURE IF EXISTS set_queue_id;


SELECT "Updating queue to reflect changes in state/hold system" AS "";

UPDATE queue
SET description = "Participants who are not eligible to answer questionnaires."
WHERE name = "ineligible";

UPDATE queue
SET name = "not enrolled",
    title = "Not enrolled participants",
    description = "Participants who cannot be enrolled in the study."
WHERE name = "inactive";

UPDATE queue
SET name = "final hold",
    title = "Participants who are in a final hold",
    description = "Participants who will likely never be called again."
WHERE name = "refused consent";

UPDATE queue
SET name = "temporary hold",
    title = "Participants who are in a temporary hold",
    description = "Participants who cannot currently be called but may become available in the future."
WHERE name = "condition";

UPDATE queue
SET name = "tracing",
    title = "Participants who require tracing",
    description = "Participants who are uncontactable because of missing or invalid contact information."
WHERE name = "no address";

UPDATE queue
SET name = "proxy",
    title = "Participants who require a proxy",
    description = "Participants who cannot currently be called because they may require a proxy."
WHERE name = "no phone";

-- fix invalid queue description
UPDATE queue
SET description = "Participants who are ready to answer an questionnaire which has been disabled."
WHERE name = "qnaire disabled";
