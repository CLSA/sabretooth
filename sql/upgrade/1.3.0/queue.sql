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
    SELECT "Adding new time_specific column to queue table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "queue"
      AND COLUMN_NAME = "time_specific" );
    IF @test = 0 THEN
      ALTER TABLE queue
      ADD COLUMN time_specific TINYINT(1) NOT NULL
      AFTER qnaire_specific;
    END IF;

    SELECT "Removing defunct queues" AS "";

    SET @test = ( SELECT COUNT(*) FROM queue WHERE name = "restricted" );
    IF @test = 1 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
      SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';

      DELETE FROM queue
      WHERE name = "restricted"
      OR name = "new participant available"
      OR name LIKE "% not available";

      UPDATE queue SET rank = 1 WHERE name = "assignable appointment";
      UPDATE queue SET rank = 2 WHERE name = "missed appointment";
      UPDATE queue SET rank = 3 WHERE name = "assignable callback";
      UPDATE queue SET rank = 4 WHERE name = "contacted ready";
      UPDATE queue SET rank = 5 WHERE name = "busy ready";
      UPDATE queue SET rank = 6 WHERE name = "fax ready";
      UPDATE queue SET rank = 7 WHERE name = "no answer ready";
      UPDATE queue SET rank = 8 WHERE name = "not reached ready";
      UPDATE queue SET rank = 9 WHERE name = "hang up ready";
      UPDATE queue SET rank = 10 WHERE name = "soft refusal ready";
      UPDATE queue SET rank = 11 WHERE name = "new participant";

      UPDATE queue SET
        title = REPLACE( title, "available", "ready" ),
        name = REPLACE( name, "available", "ready" ),
        description = REPLACE( description, "Unavailable participants", "Participants" )
      WHERE name LIKE "% available";

      UPDATE queue SET time_specific = true
      WHERE name LIKE "upcoming %"
      OR name LIKE "assignable %"
      OR name LIKE "missed %"
      OR ( name LIKE ( "% waiting" ) AND name != "qnaire waiting" )
      OR name LIKE ( "% ready" );
            
      -- decrement all queue ids by 1 for id >= 49
      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = 49;
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;
            
      -- decrement all queue ids by 1 for id >= 45
      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = 45;
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;
            
      -- decrement all queue ids by 1 for id >= 41
      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = 41;
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;
            
      -- decrement all queue ids by 1 for id >= 37
      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = 37;
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;
            
      -- decrement all queue ids by 1 for id >= 33
      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = 33;
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;
            
      -- decrement all queue ids by 1 for id >= 29
      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = 29;
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;
            
      -- decrement all queue ids by 2 for id >= 24
      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = 24;
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 2 );
        SET @id = @id + 1;
      END WHILE;

      -- decrement all queue ids by 1 for id >= 16
      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = 16;
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;
            
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue();
DROP PROCEDURE IF EXISTS patch_queue;
DROP PROCEDURE IF EXISTS set_queue_id;
