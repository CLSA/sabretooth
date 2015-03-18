CREATE  TABLE IF NOT EXISTS source_withdraw (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  update_timestamp TIMESTAMP NOT NULL ,
  create_timestamp TIMESTAMP NOT NULL ,
  qnaire_id INT UNSIGNED NOT NULL ,
  source_id INT UNSIGNED NOT NULL ,
  sid INT NULL ,
  PRIMARY KEY (id) ,
  INDEX fk_source_withdraw_qnaire_id (qnaire_id ASC) ,
  INDEX fk_source_withdraw_source_id (source_id ASC) ,
  UNIQUE INDEX uq_qnaire_id_source_id (qnaire_id ASC, source_id ASC) ,
  CONSTRAINT fk_source_withdraw_qnaire_id
    FOREIGN KEY (qnaire_id )
    REFERENCES qnaire (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_source_withdraw_source_id
    FOREIGN KEY (source_id )
    REFERENCES source (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
