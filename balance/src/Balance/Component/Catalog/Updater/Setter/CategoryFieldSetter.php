<?php

namespace Balance\Component\Catalog\Updater\Setter;

use Akeneo\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use Pim\Component\Catalog\Exception\InvalidArgumentException;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Updater\Setter\CategoryFieldSetter as BaseCategoryFieldSetter;
use Pim\Bundle\UserBundle\Provider\UserProvider;
use PimEnterprise\Bundle\SecurityBundle\Attributes;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Sets the category field
 *
 * @author    Maxim Baibakov <maxim@balanceinternet.com.au>
 * @copyright 2015 Balance Internet (http://www.balanceinternet.com.au)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CategoryFieldSetter extends BaseCategoryFieldSetter
{
    /** @var UserProvider */
    protected $userProvider;

    /**
     * @param IdentifiableObjectRepositoryInterface $categoryRepository
     * @param array                                 $supportedFields
     * @param UserProvider                          $userProvider
     */
    public function __construct(
        IdentifiableObjectRepositoryInterface $categoryRepository,
        array $supportedFields,
        EntityManager $entityManager,
        UserProvider $userProvider,
        SessionInterface $session
    ) {
        parent::__construct(
            $categoryRepository,
            $supportedFields
        );

        $this->entityManager = $entityManager;
        $this->userProvider  = $userProvider;
        $this->session       = $session;
    }

    /**
     * {@inheritdoc}
     *
     * Expected data input format : ["category_code"]
     */
    public function setFieldData(ProductInterface $product, $field, $data, array $options = [])
    {
        $user = $this->session->get('job_execution_user');
        if(!empty($user)) {
            $this->setFieldDataACL($product, $field, $data, $options);
        } else {
            parent::setFieldData($product, $field, $data, $options);
        }
    }

    /**
     * {@inheritdoc}
     *
     * Expected data input format : ["category_code"]
     */
    public function setFieldDataACL(ProductInterface $product, $field, $data, array $options = []) 
    {
        
        $this->checkData($field, $data);

        $user = $this->session->get('job_execution_user');
        $user = $this->userProvider->loadUserByUsername($user);
        $categoryAccessRP = $this->entityManager->getRepository('PimEnterpriseSecurityBundle:ProductCategoryAccess');
        $grantCategories = $categoryAccessRP->getGrantedCategoryIds($user, Attributes::EDIT_ITEMS);

        $categories = [];
        foreach ($data as $categoryCode) {
            $category = $this->getCategory($categoryCode);
            if (null === $category) {
                throw InvalidArgumentException::expected(
                    $field,
                    'existing category code',
                    'setter',
                    'category',
                    $categoryCode
                );
            } else {
                if (in_array($category->getId(), $grantCategories)) {
                    $allowToEdit = true;
                }

                $categories[] = $category;
            }
        }

        if (empty($allowToEdit) && !empty($categories)) {
            throw InvalidArgumentException::expected(
                $field,
                'category codes with edit permissions',
                'setter',
                'category',
                $categoryCode
            );
        }

        $oldCategories = $product->getCategories();

        foreach ($oldCategories as $category) {
            if (in_array($category->getId(), $grantCategories)) {
                $grantProductAccess = true;
            }
        }

        if (empty($grantProductAccess) && !empty($oldCategories->count())) {
            throw InvalidArgumentException::expected(
                    $field,
                    'category codes with edit permissions (you do not have access to edit this product)',
                    'setter',
                    'category',
                    $categoryCode
            );
        }

        foreach ($oldCategories as $category) {
            $product->removeCategory($category);
        }

        foreach ($categories as $category) {
            $product->addCategory($category);
        }
    }

}
