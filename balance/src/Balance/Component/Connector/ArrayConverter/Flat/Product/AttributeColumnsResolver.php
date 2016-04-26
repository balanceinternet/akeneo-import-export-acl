<?php

namespace Balance\Component\Connector\ArrayConverter\Flat\Product;

use Pim\Bundle\CatalogBundle\AttributeType\AttributeTypes;
use Pim\Bundle\CatalogBundle\Manager\AttributeValuesResolver;
use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;
use Pim\Bundle\CatalogBundle\Repository\CurrencyRepositoryInterface;
use Pim\Component\Connector\ArrayConverter\Flat\Product\AttributeColumnsResolver as BaseAttributeColumnsResolver;
use PimEnterprise\Bundle\SecurityBundle\Attributes;
use Pim\Bundle\UserBundle\Provider\UserProvider;
use Doctrine\ORM\EntityManager;
use Pim\Component\Connector\Exception\ArrayConversionException;
use Akeneo\Bundle\BatchBundle\Entity\JobExecution;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Resolve attribute field information
 *
 * @author    Maxim Baibakov <maxim@balanceinternet.com.au>
 * @copyright 2015 Balance Internet (http://www.balanceinternet.com.au)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AttributeColumnsResolver extends BaseAttributeColumnsResolver
{
    /** @var entityManager */
    protected $entityManager;

    /** @var UserProvider */
    protected $userProvider;
   
    protected $session;

    /**
     * @param AttributeRepositoryInterface $attributeRepository
     * @param CurrencyRepositoryInterface  $currencyRepository
     * @param AttributeValuesResolver      $valuesResolver
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        CurrencyRepositoryInterface $currencyRepository,
        AttributeValuesResolver $valuesResolver,
        EntityManager $entityManager,
        UserProvider $userProvider,
        $session
    ) {
        parent::__construct(
            $attributeRepository,
            $currencyRepository,
            $valuesResolver
        );

        $this->session       = $session;
        $this->entityManager = $entityManager;
        $this->userProvider  = $userProvider;
    }

    /**
     * @return array
     */
    public function resolveAttributeColumns()
    {
        if (empty($this->attributesFields)) {

            $user = $this->session->get('job_execution_user');
            $user = $this->userProvider->loadUserByUsername($user);

            $localeAccessRP = $this->entityManager->getRepository('PimEnterpriseSecurityBundle:LocaleAccess');
            $grantedLocale = $localeAccessRP->getGrantedLocale($user, Attributes::EDIT_ITEMS);

            $attributes = $this->attributeRepository->findAll();
            $attributes = $this->checkAttributesPermissions($attributes);
            $currencyCodes = $this->currencyRepository->getActivatedCurrencyCodes();
            $values = $this->valuesResolver->resolveEligibleValues($attributes);
            foreach ($values as $value) {
                if (null !== $value['locale'] && !in_array($value['locale'], $grantedLocale)) {
                    continue;
                }
                if (null !== $value['locale'] && null !== $value['scope']) {
                    $field = sprintf(
                        '%s-%s-%s',
                        $value['attribute'],
                        $value['locale'],
                        $value['scope']
                    );
                } elseif (null !== $value['locale']) {
                    $field = sprintf(
                        '%s-%s',
                        $value['attribute'],
                        $value['locale']
                    );
                } elseif (null !== $value['scope']) {
                    $field = sprintf(
                        '%s-%s',
                        $value['attribute'],
                        $value['scope']
                    );
                } else {
                    $field = $value['attribute'];
                }

                if (AttributeTypes::PRICE_COLLECTION === $value['type']) {
                    $this->attributesFields[] = $field;
                    foreach ($currencyCodes as $currencyCode) {
                        $currencyField = sprintf('%s-%s', $field, $currencyCode);
                        $this->attributesFields[] = $currencyField;
                    }
                } elseif (AttributeTypes::METRIC === $value['type']) {
                    $this->attributesFields[] = $field;
                    $metricField = sprintf('%s-%s', $field, 'unit');
                    $this->attributesFields[] = $metricField;
                } else {
                    $this->attributesFields[] = $field;
                }
            }
        }

        return $this->attributesFields;
    }

    /**
     * {@inheritdoc}
     *
     * We override parent to keep only attributes the user can edit
     */
    public function checkAttributesPermissions($allAttributes)
    {
        $grantedAttributes = [];

        $user = $this->session->get('job_execution_user');
        $user = $this->userProvider->loadUserByUsername($user);
        $attGroupAccessRP = $this->entityManager->getRepository('PimEnterpriseSecurityBundle:AttributeGroupAccess');
        $grantedAttributeGroups = $attGroupAccessRP->getGrantedAttributeGroupIds($user, Attributes::EDIT_ATTRIBUTES);

        foreach ($allAttributes as $attribute) {
          if (in_array($attribute->getGroup()->getId(), $grantedAttributeGroups)) {
            $grantedAttributes[] = $attribute;
          }
        }
        return $grantedAttributes;
    }
}
