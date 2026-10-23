<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022204135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Demo shop #1 and its delivery tariffs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO shop (id, name, timezone) VALUES (1, 'Цветочная лавка', 'Europe/Moscow')");
        $this->addSql(<<<'SQL'
            INSERT INTO delivery_tariff (shop_id, district, name, base_price, free_from) VALUES
                (1, 'centre', 'Центр', 30000, 500000),
                (1, 'north', 'Север', 40000, 700000),
                (1, 'south', 'Юг', 40000, 700000),
                (1, 'suburb', 'Пригород', 70000, NULL)
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM delivery_tariff WHERE shop_id = 1');
        $this->addSql('DELETE FROM shop WHERE id = 1');
    }
}
