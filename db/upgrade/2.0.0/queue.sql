-- proceedure used by patch_queue
DROP PROCEDURE IF EXISTS set_queue_id;
DELIMITER //
CREATE PROCEDURE set_queue_id( old_id INT, new_id INT )
  BEGIN
    UPDATE queue SET id = new_id WHERE id = old_id;
    UPDATE queue SET parent_queue_id = new_id WHERE parent_queue_id = old_id;
    UPDATE assignment SET queue_id = new_id WHERE queue_id = old_id;
    UPDATE queue_state SET queue_id = new_id WHERE queue_id = old_id;
  END //
DELIMITER ;

DROP PROCEDURE IF EXISTS patch_queue;
DELIMITER //
CREATE PROCEDURE patch_queue()
  BEGIN
    SELECT "Removing IVR queue" AS "";

    SET @test = ( SELECT COUNT(*) FROM queue WHERE name = "ivr_appointment" );
    IF @test = 1 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      -- remove any reference to ivr appointments
      UPDATE assignment SET queue_id = ( SELECT id FROM queue WHERE name = "appointment" )
      WHERE queue_id = ( SELECT id FROM queue WHERE name = "ivr_appointment" );
      DELETE FROM queue_state WHERE queue_id = ( SELECT id FROM queue WHERE name = "ivr_appointment" );

      -- remove the ivr queue
      DELETE FROM queue WHERE name = "ivr_appointment";

      -- decrement all queue ids by 1 from the appointment queue onward
      SET @id = ( SELECT id FROM queue WHERE name = "appointment" );
      SET @max_id = ( SELECT MAX( id ) FROM queue );
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;
            
      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue();
DROP PROCEDURE IF EXISTS patch_queue;
DROP PROCEDURE IF EXISTS set_queue_id;

SELECT "Removing extraneous whitespace in queue descriptions" AS "";

UPDATE queue SET description = REPLACE( description, "\n      ", " " );
