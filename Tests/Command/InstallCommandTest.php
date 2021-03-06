<?php

/*
 * This file is part of the SpBowerBundle package.
 *
 * (c) Martin Parsiegla <martin.parsiegla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sp\BowerBundle\Tests\Command;

use Sp\BowerBundle\Command\InstallCommand;
use Sp\BowerBundle\Bower\Configuration;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @author Martin Parsiegla <martin.parsiegla@gmail.com>
 */
class InstallCommandTest extends \PHPUnit_Framework_TestCase
{
    private $application;
    private $definition;
    private $kernel;
    private $container;
    private $command;
    private $bower;
    private $bm;
    private $helperset;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->application = $this->getMockBuilder('Symfony\\Bundle\\FrameworkBundle\\Console\\Application')
            ->disableOriginalConstructor()
            ->getMock();
        $this->definition = $this->getMockBuilder('Symfony\\Component\\Console\\Input\\InputDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $this->kernel = $this->getMock('Symfony\\Component\\HttpKernel\\KernelInterface');
        $this->helperSet = $this->getMock('Symfony\\Component\\Console\\Helper\\HelperSet');
        $this->container = $this->getMock('Symfony\\Component\\DependencyInjection\\ContainerInterface');
        $this->bm = $this->getMockBuilder('Sp\\BowerBundle\\Bower\\BowerManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->bower = $this->getMockBuilder('Sp\\BowerBundle\\Bower\\Bower')
            ->disableOriginalConstructor()
            ->getMock();

        $this->application->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue($this->definition));
        $this->definition->expects($this->any())
            ->method('getArguments')
            ->will($this->returnValue(array()));
        $this->definition->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue(array(
            new InputOption('--verbose', '-v', InputOption::VALUE_NONE, 'Increase verbosity of messages.'),
            new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'),
            new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'Switches off debug mode.'),
        )));

        $this->application->expects($this->any())
            ->method('getKernel')
            ->will($this->returnValue($this->kernel));

        $this->application->expects($this->once())
            ->method('getHelperSet')
            ->will($this->returnValue($this->helperSet));

        $this->kernel->expects($this->any())
            ->method('getContainer')
            ->will($this->returnValue($this->container));

        $this->container->expects($this->at(0))
            ->method('get')
            ->with('sp_bower.bower_manager')
            ->will($this->returnValue($this->bm));

        $this->container->expects($this->at(1))
            ->method('get')
            ->with('sp_bower.bower')
            ->will($this->returnValue($this->bower));

        $this->command = new InstallCommand();
        $this->command->setApplication($this->application);
    }

    public function testEmptyBowerManager()
    {
        $this->bm->expects($this->once())
            ->method('getPaths')
            ->will($this->returnValue(array()));

        $this->command->run(new ArrayInput(array()), new NullOutput());
    }

    public function testInstall()
    {
        $configuration = new Configuration();
        $configuration->setDirectory('/test');
        $configuration->setJsonFile('foo.json');

        $barConfig = new Configuration();
        $paths = array(
            '/foo' => $configuration,
            '/bar' => $barConfig
        );

        $this->bower->expects($this->at(0))->method('init')->with($this->equalTo('/foo'), $this->equalTo($configuration));
        $this->bower->expects($this->at(1))->method('install')->with($this->equalTo('/foo'));
        $this->bower->expects($this->at(2))->method('createDependencyMappingCache')->with($this->equalTo('/foo'));

        $this->bower->expects($this->at(3))->method('init')->with($this->equalTo('/bar'), $this->equalTo($barConfig));
        $this->bower->expects($this->at(4))->method('install')->with($this->equalTo('/bar'));
        $this->bower->expects($this->at(5))->method('createDependencyMappingCache')->with($this->equalTo('/bar'));


        $this->bm->expects($this->once())
            ->method('getPaths')
            ->will($this->returnValue($paths));

        $this->command->run(new ArrayInput(array()), new NullOutput());
    }
}
