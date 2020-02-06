<?php
declare(strict_types=1);

namespace RoaveTest\PsrContainerDoctrine;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Roave\PsrContainerDoctrine\AbstractFactory;
use Roave\PsrContainerDoctrine\EntityManagerFactory;

class EntityManagerFactoryTest extends TestCase
{
    public function testExtendsAbstractFactory() : void
    {
        $this->assertInstanceOf(AbstractFactory::class, new EntityManagerFactory());
    }

    public function testDefaults() : void
    {
        $connection = $this->buildConnection();
        $configuration = $this->buildConfiguration();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(false);
        $container->has('doctrine.connection.orm_default')->willReturn(true);
        $container->get('doctrine.connection.orm_default')->willReturn($connection);
        $container->has('doctrine.configuration.orm_default')->willReturn(true);
        $container->get('doctrine.configuration.orm_default')->willReturn($configuration);

        $factory = new EntityManagerFactory();
        $entityManager = $factory($container->reveal());

        $this->assertSame($connection, $entityManager->getConnection());
        $this->assertSame($configuration, $entityManager->getConfiguration());
    }

    public function testConfigKeyTakenFromSelf() : void
    {
        $connection = $this->buildConnection();
        $configuration = $this->buildConfiguration();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(false);
        $container->has('doctrine.connection.orm_other')->willReturn(true);
        $container->get('doctrine.connection.orm_other')->willReturn($connection);
        $container->has('doctrine.configuration.orm_other')->willReturn(true);
        $container->get('doctrine.configuration.orm_other')->willReturn($configuration);

        $factory = new EntityManagerFactory('orm_other');
        $entityManager = $factory($container->reveal());

        $this->assertSame($connection, $entityManager->getConnection());
        $this->assertSame($configuration, $entityManager->getConfiguration());
    }

    public function testConfigKeyTakenFromConfig() : void
    {
        $connection = $this->buildConnection();
        $configuration = $this->buildConfiguration();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'doctrine' => [
                'entity_manager' => [
                    'orm_default' => [
                        'connection' => 'orm_foo',
                        'configuration' => 'orm_bar',
                    ],
                ],
            ],
        ]);
        $container->has('doctrine.connection.orm_foo')->willReturn(true);
        $container->get('doctrine.connection.orm_foo')->willReturn($connection);
        $container->has('doctrine.configuration.orm_bar')->willReturn(true);
        $container->get('doctrine.configuration.orm_bar')->willReturn($configuration);

        $factory = new EntityManagerFactory();
        $entityManager = $factory($container->reveal());

        $this->assertSame($connection, $entityManager->getConnection());
        $this->assertSame($configuration, $entityManager->getConfiguration());
    }

    /**
     * @return Connection
     */
    private function buildConnection()
    {
        $connection = $this->prophesize(Connection::class);
        $connection->getEventManager()->willReturn($this->prophesize(EventManager::class)->reveal());

        return $connection->reveal();
    }

    /**
     * @return Configuration
     */
    private function buildConfiguration()
    {
        $configuration = new Configuration();
        $configuration->setMetadataDriverImpl(new MappingDriverChain());
        $configuration->setProxyDir(sys_get_temp_dir());
        $configuration->setProxyNamespace('EntityManagerFactoryTest');

        return $configuration;
    }
}
