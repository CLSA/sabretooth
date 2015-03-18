-- create new age_group table
CREATE  TABLE IF NOT EXISTS age_group (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  update_timestamp TIMESTAMP NOT NULL ,
  create_timestamp TIMESTAMP NOT NULL ,
  lower INT NOT NULL ,
  upper INT NOT NULL ,
  PRIMARY KEY (id) ,
  UNIQUE INDEX uq_lower (lower ASC) ,
  UNIQUE INDEX uq_upper (upper ASC) )
ENGINE = InnoDB;
