<?php

namespace SS6\ShopBundle\Controller\Front;

use SS6\ShopBundle\Form\Front\Order\OrderFormData;
use SS6\ShopBundle\Model\Customer\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;

class OrderController extends Controller {

	const SESSION_CREATED_ORDER = 'created_order_id';

	public function indexAction() {
		$paymentRepository = $this->get('ss6.shop.payment.payment_repository');
		/* @var $paymentRepository \SS6\ShopBundle\Model\Payment\PaymentRepository */

		$transportRepository = $this->get('ss6.shop.transport.transport_repository');
		/* @var $transportRepository \SS6\ShopBundle\Model\Transport\TransportRepository */

		$orderFacade = $this->get('ss6.shop.order.order_facade');
		/* @var $orderFacade \SS6\ShopBundle\Model\Order\OrderFacade */

		$cart = $this->get('ss6.shop.cart');
		/* @var $cart \SS6\ShopBundle\Model\Cart\Cart */

		if ($cart->isEmpty()) {
			return $this->redirect($this->generateUrl('front_cart'));
		}

		$payments = $paymentRepository->getVisible();
		$transports = $transportRepository->getVisible($payments);
		$user = $this->getUser();

		$formData = new OrderFormData();
		if ($user instanceof User) {
			$orderFacade->prefillOrderFormData($formData, $user);
		}

		$flow = $this->get('ss6.shop.order.flow');
		/* @var $flow \SS6\ShopBundle\Form\Front\Order\OrderFlow */

		$flow->setFormTypesData($transports, $payments);
		$flow->saveSentStepData();

		if ($flow->isBackToCartTransition()) {
			return $this->redirect($this->generateUrl('front_cart'));
		}

		$flow->bind($formData);

		// validate all constraints (not only step specific group)
		$form = $flow->createForm(array('validation_groups' => array('Default')));

		if ($flow->isValid($form)) {
			if ($flow->nextStep()) {
				$form = $flow->createForm();
			} else {
				$order = $orderFacade->createOrder($formData, $this->getUser());

				$flow->reset();

				try {
					$orderMailFacade = $this->get('ss6.shop.order.order_mail_facade');
					/* @var $orderMailFacade \SS6\ShopBundle\Model\Order\Mail\OrderMailFacade */
					$orderMailFacade->sendEmail($order);
				} catch (\SS6\ShopBundle\Model\Order\Mail\Exception\SendMailFailedException $e) {
					$flashMessage = $this->get('ss6.shop.flash_message.front');
					/* @var $flashMessage \SS6\ShopBundle\Model\FlashMessage\FlashMessage */
					$flashMessage->addError('Nepodařilo se odeslat některé emaily, pro ověření objednávky nás prosím kontaktujte.');
				}
				
				$session = $this->get('session');
				/* @var $session \Symfony\Component\HttpFoundation\Session\Session */
				$session->set(self::SESSION_CREATED_ORDER, $order->getId());

				return $this->redirect($this->generateUrl('front_order_sent'));
			}
		}

		if ($form->isSubmitted() && !$form->isValid() && empty($form->getErrors())) {
			$form->addError(new FormError('Prosím zkontrolujte si správnost vyplnění všech údajů'));
		}

		return $this->render('@SS6Shop/Front/Content/Order/index.html.twig', array(
			'form' => $form->createView(),
			'flow' => $flow,
			'payments' => $payments,
		));
	}

	public function sentAction() {
		$session = $this->get('session');
		/* @var $session \Symfony\Component\HttpFoundation\Session\Session */
		$orderId = $session->get(self::SESSION_CREATED_ORDER, null);
		$session->remove(self::SESSION_CREATED_ORDER);

		if ($orderId === null) {
			return $this->redirect($this->generateUrl('front_cart'));
		}

		return $this->render('@SS6Shop/Front/Content/Order/sent.html.twig');
	}

}
