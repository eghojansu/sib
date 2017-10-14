<?php

namespace Eghojansu\Bundle\SetupBundle\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Eghojansu\Bundle\SetupBundle\Utils\ArrayHelper;

class ArrayHelperTest extends TestCase
{
    /** @var Eghojansu\Bundle\SetupBundle\Utils\ArrayHelper  */
    private $helper;

    /** @var array */
    private $initial = [
        'a' => 'b',
        'c' => ['d'=>'e'],
        'f',
    ];

    /** @var array */
    private $flatten = [
        'a' => 'b',
        'd' => 'e',
        'f',
    ];

    /** @var array */
    private $swapped = [
        'a' => 'b',
        'c' => ['d'=>'e'],
        'f' => null,
    ];

    /** @var array */
    private $added = [
        'a' => 'b',
        'c' => ['d'=>'e'],
        'f',
        'x' => 'y',
    ];

    protected function setUp()
    {
        $this->helper = ArrayHelper::create($this->initial);
    }

    public function testCreate()
    {
        $this->assertInstanceOf(ArrayHelper::class, $this->helper);
    }

    public function getGetValue()
    {
        $this->assertEquals($this->initial, $this->helper->getValue());
    }

    public function testFlatten()
    {
        $this->helper->flatten();

        $this->assertEquals($this->flatten, $this->helper->getValue());
    }

    public function testSwapNumericKeyWithValue()
    {
        $this->helper->swapNumericKeyWithValue();

        $this->assertEquals($this->swapped, $this->helper->getValue());
    }

    public function testAdd()
    {
        $this->helper->add('x', 'y');

        $this->assertEquals($this->added, $this->helper->getValue());
    }
}
