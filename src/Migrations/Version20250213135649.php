<?php

/**
 * Copyright 2025 SURFnet B.V.
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
 * @SuppressWarnings(PHPMD)
 */
final class Version20250213135649 extends AbstractMigration
{

    private static string $select = <<<SQL
        SELECT id, transports, other_ui
        FROM public_key_credential_sources;
SQL;

    private static string $update = <<<SQL
        UPDATE public_key_credential_sources
        SET transports = :transports, other_ui = :other_ui
        WHERE id = :id
SQL;

    public function getDescription(): string
    {
        return '';
    }

    public function preUp(Schema $schema): void
    {
        $this->addSql('# Updating php serialized fields to json fields.');

        $result = $this->connection->executeQuery(self::$select);

        $rows = $result->fetchAllAssociative();
        $this->write("<info>Records to migrate: {$result->rowCount()}</info>");

        if ($result->rowCount() === 0) {
            return;
        }

        foreach ($rows as $row) {
            $id = $row['id'];
            $transports = $row['transports'];
            $otherUi = $row['other_ui'];

            $this->write("<info>Migating: {$id}</info>");
            
            if ($transports !== null) {
                $transports = json_encode(unserialize($transports), JSON_THROW_ON_ERROR);
            }
            
            if ($otherUi !== null) {
                $otherUi = json_encode(unserialize($otherUi), JSON_THROW_ON_ERROR);
            }
            
            $this->connection->executeUpdate(
                self::$update,
                [
                    'id' => $id,
                    'transports' => $transports,
                    'other_ui' => $otherUi,
                ],
            );
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users_user_handles DROP FOREIGN KEY FK_EFD91D5DF4D23BE4');
        $this->addSql('ALTER TABLE users_user_handles DROP FOREIGN KEY FK_EFD91D5DA76ED395');
        $this->addSql('DROP TABLE users_user_handles');
        $this->addSql('ALTER TABLE public_key_credential_sources CHANGE id id VARCHAR(36) NOT NULL, CHANGE transports transports JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE other_ui other_ui JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users_user_handles (user_id VARCHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, user_handle INT NOT NULL, UNIQUE INDEX UNIQ_EFD91D5DF4D23BE4 (user_handle), INDEX IDX_EFD91D5DA76ED395 (user_id), PRIMARY KEY(user_id, user_handle)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE users_user_handles ADD CONSTRAINT FK_EFD91D5DF4D23BE4 FOREIGN KEY (user_handle) REFERENCES public_key_credential_sources (id)');
        $this->addSql('ALTER TABLE users_user_handles ADD CONSTRAINT FK_EFD91D5DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE public_key_credential_sources CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE transports transports LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', CHANGE other_ui other_ui LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
    }
}
