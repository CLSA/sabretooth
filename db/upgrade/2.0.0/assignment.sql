DROP PROCEDURE IF EXISTS patch_assignment;
DELIMITER //
CREATE PROCEDURE patch_assignment()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Adding role_id column to assignment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "assignment"
      AND COLUMN_NAME = "role_id" );
    IF @test = 0 THEN
      ALTER TABLE assignment ADD COLUMN role_id INT UNSIGNED NOT NULL AFTER user_id;

      SET @sql = CONCAT(
        "UPDATE assignment ",
        "SET role_id = ( SELECT id FROM ", @cenozo, ".role WHERE name = 'operator' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "ALTER TABLE assignment ",
        "ADD INDEX fk_role_id (role_id ASC), ",
        "ADD CONSTRAINT fk_assignment_role_id ",
            "FOREIGN KEY (role_id) ",
            "REFERENCES ", @cenozo, ".role (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

CALL patch_assignment();
DROP PROCEDURE IF EXISTS patch_assignment;


SELECT "Adding new triggers to assignment table" AS "";

DELIMITER $$

DROP TRIGGER IF EXISTS assignment_AFTER_INSERT $$
CREATE DEFINER = CURRENT_USER TRIGGER assignment_AFTER_INSERT AFTER INSERT ON assignment FOR EACH ROW
BEGIN
  CALL update_interview_last_assignment( NEW.interview_id );
  CALL update_assignment_last_phone_call( NEW.id );
END;$$


DROP TRIGGER IF EXISTS assignment_AFTER_UPDATE $$
CREATE DEFINER = CURRENT_USER TRIGGER assignment_AFTER_UPDATE AFTER UPDATE ON assignment FOR EACH ROW
BEGIN
  CALL update_interview_last_assignment( NEW.interview_id );
  CALL update_assignment_last_phone_call( NEW.id );
END;$$


DROP TRIGGER IF EXISTS assignment_AFTER_DELETE $$
CREATE DEFINER = CURRENT_USER TRIGGER assignment_AFTER_DELETE AFTER DELETE ON assignment FOR EACH ROW
BEGIN
  CALL update_interview_last_assignment( OLD.interview_id );
END;$$

DELIMITER ;
