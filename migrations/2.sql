ALTER TABLE `{{NAILS_DB_PREFIX}}cms_block` CHANGE `type` `type` ENUM('plaintext','richtext','image','file','number','url', 'email')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'plaintext';
