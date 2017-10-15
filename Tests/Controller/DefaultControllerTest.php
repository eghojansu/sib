<?php

namespace Eghojansu\Bundle\SetupBundle\Tests\Controller;

use TestHelper;
use Symfony\Bundle\FrameworkBundle\Client;
use Eghojansu\Bundle\SetupBundle\Service\Setup;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function setUp()
    {
        TestHelper::prepare();
    }

    private function getAuthClient($maintenance = false)
    {
        $kernel = static::bootKernel();
        $setup = $kernel->getContainer()->get(Setup::class);
        $setup->setAuthenticated(true);
        $setup->setMaintenance($maintenance);
        $client = $kernel->getContainer()->get('test.client');

        return $client;
    }

    public function testIndex()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $crawler->selectButton('Confirm')->form();
        $form['form[passphrase]'] = 'welcome';
        $crawler = $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirection());
    }

    public function testMaintenance()
    {
        $client = $this->getAuthClient();
        $crawler = $client->request('GET', '/maintenance');

        $this->assertTrue($client->getResponse()->isSuccessful());

        $doneLink = $crawler->selectLink('Done')->link();
        $this->assertTrue(true);

        $form = $crawler->selectButton('Confirm')->form();
        $form['form[maintenance]']->select(true);
        $crawler = $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirection());

        $setup = $client->getContainer()->get(Setup::class);
        $this->assertTrue($setup->isMaintenance());
    }

    public function testVersions()
    {
        $client = $this->getAuthClient(true);
        $crawler = $client->request('GET', '/versions');

        $this->assertTrue($client->getResponse()->isSuccessful());

        $doneLink = $crawler->selectLink('Done')->link();
        $this->assertTrue(true);

        $version010Link = $crawler->filter('a.install')->first()->link();
        $version020Link = $crawler->filter('a.install')->last()->link();
        $crawler = $client->click($version020Link);

        $this->assertTrue($client->getResponse()->isSuccessful());

        $crawler = $client->click($version010Link);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testPassphrase()
    {
        $client = $this->getAuthClient(true);
        $crawler = $client->request('GET', '/versions/0.1.0/passphrase');

        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $crawler->selectButton('Confirm')->form();
        $form['form[old_passphrase]'] = 'welcome';
        $form['form[new_passphrase][first]'] = 'change';
        $form['form[new_passphrase][second]'] = 'change';
        $crawler = $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirection());

        $content = $client->getResponse()->getContent();
        $this->assertContains('Redirecting to /done', $content);

        $setup = $client->getContainer()->get(Setup::class);
        $this->assertEquals('change', $setup->getPassphrase());
    }

    public function testConfig()
    {
        $client = $this->getAuthClient(true);
        $crawler = $client->request('GET', '/versions/0.1.0');

        $this->assertTrue($client->getResponse()->isSuccessful());

        $doneLink = $crawler->selectLink('Done')->link();
        $passphraseLink = $crawler->selectLink('Change passphrase')->link();
        $this->assertTrue(true);

        $form = $crawler->selectButton('Confirm')->form();
        $form['form[custom_value]'] = 'modifiedxxx';
        $form['form[other_value]'] = 'modified';
        $form['form[option_value]']->select('three');
        $form['form[Grouped][group_1]'] = 'modified';
        $form['form[Grouped][group_2]'] = 'modified';
        $crawler = $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirection());

        $file = TestHelper::varfilepath(TestHelper::FILE_BY_LISTENER);
        $this->assertFileExists($file);

        $content = file_get_contents($file);
        $this->assertEquals('modifiedxxx', $content);
    }


    public function testPerformed()
    {
        $client = $this->getAuthClient(true);
        $crawler = $client->request('GET', '/versions/0.1.0/performed');

        $this->assertTrue($client->getResponse()->isSuccessful());

        $doneLink = $crawler->selectLink('Done')->link();
        $this->assertTrue(true);
    }

    public function testDone()
    {
        $client = $this->getAuthClient(true);
        $crawler = $client->request('GET', '/done');

        $this->assertTrue($client->getResponse()->isRedirection());

        $setup = $client->getContainer()->get(Setup::class);
        $this->assertFalse($setup->isAuthenticated());
    }
}
