<?php

namespace App\Response\Api\V1;

use JMS\Serializer\Annotation as Serializer;

class SubstitutionList {

    /**
     * @Serializer\SerializedName("substitutions")
     * @Serializer\Type("array<App\Response\Api\V1\Substitution>")
     *
     * @var Substitution[]
     */
    private ?array $substitutions = null;

    /**
     * @return Substitution[]
     */
    public function getSubstitutions(): array {
        return $this->substitutions;
    }

    /**
     * @param Substitution[] $substitutions
     */
    public function setSubstitutions(array $substitutions): SubstitutionList {
        $this->substitutions = $substitutions;
        return $this;
    }
}