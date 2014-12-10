<?php

namespace SS6\ShopBundle\Model\Payment;

use Doctrine\ORM\EntityManager;
use SS6\ShopBundle\Model\Payment\Payment;
use SS6\ShopBundle\Model\Pricing\Vat\Vat;
use SS6\ShopBundle\Model\Transport\Transport;

class PaymentRepository {

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @param \Doctrine\ORM\EntityManager $em
	 */
	public function __construct(EntityManager $em) {
		$this->em = $em;
	}

	/**
	 * @return \Doctrine\ORM\EntityRepository
	 */
	private function getPaymentRepository() {
		return $this->em->getRepository(Payment::class);
	}

	/**
	 * @return \Doctrine\ORM\EntityRepository
	 */
	private function getPaymentDomainRepository() {
		return $this->em->getRepository(PaymentDomain::class);
	}

	/**
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getQueryBuilderForAll() {
		$qb = $this->getPaymentRepository()->createQueryBuilder('p')
			->where('p.deleted = :deleted')->setParameter('deleted', false)
			->orderBy('p.position')
			->addOrderBy('p.id');
		return $qb;
	}

	/**
	 * @return array
	 */
	public function findAll() {
		return $this->getQueryBuilderForAll()->getQuery()->getResult();
	}

	/**
	 * @return array
	 */
	public function findAllIncludingDeleted() {
		return $this->getPaymentRepository()->findAll();
	}

	/**
	 * @param int $id
	 * @return \SS6\ShopBundle\Model\Payment\Payment|null
	 */
	public function findById($id) {
		return $this->getPaymentRepository()->find($id);
	}

	/**
	 * @param int $id
	 * @return \SS6\ShopBundle\Model\Payment\Payment
	 */
	public function getById($id) {
		$payment = $this->findById($id);
		if ($payment === null) {
			throw new Exception\PaymentNotFoundException(array('id' => $id));
		}
		return $payment;
	}

	/**
	 * @param int $id
	 * @return \SS6\ShopBundle\Model\Payment\Payment
	 */
	public function getByIdWithTransports($id) {
		$criteria = array('id' => $id);
		try {
			$dql = sprintf('SELECT p, t FROM %s p LEFT JOIN p.transports t WHERE p.id = :id', Payment::class);
			return $this->em->createQuery($dql)->setParameters($criteria)->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			throw new Exception\PaymentNotFoundException($criteria, $e);
		}
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function findAllWithTransports() {
		return $this->getQueryBuilderForAll()
			->leftJoin(Transport::class, 't')
			->getQuery()
			->getResult();
	}

	/**
	 * @param \SS6\ShopBundle\Model\Transport\Transport $transport
	 * @return array
	 */
	public function findAllByTransport(Transport $transport) {
		return $this->getQueryBuilderForAll()
			->join(Transport::class, 't')
			->andWhere('t.id = :transportId')
			->setParameter('transportId', $transport->getId())
			->getQuery()
			->getResult();
	}

	/**
	 * @param \SS6\ShopBundle\Model\Pricing\Vat\Vat $vat
	 * @return \SS6\ShopBundle\Model\Payment\Payment[]
	 */
	public function getAllIncludingDeletedByVat(Vat $vat) {
		return $this->getPaymentRepository()->findBy(array('vat' => $vat));
	}

	/**
	 * @param \SS6\ShopBundle\Model\Payment\Payment $payment
	 * @return \SS6\ShopBundle\Model\Payment\PaymentDomain[]
	 */
	public function getPaymentDomainsByPayment(Payment $payment) {
		return $this->getPaymentDomainRepository()->findBy(array('payment' => $payment));
	}

}
