<?php
namespace tests\Html;

use Graphite\Helper\Html;

class HtmlTest extends \PHPUnit_Framework_TestCase
{
    public function testRenderAttrs()
    {
        $expected = 'foo="bar" name="zoo" checked';
        $actual   = Html::renderAttrs(['foo' => 'bar', 'name' => "zoo", "checked"]);
        $this->assertEquals($expected, $actual);
    }
}
