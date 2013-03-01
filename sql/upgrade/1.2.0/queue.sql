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
    SET @test = ( SELECT COUNT(*) FROM queue );
    IF @test = 66 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
      SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';
      
      -- remove the queues and reparent the children
      DELETE FROM queue WHERE name IN ( "not assigned", "no appointment" );
      UPDATE queue SET parent_queue_id = 20 WHERE parent_queue_id IN ( 24, 32 );

      -- to avoid conflics we temporarily add 100000 to the queue id
      CALL set_queue_id( 22, 100021 );
      CALL set_queue_id( 23, 100022 );
      CALL set_queue_id( 25, 100023 );
      CALL set_queue_id( 26, 100024 );
      CALL set_queue_id( 27, 100025 );
      CALL set_queue_id( 28, 100026 );
      CALL set_queue_id( 21, 100027 );
      CALL set_queue_id( 33, 100028 );
      CALL set_queue_id( 34, 100032 );
      CALL set_queue_id( 35, 100033 );
      CALL set_queue_id( 36, 100034 );
      CALL set_queue_id( 37, 100035 );
      CALL set_queue_id( 38, 100036 );
      CALL set_queue_id( 39, 100037 );
      CALL set_queue_id( 40, 100038 );
      CALL set_queue_id( 41, 100039 );
      CALL set_queue_id( 42, 100040 );
      CALL set_queue_id( 43, 100041 );
      CALL set_queue_id( 44, 100042 );
      CALL set_queue_id( 45, 100043 );
      CALL set_queue_id( 46, 100044 );
      CALL set_queue_id( 47, 100045 );
      CALL set_queue_id( 48, 100046 );
      CALL set_queue_id( 49, 100047 );
      CALL set_queue_id( 50, 100048 );
      CALL set_queue_id( 51, 100049 );
      CALL set_queue_id( 52, 100050 );
      CALL set_queue_id( 53, 100051 );
      CALL set_queue_id( 54, 100052 );
      CALL set_queue_id( 55, 100053 );
      CALL set_queue_id( 56, 100054 );
      CALL set_queue_id( 57, 100055 );
      CALL set_queue_id( 58, 100056 );
      CALL set_queue_id( 59, 100057 );
      CALL set_queue_id( 60, 100058 );
      CALL set_queue_id( 61, 100059 );
      CALL set_queue_id( 62, 100060 );
      CALL set_queue_id( 63, 100061 );
      CALL set_queue_id( 64, 100062 );
      CALL set_queue_id( 65, 100063 );
      CALL set_queue_id( 66, 100064 );

      -- subtract 100000 from the queue ids changed above
      UPDATE queue SET id = id - 100000 WHERE id > 100000;
      UPDATE queue SET parent_queue_id = parent_queue_id - 100000 WHERE parent_queue_id > 100000;
      UPDATE assignment SET queue_id = queue_id - 100000 WHERE queue_id > 100000;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue();
DROP PROCEDURE IF EXISTS patch_queue;
DROP PROCEDURE IF EXISTS set_queue_id;
