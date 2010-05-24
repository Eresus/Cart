<?php
/**
 * ������� ������� - �������� ������
 *
 * Eresus 2.12
 *
 * ������ ��� ������������ ������� �������
 *
 * @version 1.00
 *
 * @copyright 2010, Eresus Project, http://eresus.ru/
 * @license http://www.gnu.org/licenses/gpl.txt  GPL License 3
 * @author ������ ������������
 *
 * ������ ��������� �������� ��������� ����������� ������������. ��
 * ������ �������������� �� �/��� �������������� � ������������ �
 * ��������� ������ 2 ���� �� ������ ������ � ��������� ����� �������
 * ������ ����������� ������������ �������� GNU, �������������� Free
 * Software Foundation.
 *
 * �� �������������� ��� ��������� � ������� �� ��, ��� ��� ����� ���
 * ��������, ������ �� ������������� �� ��� ������� ��������, � ���
 * ����� �������� ��������� ��������� ��� ������� � ����������� ���
 * ������������� � ���������� �����. ��� ��������� ����� ���������
 * ���������� ������������ �� ����������� ������������ ��������� GNU.
 *
 * @package cart
 * @subpackage tests
 *
 * $Id: cart.php 336 2010-05-21 14:50:59Z mk $
 */

/**
 * �������� ������
 *
 * @package cart
 * @subpackage tests
 */
class CartTest extends ContentPlugin
{
	/**
	 * ������ �������
	 * @var string
	 */
	public $version = '1.00';

	/**
	 * ��������� ������ ����
	 * @var string
	 */
	public $kernel = '2.12';

	/**
	 * �������� �������
	 * @var string
	 */
	public $title = '������� ������� (�������� ������)';

	/**
	 * ������� �������
	 * @var string
	 */
	public $description = '������ ��� ������������ ������� �������';

	/**
	 * ��� �������
	 * @var string
	 */
	public $type = 'client,content,ondemand';

	/**
	 * ��������� ��������
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
<h2>���������� ������</h2>
<form action="./" method="post">
	<table>
		<tr><td>�����</td><td><input type="text" name="class" /></td></tr>
		<tr><td>ID</td><td><input type="text" name="id" /></td></tr>
		<tr><td>����</td><td><input type="text" name="cost" /></td></tr>
		<tr><td>���-��</td><td><input type="text" name="count" /></td></tr>
	</table>
	<div><button type="submit">��������</button></div>
</form>
<br /><br />
<table border="1" cellpadding="2">
	<tr><th>�����</th><th>ID</th><th>����</th><th>���-��</th><td></td></tr>
HTML;

		$cart = $GLOBALS['Eresus']->plugins->load('cart');

		$items = $cart->fetchItems();
		foreach ($items as $item)
			$html .= "<tr><td>{$item['class']}</td><td>{$item['id']}</td><td>{$item['cost']}</td>".
				"<td>{$item['count']}</td>".
				"<td><a href=\"?action=delete&class={$item['class']}&id={$item['id']}\">X</a></td></tr>";

$html .= <<<HTML
</table>
<a href="./?action=clean">�������� �������</a>
HTML;

		return $html;
	}
	//-----------------------------------------------------------------------------

	/**
	 * ���������� ������
	 */
	private function add()
	{
		$cart = $GLOBALS['Eresus']->plugins->load('cart');

		$cart->addItem(
			arg('class'), // � �������� ������ ������ ���������� ��� �������� �������
			arg('id'), // ������������� ������
			arg('count'), // ���������� ���������� ������
			arg('cost') // ���� ������
		);
		HTTP::goback();
	}
	//-----------------------------------------------------------------------------

	/**
	 * ������� �������
	 */
	private function clean()
	{
		$cart = $GLOBALS['Eresus']->plugins->load('cart');

		$cart->clearAll();
		HTTP::goback();
	}
	//-----------------------------------------------------------------------------

	/**
	 * �������� ������
	 */
	private function delete()
	{
		$cart = $GLOBALS['Eresus']->plugins->load('cart');

		$cart->removeItem(arg('class'), arg('id'));
		HTTP::goback();
	}
	//-----------------------------------------------------------------------------

}