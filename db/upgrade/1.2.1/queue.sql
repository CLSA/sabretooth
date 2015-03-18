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
    -- reducing all participant conditions to a single category
    SELECT "Reducing all participant conditions to a single category" AS "";

    SET @test = ( SELECT COUNT(*) FROM queue WHERE name = "condition" );
    IF @test = 0 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
      SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';

      -- change "deceased" queue to "condition"
      UPDATE queue SET
        name = "condition",
        title = "Permanent Condition",
        description = "Participants who are not eligible for answering questionnaires because they have a permanent condition."
      WHERE name = "deceased";

      -- delete all other condition-based queues
      DELETE FROM queue
      WHERE parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "ineligible" ) AS t )
      AND name NOT IN ( "inactive", "refused consent", "condition" );

      -- decrement all queue ids by 14 for id >= 21
      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = 21;
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 14 );
        SET @id = @id + 1;
      END WHILE;
            
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue();
DROP PROCEDURE IF EXISTS patch_queue;
DROP PROCEDURE IF EXISTS set_queue_id;
