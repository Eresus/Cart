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
$html = <<<HTML
<h2>���������� ������</h2>
<form action="./" method="post">
	<table>
		<tr><td>ID</td><td><input type="text" name="id" /></td></tr>
		<tr><td>����</td><td><input type="text" name="cost" /></td></tr>
		<tr><td>���-��</td><td><input type="text" name="count" /></td></tr>
	</table>
	<div><button type="submit">��������</button></div>
</form>

<table>
	<tr><th>�����</th><th>ID</th><th>����</th><th>���-��</th></tr>
HTML;

		$cart = $GLOBALS['Eresus']->plugins->load('cart');

		$items = $cart->fetchItems();
		foreach ($items as $item)
			$html .= "<tr><td>{$item['class']}</td><td>{$item['id']}</td><td>{$item['cost']}</td><td>{$item['count']}</td></tr>";

		$html .= '</table>';

		return $html;
	}
	//-----------------------------------------------------------------------------

}