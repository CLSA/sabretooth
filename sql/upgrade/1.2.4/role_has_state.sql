DROP PROCEDURE IF EXISTS patch_state;
DELIMITER //
CREATE PROCEDURE patch_state()
  BEGIN
    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Adding new role_has_state tables" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.tables
      WHERE table_schema = @cenozo
      AND table_name = "role_has_state" );

    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS ", @cenozo, ".role_has_state ( ",
          "role_id INT UNSIGNED NOT NULL, ",
          "state_id INT UNSIGNED NOT NULL, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "PRIMARY KEY (role_id, state_id), ",
          "INDEX fk_state_id (state_id ASC), ",
          "INDEX fk_role_id (role_id ASC), ",
          "CONSTRAINT fk_role_has_state_role_id ",
            "FOREIGN KEY (role_id) ",
            "REFERENCES ", @cenozo, ".role (id) ",
            "ON DELETE CASCADE ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_role_has_state_state_id ",
            "FOREIGN KEY (state_id) ",
            "REFERENCES ", @cenozo, ".state (id) ",
            "ON DELETE CASCADE ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".role_has_state( role_id, state_id ) ",
        "SELECT role.id, state.id ",
        "FROM ", @cenozo, ".role, ", @cenozo, ".state ",
        "WHERE state.name NOT IN( 'unreachable', 'consent unavailable' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".role_has_state( role_id, state_id ) ",
        "SELECT role.id, state.id ",
        "FROM ", @cenozo, ".role, ", @cenozo, ".state ",
        "WHERE state.name = 'unreachable' ",
        "AND role.name IN ( 'administrator', 'curator', 'supervisor' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".role_has_state( role_id, state_id ) ",
        "SELECT role.id, state.id ",
        "FROM ", @cenozo, ".role, ", @cenozo, ".state ",
        "WHERE state.name = 'consent unavailable' ",
        "AND role.name IN ( 'administrator', 'curator' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_state();
DROP PROCEDURE IF EXISTS patch_state;
