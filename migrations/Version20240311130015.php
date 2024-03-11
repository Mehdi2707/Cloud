<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240311130015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE uploaded_files ADD folder_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE uploaded_files ADD CONSTRAINT FK_E60EFB5162CB942 FOREIGN KEY (folder_id) REFERENCES folder (id)');
        $this->addSql('CREATE INDEX IDX_E60EFB5162CB942 ON uploaded_files (folder_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE uploaded_files DROP FOREIGN KEY FK_E60EFB5162CB942');
        $this->addSql('DROP INDEX IDX_E60EFB5162CB942 ON uploaded_files');
        $this->addSql('ALTER TABLE uploaded_files DROP folder_id');
    }
}
