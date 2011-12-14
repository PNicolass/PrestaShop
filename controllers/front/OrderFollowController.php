<?php
/*
* 2007-2011 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2011 PrestaShop SA
*  @version  Release: $Revision: 7095 $
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class OrderFollowControllerCore extends FrontController
{
	public $auth = true;
	public $php_self = 'order-follow';
	public $authRedirection = 'order-follow';
	public $ssl = true;

	/**
	 * Start forms process
	 * @see FrontController::postProcess()
	 */
	public function postProcess()
	{
		if (Tools::isSubmit('submitReturnMerchandise'))
		{
			$customizationQtyInput = Tools::getValue('customization_qty_input');

			if (!$id_order = (int)(Tools::getValue('id_order')))
				Tools::redirect('index.php?controller=history');
			if (!$order_qte_input = Tools::getValue('order_qte_input'))
				Tools::redirect('index.php?controller=order-follow&errorDetail1');
			if ($customizationIds = Tools::getValue('customization_ids') AND !$customizationQtyInput)
				Tools::redirect('index.php?controller=order-follow&errorDetail1');
			if (!$ids_order_detail = Tools::getValue('ids_order_detail') AND !$customizationIds)
				Tools::redirect('index.php?controller=order-follow&errorDetail2');

			$order = new Order((int)($id_order));
			if (!$order->isReturnable()) Tools::redirect('index.php?controller=order-follow&errorNotReturnable');
			if ($order->id_customer != $this->context->customer->id)
				die(Tools::displayError());
			$orderReturn = new OrderReturn();
			$orderReturn->id_customer = (int)$this->context->customer->id;
			$orderReturn->id_order = $id_order;
			$orderReturn->question = strval(Tools::getValue('returnText'));
			if (empty($orderReturn->question))
				Tools::redirect('index.php?controller=order-follow&errorMsg');
			if (!$orderReturn->checkEnoughProduct($ids_order_detail, $order_qte_input, $customizationIds, $customizationQtyInput))
				Tools::redirect('index.php?controller=order-follow&errorQuantity');

			$orderReturn->state = 1;
			$orderReturn->add();
			$orderReturn->addReturnDetail($ids_order_detail, $order_qte_input, $customizationIds, $customizationQtyInput);
			Hook::exec('orderReturn', array('orderReturn' => $orderReturn));
			Tools::redirect('index.php?controller=order-follow');
		}
	}

	/**
	 * Assign template vars related to page content
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		$ordersReturn = OrderReturn::getOrdersReturn($this->context->customer->id);
		if (Tools::isSubmit('errorQuantity'))
			$this->context->smarty->assign('errorQuantity', true);
		elseif (Tools::isSubmit('errorMsg'))
			$this->context->smarty->assign('errorMsg', true);
		elseif (Tools::isSubmit('errorDetail1'))
			$this->context->smarty->assign('errorDetail1', true);
		elseif (Tools::isSubmit('errorDetail2'))
			$this->context->smarty->assign('errorDetail2', true);
		elseif (Tools::isSubmit('errorNotReturnable'))
			$this->context->smarty->assign('errorNotReturnable',true);

		$this->context->smarty->assign('ordersReturn', $ordersReturn);

		$this->setTemplate(_PS_THEME_DIR_.'order-follow.tpl');
		parent::initContent();
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addCSS(_THEME_CSS_DIR_.'history.css');
		$this->addCSS(_THEME_CSS_DIR_.'addresses.css');
		$this->addJqueryPlugin('scrollTo');
		$this->addJS(_THEME_JS_DIR_.'history.js');
	}
}

