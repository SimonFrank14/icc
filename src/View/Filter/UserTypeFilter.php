<?php

namespace App\View\Filter;

use App\Entity\User;
use App\Entity\UserType;
use App\Security\Voter\DocumentVoter;
use App\Sorting\Sorter;
use App\Sorting\UserTypeStrategy;
use App\Utils\ArrayUtils;
use App\Utils\EnumArrayUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserTypeFilter {
    private array $types;

    public function __construct(private Sorter $sorter) {
        $this->types = UserType::values();
    }

    /**
     * @param User|null $user
     * @param UserType[] $onlyTypes Restrict to the given user types
     * @return UserTypeFilterView
     */
    public function handle(?string $userType, User $user = null, bool $isRestrictedToOwnType = false, ?UserType $defaultType = null, array $onlyTypes = [ ]) {
        if($isRestrictedToOwnType === true) {
            return new UserTypeFilterView([ ], $user !== null ? $user->getUserType() : $defaultType);
        }

        if(empty($onlyTypes)) {
            $enums = $this->types;
        } else {
            $enums = $onlyTypes;
        }

        $types = ArrayUtils::createArrayWithKeys($enums, fn(UserType $type) => $type->getValue());

        if($user === null) {
            $fallbackUserType = null;
        } else {
            $fallbackUserType = $types[$user->getUserType()->getValue()] ?? null;
        }

        if($user !== null) {
            $type = $userType != null ?
                $types[$userType] ?? $fallbackUserType : $fallbackUserType;
        } else {
            $type = $types[$userType] ?? $defaultType;
        }

        $this->sorter->sort($types, UserTypeStrategy::class);

        return new UserTypeFilterView($types, $type);
    }
}