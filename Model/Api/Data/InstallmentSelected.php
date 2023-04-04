<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

declare(strict_types=1);

namespace PagBank\PaymentMagento\Model\Api\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use PagBank\PaymentMagento\Api\Data\InstallmentSelectedInterface;

/**
 * Class Installment Selected - Model data.
 */
class InstallmentSelected extends AbstractSimpleObject implements InstallmentSelectedInterface
{
    /**
     * @inheritdoc
     */
    public function getInstallmentSelected()
    {
        return $this->_get(InstallmentSelectedInterface::PAGBANK_INSTALLMENT_SELECTED);
    }

    /**
     * @inheritdoc
     */
    public function setInstallmentSelected($installmentSelected)
    {
        return $this->setData(InstallmentSelectedInterface::PAGBANK_INSTALLMENT_SELECTED, $installmentSelected);
    }
}
