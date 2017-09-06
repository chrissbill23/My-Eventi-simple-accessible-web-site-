SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `utente`;
DROP TABLE IF EXISTS `evento`;
DROP TABLE IF EXISTS `tipoevento`;
DROP TABLE IF EXISTS `categoria`;
DROP TABLE IF EXISTS `primopiano`;
DROP TABLE IF EXISTS `segnalazione`;

CREATE TABLE `utente` ( 

`nome` VARCHAR(50) NOT NULL,
`cognome` VARCHAR(20) NOT NULL,
`nickname`VARCHAR(20) PRIMARY KEY,
`password` VARCHAR(255) NOT NULL,
`email` VARCHAR(70) NOT NULL,
`filexml` VARCHAR(255) NOT NULL,
`isPremium` BOOLEAN DEFAULT FALSE,
`isAdmin` BOOLEAN DEFAULT FALSE,
`ultimoaccesso` CHAR(10) DEFAULT '01/01/0001',
`blocked` BOOLEAN DEFAULT FALSE,
`motivoblocked` VARCHAR(200) DEFAULT ''
) ENGINE=InnoDB;


CREATE TABLE `evento` ( 
`id` VARCHAR(30) PRIMARY KEY,
`dataEv` CHAR(10) NOT NULL,
`ora` CHAR(9) NOT NULL,
`dataPub` CHAR(10) NOT NULL,
`creator` VARCHAR(20) NOT NULL,
`titolo` VARCHAR(50) NOT NULL,
`breveDesc` VARCHAR(255) NOT NULL,
`tipo` INT NOT NULL,
`categ` INT NOT NULL,
`city` VARCHAR(20) NOT NULL,
`filexml` VARCHAR(255) NOT NULL,
`tag` VARCHAR(255),
`blocked` BOOLEAN DEFAULT FALSE,
`motivoblocked` VARCHAR(200) DEFAULT '',
FOREIGN KEY(creator) REFERENCES utente(nickname),
FOREIGN KEY(tipo) REFERENCES tipoevento(id),
FOREIGN KEY(categ) REFERENCES categoria(id)
) ENGINE=InnoDB;

CREATE TABLE `tipoevento` ( 
`nome` VARCHAR(20) NOT NULL UNIQUE,
`id` INT PRIMARY KEY AUTO_INCREMENT
) ENGINE=InnoDB;


CREATE TABLE `categoria` ( 
`nome` VARCHAR(20) NOT NULL UNIQUE,
`id` INT PRIMARY KEY AUTO_INCREMENT
) ENGINE=InnoDB DEFAULT CHARSET=utf8;;

CREATE TABLE `primopiano` ( 
`idev` VARCHAR(30) PRIMARY KEY,
`owner` VARCHAR(20) NOT NULL,
FOREIGN KEY(owner) REFERENCES utente(nickname),
FOREIGN KEY(idev) REFERENCES evento(id)
) ENGINE=InnoDB;

CREATE TABLE `segnalazione` ( 
`conta` INT PRIMARY KEY AUTO_INCREMENT,
`idev` VARCHAR(30) NOT NULL,
`motivo` VARCHAR(255) NOT NULL,
FOREIGN KEY(idev) REFERENCES evento(id)
) ENGINE=InnoDB;

INSERT INTO `utente` (`nome`, `cognome`,`nickname`,`password`,`email`,`filexml`,`isPremium`,`isAdmin`) VALUES
('Christian','Bile','chrissbill','$2y$10$YQuHagQjahFCMVmEtks5hOirVtDs14ZaS2w27IEyFBBOWpY5CUkuW','chrissbill23@gmail.com','njsnjdnjnj.xml', TRUE,FALSE),
('Christian','Bile','admin','$2y$10$wYezdsp.PBkH2sZzlj0cuOlZHWa3gOsRr3kqXbiMWSAb8PqbzHEia','admin@gmail.com','admin.xml', FALSE,TRUE);


INSERT INTO `tipoevento` (`nome`) VALUES
('Concerto'),
('Conferenza'),
('Convegno'),
('Congresso'),
('Manifestazione'),
('Seminario');

INSERT INTO `categoria` (`nome`) VALUES
('Intrattenimento'),
('Scienze'),
('Sport');


SET FOREIGN_KEY_CHECKS=1;