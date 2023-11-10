DROP PROCEDURE IF EXISTS patch_role_has_custom_report;
DELIMITER //
CREATE PROCEDURE patch_role_has_custom_report()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = ( 
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Creating new role_has_custom_report table" AS "";

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS role_has_custom_report ( ",
        "role_id INT(10) UNSIGNED NOT NULL, ",
        "custom_report_id INT(10) UNSIGNED NOT NULL, ",
        "update_timestamp TIMESTAMP NOT NULL, ",
        "create_timestamp TIMESTAMP NOT NULL, ",
        "PRIMARY KEY (role_id, custom_report_id), ",
        "INDEX fk_custom_report_id (custom_report_id ASC), ",
        "INDEX fk_role_id (role_id ASC), ",
        "CONSTRAINT fk_role_has_custom_report_role_id ",
          "FOREIGN KEY (role_id) ",
          "REFERENCES ", @cenozo, ".role (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION, ",
        "CONSTRAINT fk_role_has_custom_report_custom_report_id ",
          "FOREIGN KEY (custom_report_id) ",
          "REFERENCES custom_report (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_role_has_custom_report();
DROP PROCEDURE IF EXISTS patch_role_has_custom_report;
