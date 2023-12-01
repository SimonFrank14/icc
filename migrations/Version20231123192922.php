<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231123192922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE student_absence_type_subjects (student_absence_type_id INT UNSIGNED NOT NULL, subject_id INT UNSIGNED NOT NULL, INDEX IDX_3C9888FC506E213D (student_absence_type_id), INDEX IDX_3C9888FC23EDC87 (subject_id), PRIMARY KEY(student_absence_type_id, subject_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE student_absence_type_subjects ADD CONSTRAINT FK_3C9888FC506E213D FOREIGN KEY (student_absence_type_id) REFERENCES student_absence_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student_absence_type_subjects ADD CONSTRAINT FK_3C9888FC23EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student_absence_type ADD is_type_with_present_attendance TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE student_absence_type ADD book_attendance_type INT NOT NULL, ADD book_excuse_status INT NOT NULL, DROP is_always_excused');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE student_absence_type_subjects DROP FOREIGN KEY FK_3C9888FC506E213D');
        $this->addSql('ALTER TABLE student_absence_type_subjects DROP FOREIGN KEY FK_3C9888FC23EDC87');
        $this->addSql('DROP TABLE student_absence_type_subjects');
        $this->addSql('ALTER TABLE student_absence_type DROP is_type_with_present_attendance');
        $this->addSql('ALTER TABLE student_absence_type ADD is_always_excused TINYINT(1) NOT NULL, DROP book_attendance_type, DROP book_excuse_status');
    }
}
