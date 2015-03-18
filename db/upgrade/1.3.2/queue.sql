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

-- remove "restricted" and "not available" queues
DROP PROCEDURE IF EXISTS patch_queue;
DELIMITER //
CREATE PROCEDURE patch_queue()
  BEGIN
    SELECT "Adding new IVR queue" AS "";

    SET @test = ( SELECT COUNT(*) FROM queue WHERE name = "ivr_appointment" );
    IF @test = 0 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
      SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';

      -- increment all queue ids by 1 from the appointment queue onward
      SET @id = ( SELECT MAX( id ) FROM queue );
      SET @min_id = ( SELECT id FROM queue WHERE name = "appointment" );
      WHILE @id >= @min_id DO
        CALL set_queue_id( @id, @id + 1 );
        SET @id = @id - 1;
      END WHILE;
            
      -- now add in the IVR queue
      INSERT INTO queue SET
      id = @min_id,
      name = "ivr_appointment",
      title = "Participants scheduled for an IVR-based interview",
      rank = NULL,
      qnaire_specific = true,
      time_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "qnaire" ) AS tmp ),
      description = "Participants who are scheduled for an IVR-based interview.";
            
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue();
DROP PROCEDURE IF EXISTS patch_queue;
DROP PROCEDURE IF EXISTS set_queue_id;
