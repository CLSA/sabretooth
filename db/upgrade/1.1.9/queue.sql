-- reorder the queue if "sourcing required" is still the 5th queue
DROP PROCEDURE IF EXISTS patch_queue1;
DELIMITER //
CREATE PROCEDURE patch_queue1()
  BEGIN
    SET @test = (
      SELECT id
      FROM queue
      WHERE name = "sourcing required" );
    IF @test = 5 THEN
      UPDATE queue SET id = 0 WHERE id = 5;
      UPDATE queue SET id = 5 WHERE id = 6;
      UPDATE queue SET id = 6 WHERE id = 7;
      UPDATE queue SET id = 7 WHERE id = 8;
      UPDATE queue SET id = 8 WHERE id = 9;
      UPDATE queue SET id = 9 WHERE id = 10;
      UPDATE queue SET id = 10 WHERE id = 11;
      UPDATE queue SET id = 11 WHERE id = 12;
      UPDATE queue SET id = 12 WHERE id = 13;
      UPDATE queue SET id = 13 WHERE id = 14;
      UPDATE queue SET id = 14 WHERE id = 15;
      UPDATE queue SET id = 15 WHERE id = 16;
      UPDATE queue SET id = 16 WHERE id = 17;
      UPDATE queue SET id = 17 WHERE id = 0;
    END IF;
  END //
DELIMITER ;

-- proceedure used by patch_queue2
DROP PROCEDURE IF EXISTS increment_queue_ids;
DELIMITER //
CREATE PROCEDURE increment_queue_ids( min_id INT, max_id INT )
  BEGIN
    SET @current_id = max_id;
    WHILE @current_id >= min_id DO
      UPDATE queue SET id = ( @current_id + 1 ) WHERE id = @current_id;
      UPDATE queue SET parent_queue_id = ( @current_id + 1 ) WHERE parent_queue_id = @current_id;
      UPDATE assignment SET queue_id = ( @current_id + 1 ) WHERE queue_id = @current_id;
      SET @current_id = @current_id - 1; 
    END WHILE;
  END //
DELIMITER ;

-- add the new "unreachable" queue
DROP PROCEDURE IF EXISTS patch_queue2;
DELIMITER //
CREATE PROCEDURE patch_queue2()
  BEGIN
    SET @test = (
      SELECT COUNT(*)
      FROM queue
      WHERE name = "unreachable" );
    IF @test = 0 THEN
      -- we need to change primary keys so disable checks
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      -- move all queue ids from 18 to 65 up by one
      SET @min_id := 18;
      SET @max_id := ( SELECT MAX( id ) max_id FROM queue );
      CALL increment_queue_ids( @min_id, @max_id );

      -- insert the new unreachable status queue
      INSERT INTO queue SET
      id = @min_id,
      name = "unreachable",
      title = "Participants who are unreachable",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are unreachable even after sourcing attempts.";

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue1();
CALL patch_queue2();
DROP PROCEDURE IF EXISTS patch_queue1;
DROP PROCEDURE IF EXISTS patch_queue2;
DROP PROCEDURE IF EXISTS increment_queue_ids;

-- update the sourcing required queue's title and description
UPDATE queue
SET title = "Participants who require sourcing",
    description = "Participants who are not eligible for answering questionnaires because they have no valid phone number to call."
WHERE name = "sourcing required";
