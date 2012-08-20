CREATE TABLE IF NOT EXISTS quota (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  update_timestamp TIMESTAMP NOT NULL ,
  create_timestamp TIMESTAMP NOT NULL ,
  region_id INT UNSIGNED NOT NULL ,
  gender ENUM('male','female') NOT NULL ,
  age_group_id INT UNSIGNED NOT NULL ,
  population INT NOT NULL ,
  disabled TINYINT(1) NOT NULL DEFAULT false ,
  PRIMARY KEY (id) ,
  INDEX fk_region_id (region_id ASC) ,
  INDEX fk_age_group_id (age_group_id ASC) ,
  UNIQUE INDEX uq_region_id_gender_age_group_id (region_id ASC, gender ASC, age_group_id ASC) ,
  CONSTRAINT fk_quota_region
    FOREIGN KEY (region_id )
    REFERENCES region (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_quota_age_group_id
    FOREIGN KEY (age_group_id )
    REFERENCES age_group (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
