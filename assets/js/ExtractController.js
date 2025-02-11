/*!
 * Контроллер представления виджета формы извлечения файлов.
 * Модуль "Медиафайлы".
 * Copyright 2015 Вeб-студия GearMagic. Anton Tivonenko <anton.tivonenko@gmail.com>
 * https://gearmagic.ru/license/
 */

Ext.define('Gm.be.mediafiles.ExtractController', {
    extend: 'Gm.view.form.PanelController',
    alias: 'controller.gm-be-mediafiles-extract',

    /**
     * Событие выбора папки.
     * @param {Ext.form.field.ComboBox} me
     * @param {Ext.data.Model/Ext.data.Model[]} record
     * @param {Object} eOpts
     */
    onSelectWhere: function (me, record, eOpts) {
        let folder = Ext.getCmp(this.view.id + '__folder');
        if (record.id === 'current')
            folder.hide();
        else
            folder.show();
    }
});