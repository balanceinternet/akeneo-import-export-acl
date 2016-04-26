<?php

namespace Balance\Component\Connector\Processor\Denormalization;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Component\Connector\Processor\Denormalization\ProductAssociationProcessor as BaseProductAssociationProcessor;

/**
 * Product association import processor
 *
 * @author    Maxim Baibakov <maxim@balanceinternet.com.au>
 * @copyright 2015 Balance Internet (http://www.balanceinternet.com.au)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductAssociationProcessor extends BaseProductAssociationProcessor
{
    /** @var bool */
    protected $partialImportNonExistentAttributes = true;

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

        return $configuration;
    }

    /**
     * Set the setting column
     *
     * @param string $partialImportNonExistentAttributes
     */
    public function setPartialImportNonExistentAttributes($partialImportNonExistentAttributes)
    {
        $this->partialImportNonExistentAttributes = $partialImportNonExistentAttributes;
    }

    /**
     * Get the setting column
     *
     * @return string
     */
    public function getPartialImportNonExistentAttributes()
    {
        return $this->partialImportNonExistentAttributes;
    }

    /**
     * @param array $item
     *
     * @return array
     */
    protected function convertItemData(array $item)
    {
        $items = $this->arrayConverter->convert($item, $this->getArrayConverterOptions());
        $associations = isset($items['associations']) ? $items['associations'] : [];

        return ['associations' => $associations];
    }

    /**
     * @return array
     */
    protected function getArrayConverterOptions()
    {
        $options['with_associations'] = true;
        $options['partialImportNonExistentAttributes'] = $this->getPartialImportNonExistentAttributes();

        return $options;
    }

}
