<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use Illuminate\Database\Schema\Blueprint;

class Version304 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("create table point_member_multiple_integral
(
    id                     bigint unsigned auto_increment
        primary key,
    user_id                bigint        not null comment '用户ID',
    point_member_log_id    bigint        not null comment '获得积分记录ID',
    income                 int           not null comment '入账积分',
    used_points            int default 0 not null comment '已使用积分',
    mi_multiple            int           not null comment '倍数',
    mi_expiration_reminder int           not null comment '是否开启到期提醒[1:开启/2：关闭]',
    mi_reminder_copy       varchar(255)  not null comment '提醒文案',
    expiration_time        int           not null comment '到期时间'
)
    collate = utf8mb4_unicode_ci;

");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
