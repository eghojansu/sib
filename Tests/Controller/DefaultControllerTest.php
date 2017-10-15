<?php

namespace Eghojansu\Bundle\SetupBundle\Tests\Controller;

use TestHelper;
use Symfony\Component\Yaml\Yaml;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    /** @var array */
    private $parameters;

    public static function setupBeforeClass()
    {
        TestHelper::prepare();
    }

    protected function getParameter($var)
    {
        if (empty($this->parameters)) {
            $file = TestHelper::varfilepath(TestHelper::FILE_PARAMETERS);
            $parameters = Yaml::parse(file_get_contents($file));

            $this->parameters = $parameters ? $parameters['parameters'] : [];
        }

        return array_key_exists($var, $this->parameters) ? $this->parameters[$var] : null;
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

        return $client;
    }

    /**
     * @depends testIndex
     */
    public function testMaintenance(Client $client)
    {
        $crawler = $client->request('GET', '/maintenance');

        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $crawler->selectButton('Confirm')->form();
        $form['form[maintenance]']->select(true);
        $crawler = $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirection());

        return $client;
    }

    /**
     * @depends testMaintenance
     */
    public function testVersions(Client $client)
    {
        $crawler = $client->request('GET', '/versions');

        $this->assertTrue($client->getResponse()->isSuccessful());

        $version010Link = $crawler->filter('a.install')->first()->link();
        $version020Link = $crawler->filter('a.install')->last()->link();
        $crawler = $client->click($version020Link);

        $this->assertTrue($client->getResponse()->isSuccessful());

        $crawler = $client->click($version010Link);

        $this->assertTrue($client->getResponse()->isSuccessful());

        return $client;
    }

    /**
     * @depends testVersions
     */
    public function testConfig(Client $client)
    {
        $crawler = $client->request('GET', '/versions/0.1.0');

        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $crawler->selectButton('Confirm')->form();
        $form['form[custom_value]'] = 'modified';
        $form['form[other_value]'] = 'modified';
        $form['form[option_value]']->select('three');
        $form['form[Grouped][group_1]'] = 'modified';
        $form['form[Grouped][group_2]'] = 'modified';
        $crawler = $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirection());

        $file = TestHelper::varfilepath(TestHelper::FILE_BY_LISTENER);
        $this->assertFileExists($file);

        $content = file_get_contents($file);
        $this->assertEquals('modified', $content);

        return $client;
    }

    /**
     * @depends testConfig
     */
    public function testPerformed(Client $client)
    {
        $crawler = $client->request('GET', '/versions/0.1.0/performed');

        $this->assertTrue($client->getResponse()->isSuccessful());

        return $client;
    }

    /**
     * @depends testPerformed
     */
    public function testDone(Client $client)
    {
        $crawler = $client->request('GET', '/done');

        $this->assertTrue($client->getResponse()->isRedirection());
    }
}
