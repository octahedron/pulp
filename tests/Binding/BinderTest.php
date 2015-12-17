<?php

/*
 * This file is part of the Pulp package.
 *
 * (c) Octahedron Pty Ltd <andrew@octahedron.com.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octahedron\Pulp\Test\Binding;

use Octahedron\Pulp\Binding\Binder;
use Octahedron\Pulp\Module;
use Octahedron\Pulp\AbstractModule;
use Octahedron\Pulp\Meta\Annotation\Provides;
use Octahedron\Pulp\Meta\Annotation\Singleton;
use Octahedron\Pulp\Scope\Scopes;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationReader;

class BinderTest extends \PHPUnit_Framework_TestCase {

  public function setup() {
    AnnotationRegistry::registerLoader(function($class) {
      $file = __DIR__ . '/../../lib/' . str_replace('\\', '/', substr($class, strlen('Octahedron\\Pulp\\'))) . '.php';
      if (file_exists($file)) return !!include $file;
    });
  }

  public function testBindReturnsBindingObject() {
    $binder = new Binder(new AnnotationReader());
    $this->assertInstanceOf('Octahedron\Pulp\Binding\Binding', $binder->bind('TestInterface'));
  }

  public function testModuleInstall() {
    $moduleMock = $this->getMockBuilder('Octahedron\Pulp\Module')
        ->disableOriginalConstructor()
        ->setMethods(['configure'])
        ->getMock();
    $binder = new Binder(new AnnotationReader());
    $moduleMock->expects($this->once())->method('configure');

    $binder->install($moduleMock);
  }

  public function testSameModuleInstallsOnlyOnce() {
    $moduleMock = $this->getMockBuilder('Octahedron\Pulp\Module')
        ->setMethods(['configure'])
        ->getMock();
    $binder = new Binder(new AnnotationReader());
    $moduleMock->expects($this->once())->method('configure');

    $binder->install($moduleMock);
    $binder->install($moduleMock);
  }

  public function testRetrievePreviousBinding() {
    $binder = new Binder(new AnnotationReader());
    $binding = $binder->bind('TestInterface');
    $this->assertSame($binding, $binder->getBindingFor('TestInterface'));
  }

  public function testBindsModuleProviderMethod() {
    $bindingMock = $this->getMockBuilder('Octahedron\Pulp\Binding\Binding')
        ->disableOriginalConstructor()
        ->setMethods(['toProvider'])
        ->getMock();
    $bindingMock->expects($this->once())
       ->method('toProvider')
       ->with($this->isInstanceOf('Octahedron\Pulp\Provider\ProviderMethod'));

    $binderStub = $this->getMockBuilder('Octahedron\Pulp\Binding\Binder')
        ->setConstructorArgs([new AnnotationReader()])
        ->setMethods(['bind'])
        ->getMock();
    $binderStub->expects($this->once())
         ->method('bind')
         ->will($this->returnValue($bindingMock));

    $binderStub->install(new TestModule($binderStub));
  }

  public function testBindsModuleSingletonProviderMethod() {
    $bindingMock = $this->getMockBuilder('Octahedron\Pulp\Binding\Binding')
        ->disableOriginalConstructor()
        ->setMethods(['toProvider', 'in'])
        ->getMock();
    $bindingMock->expects($this->once())
       ->method('toProvider')
       ->with($this->isInstanceOf('Octahedron\Pulp\Provider\ProviderMethod'))
       ->will($this->returnValue($bindingMock));
    $bindingMock->expects($this->once())
         ->method('in')
         ->with($this->identicalTo(Scopes::singleton()));

    $binderStub = $this->getMockBuilder('Octahedron\Pulp\Binding\Binder')
        ->setConstructorArgs([new AnnotationReader()])
        ->setMethods(['bind'])
        ->getMock();
    $binderStub->expects($this->once())
         ->method('bind')
         ->will($this->returnValue($bindingMock));

    $binderStub->install(new TestSingletonModule($binderStub));
  }

  public function testFactoryProviderInstall() {
    $annotationReaderMock = $this->getMock('Doctrine\Common\Annotations\Reader');

    $factoryProviderMock = $this->getMockBuilder('Octahedron\Pulp\Assisted\FactoryProvider')
        ->disableOriginalConstructor()
        ->setMethods(['setAnnotationReader'])
        ->getMock();
    $factoryProviderMock->expects($this->once())
        ->method('setAnnotationReader')
        ->with($this->identicalTo($annotationReaderMock));

    $binder = new Binder($annotationReaderMock);
    $binder->installFactoryProvider($factoryProviderMock);
  }

}

class TestModule extends AbstractModule {

  public function configure() {}

  /**
   * @Provides("TestImplementation")
   */
  public function testProvider() {}

}

class TestSingletonModule extends AbstractModule {

  public function configure() {}

  /**
   * @Provides("TestImplementation")
   * @Singleton
   */
  public function testProvider() {}

}
