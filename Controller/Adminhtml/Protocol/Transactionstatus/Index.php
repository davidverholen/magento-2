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

namespace Payone\Core\Controller\Adminhtml\Protocol\Transactionstatus;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\Page as BackendPage;
use Magento\Framework\View\Result\Page;

/**
 * Controller for admin TransactionStatus grid
 */
class Index extends Action
{
    /**
     * Result page factory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Result page
     *
     * @var \Magento\Backend\Model\View\Result\Page
     */
    protected $resultPage;

    /**
     * Constructor
     *
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Return if the user has the needed rights to view this page
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Payone_Core::payone_protocol_transactionstatus');
    }

    /**
     * Returns result page
     *
     * @return BackendPage|Page
     */
    public function execute()
    {
        $this->setPageData();
        return $this->getResultPage();
    }

    /**
     * instantiate result page object
     *
     * @return BackendPage|Page
     */
    public function getResultPage()
    {
        if ($this->resultPage === null) {
            $this->resultPage = $this->resultPageFactory->create();
        }
        return $this->resultPage;
    }

    /**
     * set page data
     *
     * @return $this
     */
    protected function setPageData()
    {
        $resultPage = $this->getResultPage();
        $resultPage->setActiveMenu('Payone_Core::payone_protocol_transaction');
        $resultPage->getConfig()->getTitle()->set((__('Protocol - TransactionStatus')));
        return $this;
    }
}
