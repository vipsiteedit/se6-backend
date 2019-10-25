DROP TABLE IF EXISTS `app_section_collection_similar`;
CREATE TABLE IF NOT EXISTS `app_section_collection_similar` (
`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
`id_collection` int(10) UNSIGNED NOT NULL,
`id_item` int(10) UNSIGNED NOT NULL,
`updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
KEY `id_collection` (`id_collection`),
KEY `id_item` (`id_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Related records link';
ALTER TABLE `app_section_collection_similar`
ADD CONSTRAINT `app_section_collection_similar_ibfk_1` FOREIGN KEY (`id_collection`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `app_section_collection_similar_ibfk_2` FOREIGN KEY (`id_item`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
