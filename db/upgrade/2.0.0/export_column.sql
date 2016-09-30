SELECT "Creating new export_column table" AS "";

CREATE TABLE IF NOT EXISTS export_column (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL,
  create_timestamp TIMESTAMP NOT NULL,
  export_id INT UNSIGNED NOT NULL,
  rank INT UNSIGNED NOT NULL,
  table_name VARCHAR(45) NOT NULL,
  column_name VARCHAR(45) NOT NULL,
  subtype VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX uq_export_id_rank (export_id ASC, rank ASC))
ENGINE = InnoDB;
