<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use Illuminate\Database\Schema\Blueprint;

class Version306 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("alter table ecpay_delivery_info
    add company_id int default 1 null comment '';");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
