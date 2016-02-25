# common test table
DROP TABLE IF EXISTS `test_schema`;

CREATE TABLE IF NOT EXISTS `test_schema` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `age` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `test_schema` (`name`, `age`) VALUES ('Alex', 10);
INSERT INTO `test_schema` (`name`, `age`) VALUES ('Jack', 20);
INSERT INTO `test_schema` (`name`, `age`) VALUES ('Bill', 15);
INSERT INTO `test_schema` (`name`, `age`) VALUES ('Carl', 20);