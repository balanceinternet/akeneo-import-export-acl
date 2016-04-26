<?php

namespace Balance\Bundle\CatalogBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Balance Catalog Bundle
 *
 * @author    Maxim Baibakov <maxim@balanceinternet.com.au>
 * @copyright 2015 Balance Internet (http://www.balanceinternet.com.au)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class BalanceCatalogBundle extends Bundle
{
    public function getParent()
    {
        return 'PimEnterpriseCatalogBundle';
    }

}
