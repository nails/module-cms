ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` ADD `published_template_options` TEXT  NULL  AFTER `published_template_data`;
ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` ADD `draft_template_options` TEXT  NULL  AFTER `draft_template_data`;
ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` ADD `published_template_options` TEXT  NULL  AFTER `published_template_data`;
ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` ADD `draft_template_options` TEXT  NULL  AFTER `draft_template_data`;
