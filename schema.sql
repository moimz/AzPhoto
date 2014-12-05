--
-- 테이블 구조 `az_photos_table`
--

CREATE TABLE IF NOT EXISTS `az_photos_table` (
  `filename` char(32) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `time` int(11) NOT NULL,
  `width` int(5) NOT NULL,
  `height` int(5) NOT NULL,
  `reg_date` int(11) NOT NULL,
  `longitude` double NOT NULL,
  `latitude` double NOT NULL,
  `exif` longtext NOT NULL,
  PRIMARY KEY (`filename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;