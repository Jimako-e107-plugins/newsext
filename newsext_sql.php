CREATE TABLE `news_extended` (
`news_id` int(11) NOT NULL,
`news_categories` varchar(100) NOT NULL,
 UNIQUE KEY (`news_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;