ALTER TABLE app_nav_url
  ADD COLUMN id_collection INT(10) UNSIGNED DEFAULT NULL AFTER id_parent;

ALTER TABLE app_nav_url
  ADD COLUMN id_group INT(10) UNSIGNED DEFAULT NULL AFTER id_parent;

ALTER TABLE app_nav_url
ADD CONSTRAINT FK_app_nav_url_id_collection FOREIGN KEY (id_collection)
REFERENCES app_section_collection (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE app_nav_url
ADD CONSTRAINT FK_app_nav_url_id_group FOREIGN KEY (id_group)
REFERENCES app_section_groups (id) ON DELETE CASCADE ON UPDATE CASCADE;
