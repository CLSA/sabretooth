CREATE TABLE role_has_custom_report (
  role_id INT(10) UNSIGNED NOT NULL,
  custom_report_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (role_id, custom_report_id),
  INDEX fk_custom_report_id (custom_report_id ASC),
  INDEX fk_role_id (role_id ASC),
  CONSTRAINT fk_role_has_custom_report_role_id
    FOREIGN KEY (role_id)
    REFERENCES cenozo.role (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_role_has_custom_report_custom_report_id
    FOREIGN KEY (custom_report_id)
    REFERENCES sabretooth.custom_report (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
