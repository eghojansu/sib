<?php

namespace Eghojansu\Bundle\SetupBundle\Tests\Service;

use TestHelper;
use Eghojansu\Bundle\SetupBundle\Service\Setup;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SetupTest extends KernelTestCase
{
    /** @var Eghojansu\Bundle\SetupBundle\Service\Setup */
    private $setup;

    protected function setUp()
    {
        TestHelper::prepare();

        self::bootKernel();
        $this->setup = self::$kernel->getContainer()->get(Setup::class);
    }

    public function testIsMaintenance()
    {
        $this->assertFalse($this->setup->isMaintenance());
    }

    public function testSetMaintenance()
    {
        $this->setup->setMaintenance(true);
        $this->assertTrue($this->setup->isMaintenance());
    }

    public function testIsAuthenticated()
    {
        $this->assertFalse($this->setup->isAuthenticated());
    }

    public function testSetAuthenticated()
    {
        $this->setup->setAuthenticated(true);
        $this->assertTrue($this->setup->isAuthenticated());
    }

    public function testGetParameter()
    {
        $this->assertEquals('ThisTokenIsNotSoSecretChangeIt', $this->setup->getParameter('secret'));
    }

    public function testGetConfig()
    {
        $this->assertEquals('welcome', $this->setup->getConfig('passphrase'));
    }

    public function testGetVersions()
    {
        $versions = $this->setup->getVersions();
        $this->assertCount(2, $versions);
        $this->assertContains('0.1.0', $versions['0.1.0']);
    }

    public function testGetVersion()
    {
        $version = $this->setup->getVersion('0.2.0');
        $this->assertContains('0.2.0', $version);
    }

    public function testIsVersionInstalled()
    {
        $this->assertFalse($this->setup->isVersionInstalled('0.1.0'));
    }

    public function testIsPreviousVersionInstalled()
    {
        $this->assertTrue($this->setup->isPreviousVersionInstalled('0.1.0'));
        $this->assertFalse($this->setup->isPreviousVersionInstalled('0.2.0'));
    }

    public function testGetYamlContent()
    {
        $file = TestHelper::varfilepath(TestHelper::FILE_PARAMETERS);

        $content = $this->setup->getYamlContent($file, 'parameters');
        $this->assertContains('ThisTokenIsNotSoSecretChangeIt', $content);
    }

    public function testSetYamlContent()
    {
        $file = TestHelper::varfilepath(TestHelper::FILE_BY_SETUP);

        $data = ['data'=>'data'];
        $this->setup->setYamlContent($file, $data);

        $this->assertFileExists($file);
    }

    public function testUpdateParameters()
    {
        $vConfig = $this->setup->getVersion('0.1.0');

        $data = ['data'=>'dataxxxxx'];
        $this->setup->updateParameters('0.1.0', $data);

        $this->assertFileExists($vConfig['parameters']['destination']);

        $content = file_get_contents($vConfig['parameters']['destination']);
        $this->assertContains('dataxxxxx', $content);
    }

    public function testRecordSetupHistory()
    {
        $this->setup->recordSetupHistory('0.1.0');

        $file = TestHelper::varfilepath(Setup::HISTORY_FILENAME);
        $this->assertFileExists($file);

        $content = $this->setup->getYamlContent($file, Setup::HISTORY_INSTALLED_KEY);
        $this->assertCount(1, $content);
        $this->assertContains('0.1.0', $content[0]);
    }

    public function testGetFile()
    {
        $expected = TestHelper::varfilepath(Setup::HISTORY_FILENAME);

        $this->assertContains(dirname($expected), $this->setup->getFile(Setup::HISTORY_FILENAME));
    }
}
