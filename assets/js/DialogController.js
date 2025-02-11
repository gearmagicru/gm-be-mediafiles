/*!
 * Контроллер диалогового окна выбора файла / папки.
 * Модуль "Медиафайлы".
 * Copyright 2015 Вeб-студия GearMagic. Anton Tivonenko <anton.tivonenko@gmail.com>
 * https://gearmagic.ru/license/
 */

Ext.define('Gm.be.mediafiles.DialogController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.gm-be-mediafiles-dialog',

    /**
     * Инициализация контроллера.
     * @param {Gm.be.mediafiles.FilePanel} view
     */
     init: function (view) {
        /**
         * Применяет выбранное название файла / папки из диалогового окна к указанному 
         * в параметрах (browse.applyTo) полю.
         * @return {Boolean}
         */
         view.applyBrowse = function () {
            if (view.browse !== null) {
                let field = Ext.getCmp(view.browse.field),
                    value = field.getValue();

                if (value.length === 0) {
                    Ext.Msg.warning(view.msgEmptyDialogFile);
                    return false;
                } else {
                    if (Ext.isDefined(view.browse.stripe)) {
                        let stripe = view.browse.stripe.split(';');
                        value = value.trimWords(stripe);
                    }
                    Ext.getCmp(view.browse.applyTo).setValue(value);
                }
            }
            return true;
        };
    },

    /**
     * Нажатие кнопки "Выбрать".
     * @param {Ext.button.Button} me
     */
    onChoose: function (me) {
        let dialog = me.up('window');
        if (dialog.applyBrowse()) {
            dialog.close();
        }
    },

    /**
     * Нажатие кнопки "Отмена".
     * @param {Ext.button.Button} me
     */
    onCancel: function (me) {
        me.up('window').close();
    }
});