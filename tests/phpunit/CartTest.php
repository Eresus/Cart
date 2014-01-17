<?php
/**
 * Тесты класса Cart
 *
 * @version ${product.version}
 *
 * @copyright 2013, Михаил Красильников, <m.krasilnikov@yandex.ru>
 * @license http://www.gnu.org/licenses/gpl.txt	GPL License 3
 * @author Михаил Красильников <m.krasilnikov@yandex.ru>
 *
 * Данная программа является свободным программным обеспечением. Вы
 * вправе распространять ее и/или модифицировать в соответствии с
 * условиями версии 3 либо (по вашему выбору) с условиями более поздней
 * версии Стандартной Общественной Лицензии GNU, опубликованной Free
 * Software Foundation.
 *
 * Мы распространяем эту программу в надежде на то, что она будет вам
 * полезной, однако НЕ ПРЕДОСТАВЛЯЕМ НА НЕЕ НИКАКИХ ГАРАНТИЙ, в том
 * числе ГАРАНТИИ ТОВАРНОГО СОСТОЯНИЯ ПРИ ПРОДАЖЕ и ПРИГОДНОСТИ ДЛЯ
 * ИСПОЛЬЗОВАНИЯ В КОНКРЕТНЫХ ЦЕЛЯХ. Для получения более подробной
 * информации ознакомьтесь со Стандартной Общественной Лицензией GNU.
 *
 * Вы должны были получить копию Стандартной Общественной Лицензии
 * GNU с этой программой. Если Вы ее не получили, смотрите документ на
 * <http://www.gnu.org/licenses/>
 *
 * @package Cart
 * @subpackage Tests
 */

require_once __DIR__ . '/bootstrap.php';

/**
 * Тесты класса Cart
 * @package Cart
 * @subpackage Tests
 */
class CartTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers Cart::clientOnPageRender
     */
    public function testClientOnPageRender()
    {
        new Cart;
        $cart = $this->getMockBuilder('Cart')->setMethods(array('clientRenderBlock'))
            ->disableOriginalConstructor()->getMock();
        $cart->expects($this->once())->method('clientRenderBlock')->will($this->returnValue('foo'));

        $event = $this->getMockBuilder('stdClass')->setMockClassName('Eresus_Event_Render')
            ->setMethods(array('setText', 'getText'))->getMock();
        $event->expects($this->once())->method('getText')
            ->will($this->returnValue(('bla $(Cart) bla')));
        $event->expects($this->once())->method('setText')->with('bla foo bla');
        /** @var Cart $cart */
        /** @var Eresus_Event_Render $event */
        $cart->clientOnPageRender($event);
    }
}

