<?php

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema as Schema;
use Doctrine\Migrations\AbstractMigration;

class Version100 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("alter table members add column `email` varchar(255) default null COMMENT '邮箱' after mobile;");
        $this->addSql("alter table members drop index mobile_company;");
        $this->addSql("alter table members modify column `mobile` varchar(255) COLLATE utf8mb4_unicode_ci default null COMMENT '手机号';");
        $this->addSql("alter table members add index `idx_email` (`email`);");
        $this->addSql("alter table members add unique index uniq_mobile_email_company(`mobile`,`email`,`company_id`)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
