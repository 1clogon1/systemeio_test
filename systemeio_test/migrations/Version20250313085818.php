<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250313085818 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coupons ALTER is_active SET DEFAULT false');
        $this->addSql('ALTER TABLE product ALTER price TYPE INT');
        $this->addSql('ALTER TABLE product ALTER price SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD5E237E06 ON product (name)');
        $this->addSql('ALTER TABLE tax ADD pattern VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE tax DROP length');
        $this->addSql('ALTER TABLE tax DROP has_letters');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8E81BA765373C966 ON tax (country)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coupons ALTER is_active DROP DEFAULT');
        $this->addSql('DROP INDEX UNIQ_D34A04AD5E237E06');
        $this->addSql('ALTER TABLE product ALTER price TYPE INT');
        $this->addSql('ALTER TABLE product ALTER price DROP NOT NULL');
        $this->addSql('DROP INDEX UNIQ_8E81BA765373C966');
        $this->addSql('ALTER TABLE tax ADD length INT NOT NULL');
        $this->addSql('ALTER TABLE tax ADD has_letters BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE tax DROP pattern');
    }
}
