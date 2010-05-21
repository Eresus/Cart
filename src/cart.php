<?php
/**
 * ������� �������
 *
 * Eresus 2.12
 *
 * ���� ������� ������� � API ��� ���������� / �������� �������
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
 *
 * $Id$
 */

/**
 * ������� �������
 *
 * @package cart
 */
class Cart extends Plugin
{
	/**
	 * ������ �������
	 * @var string
	 */
	public $version = '1.00a';

	/**
	 * ��������� ������ ����
	 * @var string
	 */
	public $kernel = '2.12';

	/**
	 * �������� �������
	 * @var string
	 */
	public $title = '������� �������';

	/**
	 * ������� �������
	 * @var string
	 */
	public $description = '���� ���������� �������';

	/**
	 * ��� �������
	 * @var string
	 */
	public $type = 'client';

	/**
	 * ���������
	 * @var array
	 */
	public $settings = array(
		// ����� ����� cookie � ����
		'cookieLifeTime' => 3,
	);

	/**
	 * ���������� �������
	 * @var array
	 */
	private $items = array();

	/**
	 * �����������
	 *
	 * @return Cart
	 */
	public function __construct()
	{
		global $Eresus;

		parent::__construct();

		$this->listenEvents('clientOnPageRender');

		Core::setValue('core.template.templateDir', $Eresus->froot);
    Core::setValue('core.template.compileDir', $Eresus->fdata . 'cache');
    Core::setValue('core.template.charset', 'windows-1251');

    $this->loadFromCookies();
	}
	//-----------------------------------------------------------------------------

	/**
	 * ����������
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this->saveToCookies();
	}
	//-----------------------------------------------------------------------------

	/**
	 * ��������� �������
	 *
	 * @return void
	 */
	public function install()
	{
		global $Eresus;

		parent::install();

		$umask = umask(0000);
		@mkdir($Eresus->fdata . 'cache');
		umask($umask);

		/* �������� ������� */
		$target = $Eresus->froot . 'templates/' . $this->name;
		if (!FS::isDir($target))
		{
			$umask = umask(0000);
			mkdir($target, 0777);
			umask($umask);
		}
		$files = glob($this->dirCode . 'templates/*.html');
		foreach ($files as $file)
			copy($file, $target . '/' . basename($file));
	}
	//-----------------------------------------------------------------------------

	/**
	 * �������� �������
	 *
	 * @return void
	 */
	public function uninstall()
	{
		global $Eresus;

		useLib('templates');
		$templates = new Templates();

		/* ������� ������� */
		$list = $templates->enum($this->name);
		foreach ($list as $name => $desc)
			$templates->delete($name, $this->name);

		@rmdir($Eresus->froot . 'templates/' . $this->name);

		parent::uninstall();
	}
	//-----------------------------------------------------------------------------

	/**
	 * ��������� ����� �������
	 *
	 * @param string $html  HTML
	 * @return string  HTML
	 */
	public function clientOnPageRender($html)
	{
		// ��������� JS
		$block = $this->clientRenderBlock();
		$html = preg_replace('/\$\(cart\)/i', $block, $html);
		return $html;
	}
	//-----------------------------------------------------------------------------

	/**
	 * ��������� ����� � �������
	 *
	 * @param string $class            ����� ������ (����� ������� �������)
	 * @param string $id               ������������� ������
	 * @param int    $count[optional]  ���������� ����������� �������
	 * @param float  $cost[optional]   ��������� ������ ������
	 */
	public function addItem($class, $id, $count = 1, $cost = 0)
	{
		/* ��������� ����� �������, ���� ��� ��� ��� */
		if (!isset($this->items[$class]))
			$this->items[$class] = array();

		/* ��������� �����, ���� ��� ��� ��� */
		if (!isset($this->items[$class][$id]))
			$this->items[$class][$id] = array(
				'cost' => $cost,
				'count' => 0
			);

		// ��������� ������
		$this->items[$class][$id]['count'] += $count;
	}
	//-----------------------------------------------------------------------------

	/**
	 * ���������� ���������� �������
	 *
	 * @param string $class[optional]
	 * @return array()
	 */
	public function fetchItems($class = null)
	{
		$items = array();

		if ($class !== null)
		{
			if (!isset($this->items[$class]))
				return array();

			foreach ($this->items[$class] as $id => $item)
				$items []= array(
					'class' => $class,
					'id' => $id,
					'count' => $item['count'],
					'cost' => $item['cost']
				);
			return $items;
		}

		$classes = array_keys($this->items);
		foreach ($classes as $class)
			$items = array_merge($items, $this->fetchItems($class));

		return $items;
	}
	//-----------------------------------------------------------------------------

	/**
	 * ������������ ���� �������
	 *
	 * @return string  HTML
	 */
	private function clientRenderBlock()
	{
		global $Eresus;

		$tmpl = new Template('templates/' . $this->name . '/block.html');

		$data = array('count' => 0, 'sum' => 0);

		foreach ($this->items as $class)
			foreach ($class as $item)
			{
				$data['count'] += $item['count'];
				$data['sum'] += $item['cost'] * $item['count'];
			}

		$html = $tmpl->compile($data);
		return $html;
	}
	//-----------------------------------------------------------------------------

	/**
	 * ��������� ���������� ������� �� cookie
	 *
	 * @return void
	 */
	private function loadFromCookies()
	{
		$this->items = array();

		if (isset($_COOKIE[$this->name]))
		{
			@$items = unserialize($_COOKIE[$this->name]);

			/* ���������, ������ �� �������������� */
			if ($items === false)
			{
				eresus_log(__METHOD__, LOG_NOTICE, 'Cannot unserialize cookie value: "%s"',
					$_COOKIE[$this->name]);
				return;
			}

			// ���������� ���������
			$this->items = $items;
		}

	}
	//-----------------------------------------------------------------------------

	/**
	 * ��������� ���������� ������� � cookie
	 *
	 * @return void
	 */
	private function saveToCookies()
	{
		$value = serialize($this->items);
		setcookie($this->name, $value, time() + $this->settings['cookieLifeTime'] * 60 * 60 * 24, '/');
	}
	//-----------------------------------------------------------------------------

}