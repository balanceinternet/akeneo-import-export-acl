<?php

namespace Balance\Component\Connector\ArrayConverter\Flat;

use Pim\Component\Connector\ArrayConverter\Flat\ProductStandardConverter as BaseProductStandardConverter;

/**
 * Product Converter
 *
 * @author    Maxim Baibakov <maxim@balanceinternet.com.au>
 * @copyright 2015 Balance Internet (http://www.balanceinternet.com.au)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductStandardConverter extends BaseProductStandardConverter
{
    /** @var array */
    protected $options;

    /** @var array */
    protected $attributeRepository;

    /**
     * @param array $mappedItem
     * @param bool  $withAssociations
     *
     * @return array
     */
    protected function filterFields(array $mappedItem, $withAssociations)
    {
        $mappedItem = parent::filterFields($mappedItem, $withAssociations);
        if (!empty($this->options['partialImportNonExistentAttributes'])) {
          $mappedItem = $this->checkOptionalFields($mappedItem);
        }

        return $mappedItem;
    }

    /**
     * @param array $item
     *
     * @return array
     */
    protected function checkOptionalFields(array $item)
    {

        $optionalFields = array_merge(
            ['family', 'enabled', 'categories', 'groups'],
            $this->attrColumnsResolver->resolveAttributeColumns(),
            $this->getOptionalAssociationFields()
        );

        $unknownFields = [];
        foreach (array_keys($item) as $field) {
            if (!in_array($field, $optionalFields)) {
                unset($item[$field]);
                $unknownFields[] = $field;
            }
        }

        return $item;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function prepareOptions(array $options)
    {
        $this->options = $options;

        $options = parent::prepareOptions($options);

        return $options;
    }

}
