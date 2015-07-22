CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) NOT NULL,
  `method` varchar(250) DEFAULT NULL,
  `url` varchar(250) DEFAULT NULL,
  `ip` varchar(250) DEFAULT NULL,
  `auth` tinyint(1) NOT NULL DEFAULT '0',
  `headers` text,
  `input` text,
  `output` text,
  `httpcode` int(3) DEFAULT NULL,
  `exectime` float NOT NULL,
  `dateinsert` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `log`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;