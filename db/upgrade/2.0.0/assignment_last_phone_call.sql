DROP PROCEDURE IF EXISTS patch_assignment_last_phone_call;
DELIMITER //
CREATE PROCEDURE patch_assignment_last_phone_call()
  BEGIN

    SELECT "Adding new assignment_last_phone_call caching table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "assignment_last_phone_call" );
    IF @test = 0 THEN

      DROP VIEW IF EXISTS assignment_last_phone_call;

      CREATE TABLE IF NOT EXISTS assignment_last_phone_call (
        assignment_id INT UNSIGNED NOT NULL,
        phone_call_id INT UNSIGNED NULL,
        update_timestamp TIMESTAMP NOT NULL,
        create_timestamp TIMESTAMP NOT NULL,
        PRIMARY KEY (assignment_id),
        INDEX fk_phone_call_id (phone_call_id ASC),
        CONSTRAINT fk_assignment_last_phone_call_assignment_id
          FOREIGN KEY (assignment_id)
          REFERENCES assignment (id)
          ON DELETE CASCADE
          ON UPDATE CASCADE,
        CONSTRAINT fk_assignment_last_phone_call_phone_call_id
          FOREIGN KEY (phone_call_id)
          REFERENCES phone_call (id)
          ON DELETE SET NULL
          ON UPDATE NO ACTION)
      ENGINE = InnoDB;

      SELECT "Populating assignment_last_phone_call table" AS "";

      REPLACE INTO assignment_last_phone_call( assignment_id, phone_call_id )
      SELECT assignment.id, phone_call.id
      FROM assignment
      LEFT JOIN phone_call ON assignment.id = phone_call.assignment_id
      AND phone_call.start_datetime <=> (
        SELECT MAX( start_datetime )
        FROM phone_call
        WHERE assignment.id = phone_call.assignment_id
        GROUP BY phone_call.assignment_id
        LIMIT 1
      );

    END IF;

  END //
DELIMITER ;

CALL patch_assignment_last_phone_call();
DROP PROCEDURE IF EXISTS patch_assignment_last_phone_call;
