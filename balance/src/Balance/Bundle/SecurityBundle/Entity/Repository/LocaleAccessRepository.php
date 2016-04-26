<?php

/*
 * This file is part of the Akeneo PIM Enterprise Edition.
 *
 * (c) 2014 Akeneo SAS (http://www.akeneo.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Balance\Bundle\SecurityBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\UserBundle\Entity\Group;
use PimEnterprise\Bundle\SecurityBundle\Attributes;
use Pim\Bundle\UserBundle\Entity\UserInterface;
use PimEnterprise\Bundle\SecurityBundle\Entity\Repository\LocaleAccessRepository as BaseLocaleAccessRepository;

/**
 * Locale access repository
 *
 * @author    Maxim Baibakov <maxim@balanceinternet.com.au>
 * @copyright 2015 Balance Internet (http://www.balanceinternet.com.au)
 */
class LocaleAccessRepository extends BaseLocaleAccessRepository
{
    public function getGrantedLocale(UserInterface $user, $accessLevel) 
    {
        $qb = $this->getGrantedLocaleQB($user, $accessLevel);

        return $this->hydrateAsCodes($qb);
    }

    public function getGrantedLocaleQB(UserInterface $user, $accessLevel)
    {
        $qb = $this->createQueryBuilder('la');
        $qb
            ->andWhere($qb->expr()->in('la.userGroup', ':groups'))
            ->setParameter('groups', $user->getGroups()->toArray())
            ->andWhere($qb->expr()->eq('la.'.$this->getAccessField($accessLevel), true))
            ->andWhere($qb->expr()->eq('l.activated', true))
            ->resetDQLParts(['select'])
            ->innerJoin('la.locale', 'l', 'l.id')
            ->select('l.code');
    
        return $qb;
    }

    /**
     * Execute a query builder and hydrate it as an array of database identifiers
     *
     * @param QueryBuilder $qb
     *
     * @return integer[]
     */
    protected function hydrateAsCodes(QueryBuilder $qb)
    {
        return array_map(
            function ($row) {
                return $row['code'];
            },
            $qb->getQuery()->getArrayResult()
        );
    }
}
