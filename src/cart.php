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
 */

/**
 * Корзина заказов
 *
 * @package Cart
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
	public $kernel = '3.00b';

	/**
	 * Название плагина
	 * @var string
	 */
	public $title = 'Корзина заказов';

	/**
	 * Описание плагина
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
	private $items = null;

	/**
	 * Конструктор
	 *
	 * @return Cart
	 */
	public function __construct()
	{
		parent::__construct();

		$this->listenEvents('clientOnStart', 'clientOnPageRender');
	}
	//-----------------------------------------------------------------------------

	/**
	 * Установка плагина
	 *
	 * @return void
	 */
	public function install()
	{
		parent::install();

		$Eresus = Eresus_CMS::getLegacyKernel();
		
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
		useLib('templates');
		$templates = new Templates();

		/* Удаляем шаблоны */
		$list = $templates->enum($this->name);
		$list = array_keys($list);
		foreach ($list as $name)
		{
			$templates->delete($name, $this->name);
		}

		@rmdir(Eresus_CMS::getLegacyKernel()->froot . 'templates/' . $this->name);

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

			case 'changeAmount':
				$this->changeAmount(arg('class', 'word'), arg('id', 'word'), arg('amount', 'int'));
				break;

			case 'clearAll':
				$this->clearAll();
				break;

			case 'removeItem':
				$this->removeItem(arg('class', 'word'), arg('id', 'word'));
				break;
		}

		$html = $this->clientRenderBlock();
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
		$page = Eresus_Kernel::app()->getPage();

		$page->linkJsLib('jquery', 'cookie');
		$page->linkScripts($this->urlCode . 'api.js');

		$block = $this->clientRenderBlock();
		$html = preg_replace('/\$\(cart\)/ui', $block,	$html);

		return $html;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Добавляет товар в корзину
	 *
	 * @param string      $class  Класс товара (класс плагина товаров)
	 * @param int|string  $id     Идентификатор товара
	 * @param int         $count  Количество добавляемых товаров
	 * @param float|int   $cost   Стоимость одного товара
	 *
	 * @return void
	 *
	 * @since 1.00
	 */
	public function addItem($class, $id, $count = 1, $cost = 0)
	{
		$this->loadFromCookies();
		if ($count < 1 || $cost < 0)
		{
			return;
		}

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
		$this->saveToCookies();
	}
	//-----------------------------------------------------------------------------

	/**
	 * Изменяет количество товара в корзине
	 *
	 * @param string      $class   Класс товара (класс плагина товаров)
	 * @param int|string  $id      Идентификатор товара
	 * @param int         $amount  Новое количество добавляемых товаров
	 *
	 * @return void
	 *
	 * @since 1.00
	 */
	public function changeAmount($class, $id, $amount)
	{
		$this->loadFromCookies();
		if (
			!isset($this->items[$class]) ||
			!isset($this->items[$class][$id])
		)
		{
			return;
		}

		if ($amount < 1)
		{
			$this->removeItem($class, $id);
		}
		else
		{
			$this->items[$class][$id]['count'] = $amount;
		}
		$this->saveToCookies();
	}
	//-----------------------------------------------------------------------------

	/**
	 * Возвращает содержимое корзины
	 *
	 * @param string $class  Класс товаров
	 *
	 * @return array
	 *
	 * @since 1.00
	 */
	public function fetchItems($class = null)
	{
		$this->loadFromCookies();
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
		$this->loadFromCookies();
		if (!isset($this->items[$class]))
		{
			return;
		}

		unset($this->items[$class][$id]);
		$this->saveToCookies();
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
		$this->loadFromCookies();
		$this->items = array();
		$this->saveToCookies();
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

		if ($this->items)
		{
			foreach ($this->items as $class)
			{
				foreach ($class as $item)
				{
					$data['count'] += $item['count'];
					$data['sum'] += $item['cost'] * $item['count'];
				}
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
		if ($this->items !== null)
		{
			return;
		}
		$this->items = array();

		if (isset($_COOKIE[$this->name]))
		{
			$cookieValue = $_COOKIE[$this->name];
			/*
			 * В PHP до 5.3 при включённых "магических кавычках" в куки могут присутствовать лишние	слэши
			 */
			if (
				! PHP::checkVersion('5.3') &&
				get_magic_quotes_gpc()
			)
			{
				$cookieValue = stripslashes($cookieValue);
			}

			@$items = unserialize($cookieValue);

			/* Проверяем, прошла ли десериализация */
			if ($items === false)
			{
				eresus_log(__METHOD__, LOG_NOTICE, 'Cannot unserialize cookie value: "%s"',
					$cookieValue);
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