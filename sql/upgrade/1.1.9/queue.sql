-- reorder the queue if "sourcing required" is still the 5th queue
DROP PROCEDURE IF EXISTS patch_queue;
DELIMITER //
CREATE PROCEDURE patch_queue()
  BEGIN
    DECLARE test INT;
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

-- now call the procedure and remove the procedure
CALL patch_queue();
DROP PROCEDURE IF EXISTS patch_queue;

-- update the sourcing required queue's title and description
UPDATE queue
SET title = "Participants who require sourcing",
    description = "Participants who are not eligible for answering questionnaires because they have no valid phone number to call."
WHERE name = "sourcing required";
