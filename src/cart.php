<?php
/**
 * Корзина заказов
 *
 * Блок корзины заказов с API для добавления / удаления товаров
 *
 * @version ${product.version}
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
 * @package Cart
 *
 * $Id$
 */

/**
 * Корзина заказов
 *
 * @package cart
 */
class Cart extends Plugin
{
	/**
	 * Версия плагина
	 * @var string
	 */
	public $version = '${product.version}';

	/**
	 * Требуемая версия ядра
	 * @var string
	 */
	public $kernel = '2.12';

	/**
	 * Название плагина
	 * @var string
	 */
	public $title = 'Корзина заказов';

	/**
	 * Опиание плагина
	 * @var string
	 */
	public $description = 'Блок заказанных товаров';

	/**
	 * Тип плагина
	 * @var string
	 */
	public $type = 'client';

	/**
	 * Настройки
	 * @var array
	 */
	public $settings = array(
		// Время жизни cookie в днях
		'cookieLifeTime' => 3,
	);

	/**
	 * Содержимое корзины
	 * @var array
	 */
	private $items = array();

	/**
	 * Конструктор
	 *
	 * @return Cart
	 */
	public function __construct()
	{
		global $Eresus;

		parent::__construct();

		$this->listenEvents('clientOnStart', 'clientOnPageRender');

		Core::setValue('core.template.templateDir', $Eresus->froot);
		Core::setValue('core.template.compileDir', $Eresus->fdata . 'cache');
		Core::setValue('core.template.charset', 'windows-1251');

		$this->loadFromCookies();
	}
	//-----------------------------------------------------------------------------

	/**
	 * Деструктор
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this->saveToCookies();
	}
	//-----------------------------------------------------------------------------

	/**
	 * Установка плагина
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

		/* Копируем шаблоны */
		$target = $Eresus->froot . 'templates/' . $this->name;
		if (!FS::isDir($target))
		{
			$umask = umask(0000);
			mkdir($target, 0777);
			umask($umask);
		}
		$files = glob($this->dirCode . 'templates/*.html');
		foreach ($files as $file)
		{
			copy($file, $target . '/' . basename($file));
		}
	}
	//-----------------------------------------------------------------------------

	/**
	 * Удаление плагина
	 *
	 * @return void
	 */
	public function uninstall()
	{
		global $Eresus;

		useLib('templates');
		$templates = new Templates();

		/* Удаляем шаблоны */
		$list = $templates->enum($this->name);
		$list = array_keys($list);
		foreach ($list as $name)
		{
			$templates->delete($name, $this->name);
		}

		@rmdir($Eresus->froot . 'templates/' . $this->name);

		parent::uninstall();
	}
	//-----------------------------------------------------------------------------

	/**
	 * Обработка запросов от JS API
	 *
	 * @return void
	 */
	public function clientOnStart()
	{
		if (HTTP::request()->getFile() != 'cart.php')
		{
			return;
		}

		switch (arg('method'))
		{
			case 'addItem':
				$this->addItem(arg('class', 'word'), arg('id', 'word'), arg('count', 'int'),
					arg('cost', '[^0-9\.]'));
			break;

			case 'removeItem':
				$this->removeItem(arg('class', 'word'), arg('id', 'word'));
			break;
		}

		$html = $this->clientRenderBlock();

		if (!preg_match('/^UTF-8$/i', CHARSET))
		{
			$html = iconv(CHARSET, 'UTF-8', $html);
		}

		die($html);
	}
	//-----------------------------------------------------------------------------

	/**
	 * Отрисовка блока корзины
	 *
	 * @param string $html  HTML
	 * @return string  HTML
	 */
	public function clientOnPageRender($html)
	{
		global $page;

		$page->linkScripts($GLOBALS['Eresus']->root . 'core/jquery/jquery.min.js');
		$page->linkScripts($this->urlCode . 'jquery.cookie.js');
		$page->linkScripts($this->urlCode . 'api.js');

		$block = $this->clientRenderBlock();
		$html = preg_replace('/\$\(cart\)/i', $block,	$html);

		return $html;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Добавляет товар в корзину
	 *
	 * @param string      $class            Класс товара (класс плагина товаров)
	 * @param int|string  $id               Идентификатор товара
	 * @param int         $count[optional]  Количество добавляемых товаров
	 * @param float       $cost[optional]   Стоимость одного товара
	 *
	 * @return void
	 *
	 * @since 1.00
	 */
	public function addItem($class, $id, $count = 1, $cost = 0)
	{
		/* Добавляем класс товаров, если его ещё нет */
		if (!isset($this->items[$class]))
		{
			$this->items[$class] = array();
		}

		/* Добавляем товар, если его ещё нет */
		if (!isset($this->items[$class][$id]))
		{
			$this->items[$class][$id] = array(
				'cost' => $cost,
				'count' => 0
			);
		}

		// Добавляем товары
		$this->items[$class][$id]['count'] += $count;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Возвращает содержимое корзины
	 *
	 * @param string $class[optional]  Класс товаров
	 * @return array
	 *
	 * @since 1.00
	 */
	public function fetchItems($class = null)
	{
		$items = array();

		if ($class !== null)
		{
			if (!isset($this->items[$class]))
			{
				return array();
			}

			foreach ($this->items[$class] as $id => $item)
			{
				$items []= array(
					'class' => $class,
					'id' => $id,
					'count' => $item['count'],
					'cost' => $item['cost']
				);
			}
			return $items;
		}

		$classes = array_keys($this->items);
		foreach ($classes as $class)
		{
			$items = array_merge($items, $this->fetchItems($class));
		}

		return $items;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Удаляет товар из корзины
	 *
	 * @param string     $class  Класс товара
	 * @param int|string $id     Идентификатор товара
	 *
	 * @return void
	 *
	 * @since 1.00
	 */
	public function removeItem($class, $id)
	{
		if (!isset($this->items[$class]))
		{
			return;
		}

		unset($this->items[$class][$id]);
	}
	//-----------------------------------------------------------------------------

	/**
	 * Очищает корзину
	 *
	 * @return void
	 *
	 * @since 1.00
	 */
	public function clearAll()
	{
		$this->items = array();
	}
	//-----------------------------------------------------------------------------

	/**
	 * Отрисовывает блок корзины
	 *
	 * @return string  HTML
	 */
	private function clientRenderBlock()
	{
		$tmpl = new Template('templates/' . $this->name . '/block.html');

		$data = array('count' => 0, 'sum' => 0);

		foreach ($this->items as $class)
		{
			foreach ($class as $item)
			{
				$data['count'] += $item['count'];
				$data['sum'] += $item['cost'] * $item['count'];
			}
		}

		$html = $tmpl->compile($data);

		$html = '<div id="cart-block-container">' . $html . '</div>';

		return $html;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Загружает содержимое корзины из cookie
	 *
	 * @return void
	 */
	private function loadFromCookies()
	{
		$this->items = array();

		if (isset($_COOKIE[$this->name]))
		{
			@$items = unserialize($_COOKIE[$this->name]);

			/* Проверяем, прошла ли десереализация */
			if ($items === false)
			{
				eresus_log(__METHOD__, LOG_NOTICE, 'Cannot unserialize cookie value: "%s"',
					$_COOKIE[$this->name]);
				return;
			}

			// Записываем результат
			$this->items = $items;
		}

	}
	//-----------------------------------------------------------------------------

	/**
	 * Сохраняет содержимое корзины в cookie
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