<?php

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema as Schema;
use Doctrine\Migrations\AbstractMigration;

class Version101 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("CREATE TABLE `ecpay_bind_card` (
  `id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` int(11) unsigned DEFAULT NULL,
  `token` varchar(32) NOT NULL DEFAULT '' COMMENT '绑卡请求 token',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='绿界-绑卡申请';");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
