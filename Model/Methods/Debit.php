<?php

/**
 * PAYONE Magento 2 Connector is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PAYONE Magento 2 Connector is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with PAYONE Magento 2 Connector. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2016 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Methods;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

/**
 * Model for debit payment method
 */
class Debit extends PayoneMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_DEBIT;

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string
     */
    protected $sClearingtype = 'elv';

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        $oInfoInstance = $this->getInfoInstance();

        $aParams = [
            'bankcountry' => $oInfoInstance->getAdditionalInformation('bank_country'),
            'iban' => $oInfoInstance->getAdditionalInformation('iban'),
        ];
        if ($oInfoInstance->getAdditionalInformation('bic')) {
            $aParams['bic'] = $oInfoInstance->getAdditionalInformation('bic');
        }

        $aMandate = $this->checkoutSession->getPayoneMandate();
        if ($aMandate && array_key_exists('mandate_identification', $aMandate) !== false &&
            $aMandate['mandate_status'] == 'pending'
        ) {
            $aParams['mandate_identification'] = $aMandate['mandate_identification'];
        }
        $this->checkoutSession->unsPayoneMandate();
        return $aParams;
    }

    /**
     * Add the checkout-form-data to the checkout session
     *
     * @param  DataObject $data
     * @return $this
     */
    public function assignData(DataObject $data)
    {
        parent::assignData($data);

        $oInfoInstance = $this->getInfoInstance();
        $oInfoInstance->setAdditionalInformation('bank_country', $this->toolkitHelper->getAdditionalDataEntry($data, 'bank_country'));
        $oInfoInstance->setAdditionalInformation('iban', $this->toolkitHelper->getAdditionalDataEntry($data, 'iban'));
        $oInfoInstance->setAdditionalInformation('bic', $this->toolkitHelper->getAdditionalDataEntry($data, 'bic'));

        return $this;
    }
}
