<?php
/**
 * Корзина заказов
 *
 * Eresus 2.12
 *
 * Блок корзины заказов с API для добавления / удаления товаров
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
	public $version = '1.00a';

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
		parent::__construct();
		$this->listenEvents('clientOnPageRender');
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
			copy($file, $target . '/' . basename($file));
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
		foreach ($list as $name => $desc)
			$templates->delete($name, $this->name);

		@rmdir($Eresus->froot . 'templates/' . $this->name);

		parent::uninstall();
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
		// Подключть JS
		$block = $this->clientRenderBlock();
		$html = preg_replace('/\$\(cart\)/i', $block, $html);
		return $html;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Добавляет товар в корзину
	 *
	 * @param string $class            Класс товара (класс плагина товаров)
	 * @param string $id               Идентификатор товара
	 * @param int    $count[optional]  Количество добавляемых товаров
	 * @param float  $cost[optional]   Стоимость одного товара
	 */
	public function addItem($class, $id, $count = 1, $cost = 0)
	{
		/* Добавляем класс товаров, если его ещё нет */
		if (!isset($this->items[$class]))
			$this->items[$class] = array();

		/* Добавляем товар, если его ещё нет */
		if (!isset($this->items[$class][$id]))
			$this->items[$class][$id] = array(
				'cost' => $cost,
				'count' => 0
			);

		// Добавляем товары
		$this->items[$class][$id]['count'] += $count;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Возвращает содержимое корзины
	 *
	 * @param string $class[optional]
	 * @return array()
	 */
	public function fetchItems($class = null)
	{
		;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Отрисовывает блок корзины
	 *
	 * @return string  HTML
	 */
	private function clientRenderBlock()
	{
		;
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