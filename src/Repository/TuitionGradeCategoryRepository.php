<?php

namespace App\Repository;

use App\Entity\TuitionGradeCategory;
use App\Entity\TuitionGradeCatalog;

class TuitionGradeCategoryRepository extends AbstractRepository implements TuitionGradeCategoryRepositoryInterface {

    public function findAll(): array {
        return $this->em->getRepository(TuitionGradeCategory::class)
            ->findBy([], ['position' => 'asc']);
    }

    public function findAllByGradeType(TuitionGradeCatalog $type): array {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(TuitionGradeCategory::class, 'c')
            ->leftJoin('c.gradeType', 't')
            ->where('t.id = :type')
            ->setParameter('type', $type->getId())
            ->getQuery()
            ->getResult();
    }

    public function persist(TuitionGradeCategory $category): void {
        $this->em->persist($category);
        $this->em->flush();
    }

    public function remove(TuitionGradeCategory $category): void {
        $this->em->remove($category);
        $this->em->flush();
    }
}