<?php

declare(strict_types=1);

namespace Tests\ShopBundle\Test;

use Shopsys\FrameworkBundle\Component\DataFixture\PersistentReferenceFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Environment\EnvironmentType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FunctionalTestCase extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    private $client;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    protected function setUpDomain()
    {
        /** @var \Shopsys\FrameworkBundle\Component\Domain\Domain $domain */
        $this->domain = $this->getContainer()->get(Domain::class);
        $this->domain->switchDomainById(1);
    }

    protected function setUp()
    {
        parent::setUp();
        $this->setUpDomain();
    }

    /**
     * @param bool $createNew
     * @param string $username
     * @param string $password
     * @param array $kernelOptions
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function getClient(
        $createNew = false,
        $username = null,
        $password = null,
        $kernelOptions = []
    ) {
        $defaultKernelOptions = [
            'environment' => EnvironmentType::TEST,
            'debug' => EnvironmentType::isDebug(EnvironmentType::TEST),
        ];

        $kernelOptions = array_replace($defaultKernelOptions, $kernelOptions);

        if ($createNew) {
            $this->client = $this->createClient($kernelOptions);
            $this->setUpDomain();
        } elseif (!isset($this->client)) {
            $this->client = $this->createClient($kernelOptions);
        }

        if ($username !== null) {
            $this->client->setServerParameters([
                'PHP_AUTH_USER' => $username,
                'PHP_AUTH_PW' => $password,
            ]);
        }

        return $this->client;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        return $this->getClient()->getContainer();
    }

    /**
     * @param string $referenceName
     * @return object
     */
    protected function getReference($referenceName)
    {
        /** @var \Shopsys\FrameworkBundle\Component\DataFixture\PersistentReferenceFacade $persistentReferenceFacade */
        $persistentReferenceFacade = $this->getContainer()
            ->get(PersistentReferenceFacade::class);

        return $persistentReferenceFacade->getReference($referenceName);
    }

    protected function skipTestIfFirstDomainIsNotInEnglish()
    {
        if ($this->domain->getDomainConfigById(1)->getLocale() !== 'en') {
            $this->markTestSkipped('Tests for product searching are run only when the first domain has English locale');
        }
    }
}
