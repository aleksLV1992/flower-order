<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022204133 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schema: shops, delivery tariffs, orders, order items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE delivery_tariff (district VARCHAR(32) NOT NULL, name VARCHAR(64) NOT NULL, base_price INT NOT NULL, free_from INT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, shop_id INT NOT NULL, UNIQUE INDEX uniq_tariff_shop_district (shop_id, district), INDEX IDX_8A74D9F34D16C4DD (shop_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_item (position SMALLINT NOT NULL, name VARCHAR(255) NOT NULL, qty INT NOT NULL, unit_price INT NOT NULL, sku VARCHAR(64) DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, INDEX IDX_52EA1F098D9F6D38 (order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE orders (external_id VARCHAR(64) NOT NULL COLLATE `utf8mb4_bin`, status VARCHAR(16) NOT NULL, customer_name VARCHAR(255) NOT NULL, customer_phone VARCHAR(12) NOT NULL, delivery_type VARCHAR(16) NOT NULL, district VARCHAR(32) DEFAULT NULL, address VARCHAR(500) DEFAULT NULL, interval_start DATETIME DEFAULT NULL, interval_end DATETIME DEFAULT NULL, placed_at DATETIME DEFAULT NULL, items_total INT NOT NULL, declared_total INT DEFAULT NULL, needs_review TINYINT NOT NULL, review_reasons JSON NOT NULL, delivery_cost INT NOT NULL, delivery_breakdown JSON NOT NULL, grand_total INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, shop_id INT NOT NULL, INDEX idx_order_shop_status_id (shop_id, status, id), UNIQUE INDEX uniq_order_shop_external (shop_id, external_id), INDEX IDX_E52FFDEE4D16C4DD (shop_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shop (name VARCHAR(255) NOT NULL, timezone VARCHAR(64) NOT NULL, id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE delivery_tariff ADD CONSTRAINT FK_8A74D9F34D16C4DD FOREIGN KEY (shop_id) REFERENCES shop (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE4D16C4DD FOREIGN KEY (shop_id) REFERENCES shop (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE delivery_tariff DROP FOREIGN KEY FK_8A74D9F34D16C4DD');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098D9F6D38');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE4D16C4DD');
        $this->addSql('DROP TABLE delivery_tariff');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE shop');
    }
}
