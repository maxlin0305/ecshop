<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version208 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("
            CREATE TABLE `push_message` (
              `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主键',
              `company_id` bigint(20) NOT NULL COMMENT '公司id',
              `merchant_id` bigint(20) NOT NULL COMMENT '商户id',
              `distributor_id` bigint(20) unsigned NULL DEFAULT '0' COMMENT '分销商id',
               `user_id` bigint(20) NULL DEFAULT '0' COMMENT '用户id',
              `msg_name` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '消息名称',
              `msg_type` smallint(6) DEFAULT NULL COMMENT '消息类型:1 到货通知',
              `content` longtext COLLATE utf8mb4_unicode_ci COMMENT '内容',
              `is_read` smallint(6) DEFAULT '0' COMMENT '是否已读:0,未读;1,已读',
              `create_time` int(11) NOT NULL COMMENT '创建时间',
              `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
              PRIMARY KEY (`id`),
              KEY `idx_company_id` (`company_id`),
              KEY `idx_distributor_id` (`distributor_id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推送通知表';
        "
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
