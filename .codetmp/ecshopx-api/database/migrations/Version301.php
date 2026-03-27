<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version301 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("alter table community_chief_cash_withdrawal modify wxa_appid varchar(255) null comment '提现的小程序appid';");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
