<?php

namespace Balance\Component\Connector\Processor\Denormalization;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Akeneo\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use Akeneo\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use Akeneo\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Pim\Component\Catalog\Builder\ProductBuilderInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Comparator\Filter\ProductFilterInterface;
use Pim\Component\Catalog\Localization\Localizer\AttributeConverterInterface;
use Pim\Component\Connector\ArrayConverter\StandardArrayConverterInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Pim\Component\Connector\Processor\Denormalization\ProductProcessor as BaseProductProcessor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Product import processor, allows to,
 *  - create / update
 *  - validate
 *  - skip invalid ones and detach it
 *  - return the valid ones
 *
 * @author    Maxim Baibakov <maxim@balanceinternet.com.au>
 * @copyright 2015 Balance Internet (http://www.balanceinternet.com.au)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductProcessor extends BaseProductProcessor
{
    /** @var StandardArrayConverterInterface */
    protected $arrayConverter;

    /** @var ProductBuilderInterface */
    protected $builder;

    /** @var ObjectUpdaterInterface */
    protected $updater;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var ObjectDetacherInterface */
    protected $detacher;

    /** @var bool */
    protected $partialImportNonExistentAttributes = true;

    /** @var bool */
    protected $importWithoutCategory = false;

    /** @var string */
    protected $categoriesColumn = 'categories';

    /** @var string */
    protected $familyColumn  = 'family';

    /** @var string */
    protected $groupsColumn  = 'groups';

    /** @var bool */
    protected $enabledComparison = true;

    /** @var ProductFilterInterface */
    protected $productFilter;

    protected $session;

    /**
     * @param StandardArrayConverterInterface       $arrayConverter array converter
     * @param IdentifiableObjectRepositoryInterface $repository     product repository
     * @param ProductBuilderInterface               $builder        product builder
     * @param ObjectUpdaterInterface                $updater        product updater
     * @param ValidatorInterface                    $validator      product validator
     * @param ObjectDetacherInterface               $detacher       detacher to remove it from UOW when skip
     * @param ProductFilterInterface                $productFilter  product filter
     */
    public function __construct(
        StandardArrayConverterInterface $arrayConverter,
        IdentifiableObjectRepositoryInterface $repository,
        ProductBuilderInterface $builder,
        ObjectUpdaterInterface $updater,
        ValidatorInterface $validator,
        ObjectDetacherInterface $detacher,
        ProductFilterInterface $productFilter,
        AttributeConverterInterface $localizedConverter,
        $session
    ) {

        parent::__construct(
            $arrayConverter,
            $repository,
            $builder,
            $updater,
            $validator,
            $detacher,
            $productFilter,
            $localizedConverter
        );

        $this->session = $session;
    }

    public function process($item)
    {
        $username = !empty($this->stepExecution->getJobExecution()->getUser()) ? $this->stepExecution->getJobExecution()->getUser() : 'admin'; // TODO: make the default user configurable

        $this->session->set('job_execution_user', $username);
        
        $convertedItem = $this->convertItemData($item);

        $identifier    = $this->getIdentifier($convertedItem);

        if (null === $identifier) {
            $this->skipItemWithMessage($item, 'The identifier must be filled');
        }

        if (empty($convertedItem['categories']) && !$this->getImportWithoutCategory()) {
            $this->skipItemWithMessage($item, 'The category must be filled');
        }

        $familyCode    = $this->getFamilyCode($convertedItem);
        $filteredItem  = $this->filterItemData($convertedItem);

        $product = $this->findOrCreateProduct($identifier, $familyCode);

        if ($this->enabledComparison && null !== $product->getId()) {
            $filteredItem = $this->filterIdenticalData($product, $filteredItem);

            if (empty($filteredItem)) {
                $this->stepExecution->incrementSummaryInfo('product_skipped_no_diff');

                return null;
            }
        }

        try {
            $this->updateProduct($product, $filteredItem);
        } catch (\InvalidArgumentException $exception) {
            $this->detachProduct($product);
            $this->skipItemWithMessage($item, $exception->getMessage(), $exception);
        }

        $violations = $this->validateProduct($product);
        if ($violations->count() > 0) {
            $this->detachProduct($product);
            $this->skipItemWithConstraintViolations($item, $violations);
        }

        return $product;
    }

    /**
     * @param ProductInterface $product
     * @param array            $filteredItem
     *
     * @return array
     */
    protected function filterIdenticalData(ProductInterface $product, array $filteredItem)
    {
        return $this->productFilter->filter($product, $filteredItem);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
	$configuration = parent::getConfigurationFields();
       
        $configuration['partialImportNonExistentAttributes'] = array (
                'type'    => 'switch',
                'options' => [
                    'label' => 'pim_connector.import.partialImportNonExistentAttributes.label',
                    'help'  => 'pim_connector.import.partialImportNonExistentAttributes.help'
                ]
            );

        $configuration['importWithoutCategory'] = array (
                'type'    => 'switch',
                'options' => [
                    'label' => 'pim_connector.import.importWithoutCategory.label',
                    'help'  => 'pim_connector.import.importWithoutCategory.help'
                ]
        );

        return $configuration;
    }

    /**
     * Set the groups column
     *
     * @param string $partialImportNonExistentAttributes
     */
    public function setPartialImportNonExistentAttributes($partialImportNonExistentAttributes)
    {
        $this->partialImportNonExistentAttributes = $partialImportNonExistentAttributes;
    }

    /**
     * Get the categories column
     *
     * @return string
     */
    public function getPartialImportNonExistentAttributes()
    {
        return $this->partialImportNonExistentAttributes;
    }

    public function setImportWithoutCategory($importWithoutCategory)
    {
        $this->importWithoutCategory = $importWithoutCategory;
    }

    public function getImportWithoutCategory()
    {
        return $this->importWithoutCategory;
    }


    /**
     * Filters item data to remove associations which are imported through a dedicated processor because we need to
     * create any products before to associate them
     *
     * @param array $convertedItem
     *
     * @return array
     */
    protected function filterItemData(array $convertedItem)
    {
        unset($convertedItem[$this->repository->getIdentifierProperties()[0]]);
        unset($convertedItem['associations']);

        return $convertedItem;
    }

    /**
     * @return array
     */ 
    protected function getArrayConverterOptions()
    {
        $options = parent::getArrayConverterOptions();
        $options['partialImportNonExistentAttributes'] = $this->getPartialImportNonExistentAttributes();
        
        return $options;
    }
    

}
