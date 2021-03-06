/**
 * JavaScript API для работы с корзиной
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
 */

/**
 * Объект "Корзина"
 */
var cart =
{
    /**
     * Добавляет товар в корзину
     *
     * @param {String}  className  Класс товара (класс плагина товаров)
     * @param {String}  id         Идентификатор товара
     * @param {Integer} count      Количество добавляемых товаров
     * @param {Number}  cost       Стоимость одного товара
     *
     * @since 1.00
     */
    addItem: function (className, id)
    {
        var count = arguments.length > 2 ? arguments[2] : 1;
        var cost = arguments.length > 3 ? arguments[3] : 0;

        cart.callAPI("addItem", {"class": className, "id": id, "count": count, "cost": cost});
    },

    /**
     * Изменяет количество товара в корзине
     *
     * @param {String}  className  Класс товара (класс плагина товаров)
     * @param {String}  id         Идентификатор товара
     * @param {Integer} amount     Количество добавляемых товаров
     *
     * @since 1.00
     */
    changeAmount: function (className, id, amount)
    {
        cart.callAPI("changeAmount", {"class": className, "id": id, "amount": amount});
    },

    /**
     * Полностью очищает корзину
     *
     * @since 1.00
     */
    clearAll: function ()
    {
        cart.callAPI("clearAll", {});
    },

    /**
     * Удаляет товар из корзины
     *
     * @param {String}  className        Класс товара (класс плагина товаров)
     * @param {String}  id               Идентификатор товара
     *
     * @since 1.00
     */
    removeItem: function (className, id)
    {
        cart.callAPI("removeItem", {"class": className, "id": id});
    },

    /**
     * Вызывает метод PHP API
     *
     * Отправляет AJAX-запрос к PHP API. В случае успеха вызывает метод updateBlock для обновления
     * блока корзины.
     *
     * @param {String}   method    имя метода PHP API
     * @param {Object}   args      аргументы
     * @param {Function} callback  функция, которая будет вызвана по завершении
     */
    callAPI: function (method, args)
    {
        var callback = arguments.length > 2 ? arguments[2] : undefined;

        args.method = method;

        var self = this;
        jQuery.ajax({
            async: true,
            context: this,
            data: args,
            dataType: "html",
            success: function (data, textStatus, request)
            {
                self.updateBlock(data, textStatus, request, callback);
            },
            url: "cart.php"
        });
    },

    /**
     * Обновляет блок корзины
     *
     * @param {String}         data
     * @param {String}         textStatus
     * @param {XMLHttpRequest} request
     * @param {Function}       callback  функция, которая надо вызвать по завершении
     */
    updateBlock: function (data, textStatus, request, callback)
    {
        jQuery("#cart-block-container").replaceWith(data);
        callback();
    }
};

