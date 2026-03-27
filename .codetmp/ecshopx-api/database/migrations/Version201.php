<?php

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema as Schema;
use Doctrine\Migrations\AbstractMigration;

class Version201 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('CREATE TABLE `ecpay_delivery_info` (
                    `id` BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\',
                    `logistics_id` varchar(20) NOT NULL COMMENT \'物流单号\',
                    `booking_note` varchar(60) DEFAULT NULL COMMENT \'托運單號\',
                    `cvs_payment_no` varchar(32) DEFAULT NULL COMMENT \'寄貨編號\',
                    `cvs_validation_no` varchar(32) DEFAULT NULL COMMENT \'驗證碼\',
                    `goods_amount` varchar(32) DEFAULT NULL COMMENT \'商品金額\',
                    `logistics_type` varchar(32) DEFAULT NULL COMMENT \'物流類型\',
                    `logistics_subtype` varchar(32) DEFAULT NULL COMMENT \'物流子類型\',
                    `merchant_id` varchar(32) DEFAULT NULL COMMENT \'廠商編號\',
                    `merchant_tradeno` varchar(50) NOT NULL COMMENT \'廠商交易編號\',
                    `receiver_address` varchar(250) DEFAULT NULL COMMENT \'收件人地址\',
                    `receiver_mobile` varchar(32) DEFAULT NULL COMMENT \'收件人手機\',
                    `receiver_email` varchar(100) DEFAULT NULL COMMENT \'收件人email\',
                    `receiver_name` varchar(70) DEFAULT NULL COMMENT \'收件人姓名\',
                    `receiver_phone` varchar(32) DEFAULT NULL COMMENT \'收件人電話\',
                    `rtn_code` varchar(32) DEFAULT NULL COMMENT \'目前物流狀態\',
                    `rtn_msg` varchar(255) DEFAULT NULL COMMENT \'物流狀態說明\',
                    `update_status_date` varchar(255) DEFAULT NULL COMMENT \'物流狀態更新時間\',
                    `created_at` datetime DEFAULT NULL,
                    `updated_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    INDEX idx_logistics_id (logistics_id),
                    INDEX idx_merchant_tradeno (merchant_tradeno)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT=\'绿界-物流信息\';');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}