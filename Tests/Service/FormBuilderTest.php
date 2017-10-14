<?php

namespace Eghojansu\Bundle\SetupBundle\Tests\Service;

use Symfony\Component\Form\FormInterface;
use Eghojansu\Bundle\SetupBundle\Service\FormBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FormBuilderTest extends KernelTestCase
{
    /** @var Eghojansu\Bundle\SetupBundle\Service\FormBuilder */
    private $formBuilder;

    protected function setUp()
    {
        self::bootKernel();

        $this->formBuilder = self::$kernel->getContainer()->get(FormBuilder::class);
    }

    public function testCreateLoginForm()
    {
        $form = $this->formBuilder->createLoginForm();

        $this->assertInstanceOf(FormInterface::class, $form);
    }

    public function testCreateMaintenanceForm()
    {
        $form = $this->formBuilder->createMaintenanceForm();

        $this->assertInstanceOf(FormInterface::class, $form);
    }

    public function testCreateConfigForm()
    {
        $form = $this->formBuilder->createConfigForm('0.1.0');

        $this->assertInstanceOf(FormInterface::class, $form);
    }
}
