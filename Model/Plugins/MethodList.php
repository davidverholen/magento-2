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

namespace Payone\Core\Model\Plugins;

use Magento\Payment\Model\MethodList as OrigMethodList;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Payone\Core\Model\Source\CreditratingIntegrationEvent as Event;

/**
 * Plugin for Magentos MethodList class
 */
class MethodList
{
    /**
     * PAYONE consumerscore request model
     *
     * @var \Payone\Core\Model\Api\Request\Consumerscore
     */
    protected $consumerscore;

    /**
     * Consumerscore helper
     *
     * @var \Payone\Core\Helper\Consumerscore
     */
    protected $consumerscoreHelper;

    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param \Payone\Core\Model\Api\Request\Consumerscore $consumerscore
     * @param \Payone\Core\Helper\Consumerscore            $consumerscoreHelper
     * @param \Magento\Checkout\Model\Session              $checkoutSession
     */
    public function __construct(
        \Payone\Core\Model\Api\Request\Consumerscore $consumerscore,
        \Payone\Core\Helper\Consumerscore $consumerscoreHelper,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->consumerscore = $consumerscore;
        $this->consumerscoreHelper = $consumerscoreHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Filter methods by the worst score
     *
     * @param  MethodInterface[] $aPaymentMethods
     * @param  string            $sWorstScore
     * @return MethodInterface[]
     */
    protected function filterMethodsByScore($aPaymentMethods, $sWorstScore)
    {
        $aRedMethods = $this->consumerscoreHelper->getAllowedMethodsForScore('R');
        $aYellowMethods = array_merge($aRedMethods, $this->consumerscoreHelper->getAllowedMethodsForScore('Y'));

        $aReturnMethods = [];
        foreach ($aPaymentMethods as $oMethod) {
            if ($sWorstScore == 'Y' && array_search($oMethod->getCode(), $aYellowMethods) !== false) {
                $aReturnMethods[] = $oMethod;
            }

            if ($sWorstScore == 'R' && array_search($oMethod->getCode(), $aRedMethods) !== false) {
                $aReturnMethods[] = $oMethod;
            }
        }
        return $aReturnMethods;
    }

    /**
     * Execute a consumerscore request to PAYONE or load an old score if its lifetime is still active
     *
     * @param  AddressInterface $oShipping
     * @return string
     */
    protected function getScoreByCreditrating(AddressInterface $oShipping)
    {
        $aResponse = $this->consumerscore->sendRequest($oShipping);
        if ($aResponse === true) {// creditrating not executed because of a previous check
            $this->consumerscoreHelper->copyOldStatusToNewAddress($oShipping);
        }

        if (isset($aResponse['score'])) {
            $oShipping->setPayoneProtectScore($aResponse['score'])->save();
        }

        $sScore = $oShipping->getPayoneProtectScore();
        return $sScore;
    }

    /**
     * Get parameter from config
     *
     * @param  string $sParam
     * @param  bool   $blIsAddresscheck
     * @return string
     */
    protected function getConfigParam($sParam, $blIsAddresscheck = false)
    {
        $sGroup = 'creditrating';
        if ($blIsAddresscheck === true) {
            $sGroup = 'address_check';
        }
        return $this->consumerscoreHelper->getConfigParam($sParam, $sGroup, 'payone_protect');
    }

    /**
     * Get quote object from session
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     *
     * @param  OrigMethodList    $subject
     * @param  MethodInterface[] $aPaymentMethods
     * @return MethodInterface[]
     */
    public function afterGetAvailableMethods(OrigMethodList $subject, $aPaymentMethods)
    {
        $oQuote = $this->getQuote();
        $oShipping = $oQuote->getShippingAddress();

        $aScores = [];
        if ($this->getConfigParam('enabled', true)) {// is addresscheck active
            $aScores[] = $oShipping->getPayoneAddresscheckScore();
        }

        $dTotal = $this->getQuote()->getGrandTotal();
        if ($this->consumerscoreHelper->isCreditratingNeeded(Event::BEFORE_PAYMENT, $dTotal) === true) {
            $aScores[] = $this->getScoreByCreditrating($oShipping);
        }

        $sScore = $this->consumerscoreHelper->getWorstScore($aScores);
        if ($sScore != 'G') { // no need to filter
            $aPaymentMethods = $this->filterMethodsByScore($aPaymentMethods, $sScore);
        }

        return $aPaymentMethods;
    }
}