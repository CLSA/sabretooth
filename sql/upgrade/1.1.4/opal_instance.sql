CREATE TABLE IF NOT EXISTS opal_instance (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  update_timestamp TIMESTAMP NOT NULL ,
  create_timestamp TIMESTAMP NOT NULL ,
  user_id INT UNSIGNED NOT NULL ,
  PRIMARY KEY (id) ,
  INDEX fk_user_id (user_id ASC) ,
  UNIQUE INDEX uq_user_id (user_id ASC) ,
  CONSTRAINT fk_opal_instance_user_id
    FOREIGN KEY (user_id )
    REFERENCES user (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
