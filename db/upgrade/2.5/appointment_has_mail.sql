DROP PROCEDURE IF EXISTS patch_role_has_service;
DELIMITER //
CREATE PROCEDURE patch_role_has_service()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Adding services to roles" AS "";

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS appointment_has_mail ( ",
        "appointment_id INT UNSIGNED NOT NULL, ",
        "mail_id INT UNSIGNED NOT NULL, ",
        "update_timestamp TIMESTAMP NOT NULL, ",
        "create_timestamp TIMESTAMP NOT NULL, ",
        "PRIMARY KEY (appointment_id, mail_id), ",
        "INDEX fk_mail_id (mail_id ASC), ",
        "INDEX fk_appointment_id (appointment_id ASC), ",
        "UNIQUE INDEX uq_appointment_id_mail_id (appointment_id ASC, mail_id ASC), ",
        "CONSTRAINT fk_appointment_has_mail_appointment_id ",
          "FOREIGN KEY (appointment_id) ",
          "REFERENCES appointment (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION, ",
        "CONSTRAINT fk_appointment_has_mail_mail_id ",
          "FOREIGN KEY (mail_id) ",
          "REFERENCES ", @cenozo, ".mail (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_role_has_service();
DROP PROCEDURE IF EXISTS patch_role_has_service;
