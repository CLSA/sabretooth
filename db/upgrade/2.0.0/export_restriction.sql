SELECT "Creating new export_restriction table" AS "";

CREATE TABLE IF NOT EXISTS export_restriction (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL,
  create_timestamp TIMESTAMP NOT NULL,
  export_id INT UNSIGNED NOT NULL,
  export_column_id INT UNSIGNED NOT NULL,
  rank INT UNSIGNED NOT NULL,
  column_name VARCHAR(45) NOT NULL,
  logic ENUM('or', 'and') NOT NULL DEFAULT 'and',
  test ENUM('<=>', '<>', '<', '>', 'like', 'not like') NOT NULL DEFAULT '<=>',
  value VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX uq_export_id_rank (export_id ASC, rank ASC))
ENGINE = InnoDB;
