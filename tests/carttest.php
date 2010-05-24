<?php
/**
 * Корзина заказов - тестовый плагин
 *
 * Eresus 2.12
 *
 * Плагин для тестирования корзины заказов
 *
 * @version 1.00
 *
 * @copyright 2010, Eresus Project, http://eresus.ru/
 * @license http://www.gnu.org/licenses/gpl.txt  GPL License 3
 * @author Михаил Красильников
 *
 * Данная программа является свободным программным обеспечением. Вы
 * вправе распространять ее и/или модифицировать в соответствии с
 * условиями версии 2 либо по вашему выбору с условиями более поздней
 * версии Стандартной Общественной Лицензии GNU, опубликованной Free
 * Software Foundation.
 *
 * Мы распространяем эту программу в надежде на то, что она будет вам
 * полезной, однако НЕ ПРЕДОСТАВЛЯЕМ НА НЕЕ НИКАКИХ ГАРАНТИЙ, в том
 * числе ГАРАНТИИ ТОВАРНОГО СОСТОЯНИЯ ПРИ ПРОДАЖЕ и ПРИГОДНОСТИ ДЛЯ
 * ИСПОЛЬЗОВАНИЯ В КОНКРЕТНЫХ ЦЕЛЯХ. Для получения более подробной
 * информации ознакомьтесь со Стандартной Общественной Лицензией GNU.
 *
 * @package cart
 * @subpackage tests
 *
 * $Id: cart.php 336 2010-05-21 14:50:59Z mk $
 */

/**
 * Тестовый плагин
 *
 * @package cart
 * @subpackage tests
 */
class CartTest extends ContentPlugin
{
	/**
	 * Версия плагина
	 * @var string
	 */
	public $version = '1.00';

	/**
	 * Требуемая версия ядра
	 * @var string
	 */
	public $kernel = '2.12';

	/**
	 * Название плагина
	 * @var string
	 */
	public $title = 'Корзина заказов (тестовый плагин)';

	/**
	 * Опиание плагина
	 * @var string
	 */
	public $description = 'Плагин для тестирования корзины заказов';

	/**
	 * Тип плагина
	 * @var string
	 */
	public $type = 'client,content,ondemand';

	/**
	 * Отрисовка контента
	 *
	 * @return string  HTML
	 */
	public function clientRenderContent()
	{
		if (HTTP::request()->getMethod() == 'POST')
			$this->add();

		if (arg('action') == 'clean')
			$this->clean();

		if (arg('action') == 'delete')
			$this->delete();

$html = <<<HTML
<h2>Добавление товара</h2>
<form action="./" method="post">
	<table>
		<tr><td>Класс</td><td><input type="text" name="class" /></td></tr>
		<tr><td>ID</td><td><input type="text" name="id" /></td></tr>
		<tr><td>Цена</td><td><input type="text" name="cost" /></td></tr>
		<tr><td>Кол-во</td><td><input type="text" name="count" /></td></tr>
	</table>
	<div><button type="submit">Добавить</button></div>
</form>
<br /><br />
<table border="1" cellpadding="2">
	<tr><th>Класс</th><th>ID</th><th>Цена</th><th>Кол-во</th><td></td></tr>
HTML;

		$cart = $GLOBALS['Eresus']->plugins->load('cart');

		$items = $cart->fetchItems();
		foreach ($items as $item)
			$html .= "<tr><td>{$item['class']}</td><td>{$item['id']}</td><td>{$item['cost']}</td>".
				"<td>{$item['count']}</td>".
				"<td><a href=\"?action=delete&class={$item['class']}&id={$item['id']}\">X</a></td></tr>";

$html .= <<<HTML
</table>
<a href="./?action=clean">Очистить корзину</a>
HTML;

		return $html;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Добавление товара
	 */
	private function add()
	{
		$cart = $GLOBALS['Eresus']->plugins->load('cart');

		$cart->addItem(
			arg('class'), // в качестве класса товара используем имя текущего плагина
			arg('id'), // идентификатор товара
			arg('count'), // количество заказанных единиц
			arg('cost') // цена товара
		);
		HTTP::goback();
	}
	//-----------------------------------------------------------------------------

	/**
	 * Очистка корзины
	 */
	private function clean()
	{
		$cart = $GLOBALS['Eresus']->plugins->load('cart');

		$cart->clearAll();
		HTTP::goback();
	}
	//-----------------------------------------------------------------------------

	/**
	 * Удаление товара
	 */
	private function delete()
	{
		$cart = $GLOBALS['Eresus']->plugins->load('cart');

		$cart->removeItem(arg('class'), arg('id'));
		HTTP::goback();
	}
	//-----------------------------------------------------------------------------

}