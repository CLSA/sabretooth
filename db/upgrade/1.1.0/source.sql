CREATE TABLE IF NOT EXISTS source (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  update_timestamp TIMESTAMP NOT NULL ,
  create_timestamp TIMESTAMP NOT NULL ,
  name VARCHAR(45) NOT NULL ,
  withdraw_type ENUM('verbal accept','verbal deny','written accept','written deny','retract','withdraw') NOT NULL DEFAULT "withdraw" ,
  PRIMARY KEY (id) ,
  UNIQUE INDEX uq_name (name ASC) )
ENGINE = InnoDB;

INSERT IGNORE INTO source (name,withdraw_type)
VALUES ('statscan','withdraw'),('ministry','verbal deny'),('rdd','verbal deny');
