<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version207 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE push_logs (id INT AUTO_INCREMENT NOT NULL, company_id BIGINT NOT NULL COMMENT \'公司id\', request_url VARCHAR(50) NOT NULL COMMENT \'请求路径\', request_params LONGTEXT DEFAULT NULL COMMENT \'请求参数\', response_data LONGTEXT DEFAULT NULL COMMENT \'响应参数\', http_status_code INT NOT NULL COMMENT \'http状态码\', status INT NOT NULL COMMENT \'状态 0成功 1失败\', push_time VARCHAR(50) NOT NULL COMMENT \'推送时间\', cost_time INT DEFAULT 0 NOT NULL COMMENT \'耗时(毫秒)\', retry_times INT DEFAULT 0 NOT NULL COMMENT \'重试次数\', method VARCHAR(20) NOT NULL COMMENT \'请求方法\', type VARCHAR(20) NOT NULL COMMENT \'请求类型\', created INT NOT NULL, updated INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
