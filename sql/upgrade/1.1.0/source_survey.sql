CREATE  TABLE IF NOT EXISTS source_survey (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  update_timestamp TIMESTAMP NOT NULL ,
  create_timestamp TIMESTAMP NOT NULL ,
  phase_id INT UNSIGNED NOT NULL ,
  source_id INT UNSIGNED NOT NULL ,
  sid INT NOT NULL ,
  PRIMARY KEY (id) ,
  INDEX fk_phase_id (phase_id ASC) ,
  INDEX fk_source_id (source_id ASC) ,
  UNIQUE INDEX uq_phase_id_source_id (phase_id ASC, source_id ASC) ,
  CONSTRAINT fk_source_survey_phase_id
    FOREIGN KEY (phase_id )
    REFERENCES phase (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_source_survey_source_id
    FOREIGN KEY (source_id )
    REFERENCES source (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
