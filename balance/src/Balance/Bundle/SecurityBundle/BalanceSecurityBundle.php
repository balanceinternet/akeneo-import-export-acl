<?php

/*
 * This file is part of the Akeneo PIM Enterprise Edition.
 *
 * (c) 2014 Akeneo SAS (http://www.akeneo.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Balance\Bundle\SecurityBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Enterprise Security Bundle
 *
 * @author    Maxim Baibakov <maxim@balanceinternet.com.au>
 * @copyright 2015 Balance Internet (http://www.balanceinternet.com.au)
 */
class BalanceSecurityBundle extends Bundle
{

    public function getParent() 
    {
        return 'PimEnterpriseSecurityBundle';    
    }

}
