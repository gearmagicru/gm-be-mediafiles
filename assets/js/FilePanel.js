/**
 * Виджет панели отображения файлов / папок.
 
 * Этот файл является частью GM Panel.
 *
 * Copyright (c) 2015 Веб-студия GearMagic
 * 
 * Contact: https://gearmagic.ru
 *
 * @author    Anton Tivonenko
 * @copyright (c) 2015, by Anton Tivonenko, anton.tivonenko@gmail.com
 * @date      Oct 01, 2015
 * @version   $Id: 1.0 $
 *
 * @license FilePanel.js is licensed under the terms of the Open Source
 * LGPL 3.0 license. Commercial use is permitted to the extent that the
 * code/component(s) do NOT become part of another Open Source or Commercially 
 * development library or toolkit without explicit permission.
 */

/**
 * Буфер обмена файлов.
 * @class Gm.be.mediafiles.Clipboard
 * @extends Ext.Base
 */
 Ext.define('Gm.be.mediafiles.Clipboard', {
    extend: 'Ext.Base',

    items: [],
    filePanel: null,
    path: null,
    action: null,

    /**
     * Конструктор.
     * @param {Object} config Настройки конфигурации.
     */
     constructor: function (config) {
        this.initConfig(config);
        return this;
    },

    /**
     * Вырезает в буфер.
     * @param {Array} items Файлы и папки.
     */
    cut: function (items) {
        this.items = items;
        this.action = 'cut';
        this.path = this.filePanel.path;
    },

    /**
     * Копирует в буфер.
     * @param {Array} items Файлы и папки.
     */
    copy: function (items) {
        this.items = items;
        this.action = 'copy';
        this.path = this.filePanel.path;
    },

    /**
     * Проверяет, можно ли вставить файлы и папки из буфера в текущую папку.
     * @return {Boolean}
     */
    canPaste: function () {
        return this.path !== this.filePanel.path;
    },

    /**
     * Вставляет файлы и папки из буфера.
     */
    paste: function () {
        let fviews = this.filePanel,
            active = fviews.getActive();

        if (this.items.length === 0) {
            return;
        }

        active.mask(Ext.Txt.waiting);
        Ext.Ajax.request({
            url: Gm.url.build(active.router.build('paste')),
            method: 'post',
            params: {
                files: Ext.encode(this.items), 
                path: fviews.path,
                action: this.action
            },
            /**
             * Успешное выполнение запроса.
             * @param {XMLHttpRequest} response Ответ.
             * @param {Object} opts Параметр запроса вызова.
             */
            success: function (response, opts) {
                active.unmask();
                var response = Gm.response.normalize(response);
                if (response.success)
                    fviews.reload();
                else
                    Ext.Msg.exception(response);
            },
            /**
             * Ошибка запроса.
             * @param {XMLHttpRequest} response Ответ.
             * @param {Object} opts Параметр запроса вызова.
             */
            failure: function (response, opts) {
                active.unmask();
                Ext.Msg.exception(response, true, true);
            }
        });
    }
 });


/**
 * Навигационная цепочка.
 * @class Gm.be.mediafiles.Breadcrumbs
 * @extends Ext.Component
 */
 Ext.define('Gm.be.mediafiles.Breadcrumbs', {
    extend: 'Ext.Component',
    xtype: 'gm-be-mediafiles-breadcrumbs',
    height: 28,
    cls: 'gm-mediafiles-breadcrumbs',
    tpl: [
        '<tpl for="items">', 
            '<span class="item {[xindex!=xcount ? "" : "item_active"]}" title="{path}">{name}</span>', 
            '<tpl if="xindex!=xcount">', 
                '<span class="divider"></span>',
            '</tpl>', 
        '</tpl>'
    ],

    /**
     * @cfg {String} path
     * Идентификатор папки (текущий путь).
     */
    path: '@media',

    /**
    * @cfg {Object} folders
    * Медиапапки используемые в навигацинной цепочки.
    */
    folders: {},

    /**
     * Обновляет путь.
     */
    updatePath: function () { this.setPath(this.path); },

    getFolder: function (path) {
        return Ext.isDefined(this.folders[path]) ? this.folders[path] : null;
    },

    /**
     * Устанавливает путь.
     * @param {String} Путь.
     */
    setPath: function (path) {
        let me = this,
            data = [],
            build = '';

        path.split('/').forEach(item => {
            if (build !== '')
                build = build + '/' + item;
            else
                build = item;
            let folder = me.getFolder(build);
            data.push({
                name: folder ? folder.title : item,
                path: build
            });
        });

        this.path = path;
        this.setData({items: data});
        this.initEvents();
    },

    /**
     * Инициализация событий.
     */
    initEvents: function () {
        let me = this,
            items = me.data.items;
        me.el.query('.item', false).forEach((item, index) => {
            item.on('click', function () {
                me.fireEvent('itemClick', items[index]);
            });
        });
    },

    /**
     * @protected
     */
    afterRender: function() {
        this.callParent(arguments);

        this.updatePath();
    }
 });


/**
 * Панель отображения файлов / папок.
 * @class Gm.be.mediafiles.FilePanel
 * @extends Ext.panel.Panel
 */
Ext.define('Gm.be.mediafiles.FilePanel', {
    extend: 'Ext.panel.Panel',
    xtype: 'gm-be-mediafiles-filepanel',

    /**
     * @cfg {Gm.view.grid.Grid|null} grid
     * Сетка отображения файлов.
     */
    grid: null,

    /**
     * @cfg {Ext.panel.Panel|null} list
     * Список отображения файлов.
     */
    list: null,

    /**
    * @cfg {Gm.be.mediafiles.Breadcrumbs|null} breadcrumbs
    * Навигационная цепочка.
    */
    breadcrumbs: null,

    /**
     * @cfg {Object|null} browse
     * Параметры конфигурации выбора файла / папки через диалоговое окно.
     * Например: {"field": "browse-field-id", "applyTo": "field-id", "type": "file|folder|image", "stripe": "/public;/download"}
     */
    browse: null,

    /**
     * @cfg {String} path
     * Идентификатор выбранной из дерева папки (текущий путь).
     */
    path: '',

    /**
     * @cfg {String} baseRoute
     * Базовый маршрут к модулю менеджера файлов (например, 'admin/mediafiles').
     */
    baseRoute: '',

    /**
     * @cfg {String} folderRootId
     * Идентификатор корневой папки дерева.
     */
    folderRootId: '',

    /**
     * @cfg {Gm.be.mediafiles.Clipboard|null} clipboard
     * Буфер обмена файлов.
     */
    clipboard: null,

    /**
     * @cfg {Boolean} isFiltered
     * Фильтр файлов и папок задействован. 
     */
    isFiltered: false,

    /**
     * @cfg {String} msgMustSelect
     * Сообщение выбора файла или папки.
     */
    msgMustSelect: 'You must select a file or folder',
    /**
     * @cfg {String} msgMustSelectFile
     * Сообщение о необходимости выбора файла.
     */
    msgMustSelectFile: 'You must select a file',
    /**
     * @cfg {String} msgMustSelectOne
     * Сообщение о необходимости выбора только файла или папки.
     */
    msgMustSelectOne: 'Only one file or folder needs to be selected',
    /**
     * @cfg {String} msgMustSelectArchive
     * Сообщение о необходимости выбора только архива.
     */
    msgMustSelectArchive: 'You only need to select the archive file',
    /**
     * @cfg {String} msgUnselectMFolders
     * Сообщение о необходимости убрать выделения с элементов - медиапапка.
     */
    msgUnselectMFolders: 'You need to remove selections from elements - media folder',
    /**
     * @cfg {String} msgConfirm
     * Подтверждение о удалении выделенных файлов.
     */
     msgDelConfirm: 'Are you sure you want to delete the selected files / folders ({0} pcs)? {1}',
    /**
     * @cfg {String} msgConfirmFolders
     * Подтверждение о удалении выделенных папок.
     */
    msgDelConfirmFolders: 'Are you sure you want to delete the selected folders ({0} pcs)? {1}',
    /**
     * @cfg {String} msgConfirmFolder
     * Подтверждение о удалении папки.
     */
    msgDelConfirmFolder: 'Are you sure you want to delete the folder "{0}"?',
    /**
     * @cfg {String} msgConfirmFolder
     * Подтверждение о удалении выделенных файлов.
     */
    msgDelConfirmFiles: 'Are you sure you want to delete the selected files ({0} pcs)? {1}',
    /**
     * @cfg {String} msgConfirmFile
     * Подтверждение о удалении файла.
     */
    msgDelConfirmFile: 'Are you sure you want to delete the file "{0}"?',
    /**
     * @cfg {String} msgCannotPasteFiles
     * Невозможно вставить файлы.
     */
    msgCannotPasteFiles: 'Cannot paste files where they were copied or cut from',
    /**
     * @cfg {String} msgCopyClipboard
     * Копирование файлов в буфер обмена.
     */
    msgCopyClipboard: 'Files / folders copied to clipboard',
    /**
     * @cfg {String} msgCutClipboard
     * Вырезание файлов в буфер обмена.
     */
    msgCutClipboard: 'Files / folders cut to clipboard',
    /**
     * @cfg {String} msgEmptyBrowseFile
     * Необходимо выбрать файл.
     */
    msgEmptyBrowseFile: 'You must select a file',
    /**
     * @cfg {String} msgEmptyBrowseFolder
     * Необходимо выбрать папку.
     */
    msgEmptyBrowseFolder: 'You must select a folder',
    /**
     * @cfg {String} titleClipboard
     * Заголовок сообщения.
     */
    titleClipboard: 'Clipboard',

    /**
     * Конструктор.
     * @param {Object} config Настройки конфигурации.
     */
    constructor: function (config) {
        this.clipboard = Ext.create('Gm.be.mediafiles.Clipboard', { filePanel: this });

        this.callParent(arguments);
    },

    /**
     * Обработчик событий.
     * @cfg {Object}
     */
    listeners: {
        /**
         * Событие после рендера компонента.
         * @param {Ext.panel.Panel} me
         * @param {Object} eOpts Параметры слушателя.
         */
        afterrender: function (me, eOpts) {
            me.grid = me.items.getAt(0);
            me.list = me.items.getAt(1);
            me.breadcrumbs = Ext.getCmp(this.id + '__breadcrumbs');
            if (me.breadcrumbs) {
                me.breadcrumbs.setPath(me.path);
            }

            // только тут определения событий загрузки списка файлов
            me.grid.getStore().on('load', me.controller.onLoadFiles, me);
            me.list.getStore().on('load', me.controller.onLoadFiles, me);
            me.grid.getStore().on('beforeload', me.controller.onBeforeLoadFiles, me);
            me.list.getStore().on('beforeload', me.controller.onBeforeLoadFiles, me);
        },
        /**
         * Событие после установки параметров поиска.
         * @param {Boolean} success Если true, параметры установлены.
         */
        afterSearch: function (success) {
            if (!success) return;

            this.btnDisabled(['btnHome', 'btnGoUp', 'btnUpload', 'btnCompress'], true);
        },
        /**
         * Событие после сброса параметров поиска.
         */
        afterSearchReset: function () {
            this.btnDisabled(['btnHome', 'btnGoUp', 'btnUpload', 'btnCompress'], false);
        }
    },

    /**
     * Проверяет, является ли указанная папка корневой.
     * @param {String} folder Идентификатор проверяемой папки.
     * @return {Boolean}
     */
    isFolderRoot: function (folder) {
        return folder === this.folderRootId;
    },

    /**
     * Возвращает активную панель отображения файлов.
     * @return {Gm.view.grid.Grid|Ext.panel.Panel}
     */
    getActive: function () {
        return this.grid.isHidden() ? this.list : this.grid;
    },

    /**
     * Показывает маску на активной панеле отображения файлов.
     */
    mask: function () { this.getActive().mask(); },

    /**
     * Показывает сетку отображения файлов.
     * @return {this}
     */
    showGrid: function () {
        this.grid.show();
        this.list.hide();
        return this;
    },

    /**
     * Показывает список отображения файлов.
     * @return {this}
     */
    showList: function () {
        this.list.show();
        this.grid.hide();
        return this;
    },

    /**
     * Сделать панель инструментов недоступным.
     * @param {Boolean} disabled
     * @return {this}
     */
    toolbarDisabled: function (disabled) {
        let tlb = Ext.getCmp(this.id + '__toolbar');
        if (tlb) tlb.setDisabled(disabled);
        return this;
    },

    /**
     * Сделать кнопку недоступной.
     * @param {String} name
     * @param {Boolean} disabled
     * @return {this}
     */
    btnDisabled: function (name, disabled) {
        let viewId = this.id + '__';

        if (Ext.isArray(name)) {
            name.forEach((item) => {
                let btn = Ext.getCmp(viewId + item);
                if (Ext.isDefined(btn))
                    btn.setDisabled(disabled);
            });
        } else
            Ext.getCmp(viewId + name).setDisabled(disabled);
        return this;
    },

    /**
     * Возвращает идентификатор папки на уровень выше.
     * @return {String}
     */
    getTopFolder: function () {
        if (this.path === '' || this.path === this.folderRootId) {
            return this.folderRootId;
        }
        let names = this.path.split('/');
        names.pop();
        if (names.length > 0)
            return names.join('/');
        else
            return this.folderRootId;
    },

    /**
     * Возвращает идентификатор папки на уровень ниже относительно указанной папки.
     * @param {String} folder Идентификатор папки.
     * @return {String}
     */
    getSubFolder: function (folder) {
        if (this.path === '' || this.path === this.folderRootId) {
            return folder;
        }
        return this.path + '/' + folder;
    },

    /**
     * Возвращает идентификатор папки на уровень ниже относительно указанной папки.
     * @param {String} folder Идентификатор папки.
     * @return {String}
     */
    getSupFolder: function (folder) {
        let chunks = folder.split('/');
        chunks.pop();
        if (chunks.length > 0)
            return chunks.join('/');
        else
            return this.folderRootId;
    },

    /**
     * Возвращает идентификатор корневой папки.
     * @return {String}
     */
    getHomeFolder: function () { return this.folderRootId; },

    /**
     * Возвращает идентификатор выбранной из дерева папки (текущий путь).
     * @return {String}
     */
    getPath: function () {
        return this.path === '' ? this.folderRootId : this.path;
    },

    /**
     * Обновляет список файлов / папок.
     */
    reload: function () { this.getActive().getStore().reload(); },

    /**
     * Загружает список файлов / папок по указанному пути.
     * @param {String} path Путь к файлам / папкам (идентификатор папки).
     */
    load: function (path) {
        this.path = path;
        let store = this.getActive().getStore();

        // если диалог
        let dialog = this.getDialog();
        if (dialog) {
            store.getProxy().extraParams.dialog = dialog.dialogAlias;
        }
        store.getProxy().extraParams.path = path;
        store.reload({ page: 1, start: 0 });

        if (this.breadcrumbs) this.breadcrumbs.setPath(path);
        return this;
    },

    /**
     * Загружает виджет.
     * @param {String} widget Название виджета.
     * @param {Object} params Параметры загрузки.
     * @param {Boolean} caching Кэшировать результат (по умолчанию `false`).
     */
    loadWidget: function (widget, params = null, caching = false) {
        Gm.getApp().widget.load(this.baseRoute + '/' + widget, params, caching);
    },

    /**
     * Удаляет файлы / папки.
     * @param {Array} files Идентификаторы файлов / папок.
     * @param {Function} onResponse Результат удаления.
     */
    delete: function (files, onResponse) {
        let active = this.getActive(),
            params = { id: Ext.encode(files) };

        // если диалоговое окно
        if (this.isDialog()) {
            params.dialog = this.getDialog().dialogAlias;
        }

        active.mask(Ext.Txt.waiting);
        Ext.Ajax.request({
            url: Gm.url.build(active.router.build('delete')),
            method: 'post',
            params: params,
            /**
             * Успешное выполнение запроса.
             * @param {XMLHttpRequest} response Ответ.
             * @param {Object} opts Параметр запроса вызова.
             */
            success: function (response, opts) {
                active.unmask();
                var response = Gm.response.normalize(response);
                if (!response.success) {
                    Ext.Msg.exception(response);
                }
                if (Ext.isFunction(onResponse)) {
                    onResponse(response.success);
                }
            },
            /**
             * Ошибка запроса.
             * @param {XMLHttpRequest} response Ответ.
             * @param {Object} opts Параметр запроса вызова.
             */
            failure: function (response, opts) {
                active.unmask();
                Ext.Msg.exception(response, true, true);
                if (Ext.isFunction(onResponse)) {
                    onResponse(false);
                }
            }
        });
    },

    /**
     * Устанавливает выделение файлов / папок.
     */
    selectAll: function () {
        this.getActive().getSelectionModel().selectAll();
    },

    /**
     * Убирает выделение файлов / папок.
     */
    deselectAll: function () {
        this.getActive().getSelectionModel().deselectAll();
    },

    /**
     * Инвертирует выделение файлов / папок.
     */
    invertSelection: function () {
        let active = this.getActive(),
            sm = active.getSelectionModel(),
            rows = [];
        active.getStore().each(function (row) {
            if (!sm.isSelected(row)) rows.push(row);
        });
        sm.select(rows);
    },

    /**
     * Проверяет, выделены ли файлы / папки.
     * @return {Boolean}
     */
    isSelected: function (excludeMediaFolders = true) {
        let active = this.getActive(),
            items  = active.getSelectionModel().getSelection();
        if (items.length > 0) {
            if (excludeMediaFolders) {
                let item = items.find((item) => { return item.data.isMediaFolder == true });
                return !Ext.isDefined(item);
            } else
                return true;
        }
        return false;
    },

    /**
     * Проверяет, выделен ли один файл или папка.
     * @return {Boolean}
     */
     isOneSelected: function () {
        let active = this.getActive(),
            items  = active.getSelectionModel().getSelection();

        if (items.length === 1) {
            return !items[0].get('isMediaFolder');
        }
        return false;
    },

    /**
     * Проверяет, выделена ил папка.
     * @return {Boolean}
     */
    isFolderSelected: function () {
        let active = this.getActive(),
            items  = active.getSelectionModel().getSelection();
        if (items.length > 0) {
            return items[0].get('isFolder') && !items[0].get('isMediaFolder');
        }
        return false;
    },

    /**
     * Возвращает выделенные файлы / папки + медиапапки.
     * @param {Boolean} onlyOne Возвращать только один выделенный файл / папку.
     * @param {Boolean} returnItem Возвращать объект или название файла / папки.
     * @return {Object}
     */
    getSelected: function (onlyOne = true, returnItem = true) {
        let active = this.getActive(),
            items  = active.getSelectionModel().getSelection();

        if (onlyOne) {
            let item = items.length > 0 ? items[0] : null;
            return returnItem ? item : (item ? item.id : item);
        }

        let files = [], folders = [], mfolders = [];
        for (const item of items) {
            if (item.get('isFolder')) {
                if (item.get('isMediaFolder'))
                    mfolders.push(returnItem ? item : item.id);
                else
                    folders.push(returnItem ? item : item.id);
            } else
                files.push(returnItem ? item : item.id);
        };
        return { files: files, folders: folders, mediaFolders: mfolders };
    },

    /**
     * Возвращает выделенные файлы.
     * @return {Array}
     */
    getSelectedFiles: function () {
        let active = this.getActive(),
            items  = active.getSelectionModel().getSelection(),
            files = [];
        for (const item of items) {
            files.push(item.id);
        };
        return files;
    },

    /**
     * Выполняет скачивание указанных файлов / папок.
     * @param {String} route Маршрут для скачивания, например 'download'.
     * @param {Array} files Идентификаторы файлов / папок.
     */
    download: function (route, files) {
        let active = this.getActive(),
            me = this;

        active.mask(Ext.Txt.waiting);
        Ext.Ajax.request({
            url: Gm.url.build(me.baseRoute + '/' + route),
            method: 'post',
            params: { files: Ext.encode(files) },
            /**
             * Успешное выполнение запроса.
             * @param {XMLHttpRequest} response Ответ.
             * @param {Object} opts Параметр запроса вызова.
             */
            success: function (response, opts) {
                active.unmask();
                var response = Gm.response.normalize(response);
                if (response.success)
                    me.downloadFile(
                        Gm.url.build(me.baseRoute + '/' + route + '/file/' + response.data)
                    );
                else
                    Ext.Msg.exception(response);
            },
            /**
             * Ошибка запроса.
             * @param {XMLHttpRequest} response Ответ.
             * @param {Object} opts Параметр запроса вызова.
             */
            failure: function (response, opts) {
                active.unmask();
                Ext.Msg.exception(response, true, true);
            }
        });
    },

    /**
     * Выполняет скачивание файла по указанному URL-адресу.
     * @param {String} url URL-адрес для скачивания.
     */
    downloadFile: function (url) {
        let frameId = this.id + '__frame'; // => gm-mediafiles-view__frame

        let iframe = document.getElementById(frameId);
        if (iframe === null) {
            iframe = document.createElement('iframe');
            iframe.id = frameId;
            iframe.style.display = 'none';
            document.body.appendChild(iframe);
        }
        iframe.src = url;
    },

    isDialog: function () {
        let win = this.up('window');
        return win ? true : false;
    },

    getDialog: function () { return this.up('window'); }
});