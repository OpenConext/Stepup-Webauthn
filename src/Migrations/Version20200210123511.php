<?php

/**
 * Copyright 2019 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 *
 * @SuppressWarnings(PHPMD)
 */
final class Version20200210123511 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE public_key_credential_sources (id INT AUTO_INCREMENT NOT NULL, public_key_credential_id LONGTEXT NOT NULL COMMENT \'(DC2Type:base64)\', type VARCHAR(255) NOT NULL, transports LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', attestation_type VARCHAR(255) NOT NULL, trust_path LONGTEXT NOT NULL COMMENT \'(DC2Type:trust_path)\', aaguid TINYTEXT NOT NULL COMMENT \'(DC2Type:aaguid)\', credential_public_key LONGTEXT NOT NULL COMMENT \'(DC2Type:base64)\', user_handle VARCHAR(255) NOT NULL, counter INT NOT NULL, fmt VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, icon VARCHAR(255) DEFAULT NULL, display_name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users_user_handles (user_id VARCHAR(36) NOT NULL, user_handle INT NOT NULL, INDEX IDX_EFD91D5DA76ED395 (user_id), UNIQUE INDEX UNIQ_EFD91D5DF4D23BE4 (user_handle), PRIMARY KEY(user_id, user_handle)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE users_user_handles ADD CONSTRAINT FK_EFD91D5DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users_user_handles ADD CONSTRAINT FK_EFD91D5DF4D23BE4 FOREIGN KEY (user_handle) REFERENCES public_key_credential_sources (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users_user_handles DROP FOREIGN KEY FK_EFD91D5DF4D23BE4');
        $this->addSql('ALTER TABLE users_user_handles DROP FOREIGN KEY FK_EFD91D5DA76ED395');
        $this->addSql('DROP TABLE public_key_credential_sources');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE users_user_handles');
    }
}
