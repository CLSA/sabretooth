DROP PROCEDURE IF EXISTS patch_activity;
DELIMITER //
CREATE PROCEDURE patch_activity()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Replacing operation_id with service_id in activity table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "activity"
      AND COLUMN_NAME = "service_id" );
    IF @test = 0 THEN
      -- add new service_id column
      ALTER TABLE activity
      ADD COLUMN service_id,
      ADD KEY fk_service_id (service_id ASC);

      SET @sql = CONCAT(
        "ALTER TABLE activity ",
        "ADD CONSTRAINT fk_activity_service_id ",
        "FOREIGN KEY (service_id) REFERENCES ", @cenozo, ".service (id) ",
        "ON DELETE NO ACTION ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- drop old operation_id column
      ALTER TABLE activity
      DROP FOREIGN KEY fk_activity_operation_id,
      DROP KEY fk_operation_id,
      DROP COLUMN operation_id;
    END IF;

  END //
DELIMITER ;

CALL patch_activity();
DROP PROCEDURE IF EXISTS patch_activity;
