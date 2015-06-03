DROP PROCEDURE IF EXISTS patch_interview_last_assignment;
DELIMITER //
CREATE PROCEDURE patch_interview_last_assignment()
  BEGIN

    SELECT "Adding new interview_last_assignment caching table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "interview_last_assignment" );
    IF @test = 0 THEN

      DROP VIEW IF EXISTS interview_last_assignment;

      CREATE TABLE IF NOT EXISTS interview_last_assignment (
        interview_id INT UNSIGNED NOT NULL,
        assignment_id INT UNSIGNED NULL,
        update_timestamp TIMESTAMP NOT NULL,
        create_timestamp TIMESTAMP NOT NULL,
        PRIMARY KEY (interview_id),
        INDEX fk_assignment_id (assignment_id ASC),
        CONSTRAINT fk_interview_last_assignment_interview_id
          FOREIGN KEY (interview_id)
          REFERENCES interview (id)
          ON DELETE CASCADE
          ON UPDATE CASCADE,
        CONSTRAINT fk_interview_last_assignment_assignment_id
          FOREIGN KEY (assignment_id)
          REFERENCES assignment (id)
          ON DELETE SET NULL
          ON UPDATE NO ACTION)
      ENGINE = InnoDB;

      SELECT "Populating interview_last_assignment table" AS "";

      REPLACE INTO interview_last_assignment( interview_id, assignment_id )
      SELECT interview.id, assignment.id
      FROM interview
      LEFT JOIN assignment ON interview.id = assignment.interview_id
      AND assignment.start_datetime <=> (
        SELECT MAX( start_datetime )
        FROM assignment
        WHERE interview.id = assignment.interview_id
        GROUP BY assignment.interview_id
        LIMIT 1
      );

    END IF;

  END //
DELIMITER ;

CALL patch_interview_last_assignment();
DROP PROCEDURE IF EXISTS patch_interview_last_assignment;
