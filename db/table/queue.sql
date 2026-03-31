CREATE TABLE queue (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  name VARCHAR(45) NOT NULL,
  title VARCHAR(255) NOT NULL,
  rank INT(10) UNSIGNED NULL DEFAULT NULL,
  time_specific TINYINT(1) NOT NULL,
  parent_queue_id INT(10) UNSIGNED NULL DEFAULT NULL,
  description MEDIUMTEXT NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX uq_name (name ASC),
  UNIQUE INDEX uq_rank (rank ASC),
  INDEX fk_parent_queue_id (parent_queue_id ASC),
  CONSTRAINT fk_queue_parent_queue_id
    FOREIGN KEY (parent_queue_id)
    REFERENCES sabretooth.queue (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
