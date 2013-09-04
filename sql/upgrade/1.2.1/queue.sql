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

-- remove "not assigned" and "no appointment" queues
-- rearrange "restricted" and "quota disabled" queues
DROP PROCEDURE IF EXISTS patch_queue;
DELIMITER //
CREATE PROCEDURE patch_queue()
  BEGIN
    -- add the "duplicate" queue
    SELECT "Adding 'duplicate' entry to queue" AS "";

    SET @test = ( SELECT COUNT(*) FROM queue WHERE name = "duplicate" );
    IF @test = 0 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
      SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';

      -- increment all queue ids by 1 for id >= 19
      SET @id = ( SELECT MAX( id ) FROM queue );
      REPEAT
        CALL set_queue_id( @id, @id + 1 );
        SET @id = @id - 1;
      UNTIL @id < 19 END REPEAT;
            
      -- now add the new "duplicate" queue with id = 19
      INSERT INTO queue SET
      id = 19,
      name = "duplicate",
      title = "Duplicate participants",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are a duplicate of another participant record.";

    END IF;

    -- add the "consent unavailable" queue
    SELECT "Adding 'consent unavailable' entry to queue" AS "";

    SET @test = ( SELECT COUNT(*) FROM queue WHERE name = "consent unavailable" );
    IF @test = 0 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
      SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';

      -- increment all queue ids by 1 for id >= 19
      SET @id = ( SELECT MAX( id ) FROM queue );
      REPEAT
        CALL set_queue_id( @id, @id + 1 );
        SET @id = @id - 1;
      UNTIL @id < 19 END REPEAT;
            
      -- now add the new "consent unavailable" queue with id = 19
      INSERT INTO queue SET
      id = 19,
      name = "consent unavailable",
      title = "Cannot obtain written consent",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because we are
      unable to obtain their written consent.";

    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue();
DROP PROCEDURE IF EXISTS patch_queue;
DROP PROCEDURE IF EXISTS set_queue_id;
