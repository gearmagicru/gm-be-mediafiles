/*!
 * Контроллер представления виджета панели дерева папок.
 * Модуль "Медиафайлы".
 * Copyright 2015 Вeб-студия GearMagic. Anton Tivonenko <anton.tivonenko@gmail.com>
 * https://gearmagic.ru/license/
 */

Ext.define('Gm.be.mediafiles.FolderTreeController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.gm-be-mediafiles-foldertree',

    /**
     * @cfg {String} filePanelId
     * Идентификатор панели отображения файлов.
     */
    filePanelId: '',

    init: function (view) {
        this.filePanelId = view.up().filePanelId;
        view.on('itemclick', this.onFolderClick, this);
    },

    /**
     * Возвращает панель отображения файлов.
     * @return {Gm.be.mediafiles.FilePanel}
     */
    getFilePanel: function () { return Ext.getCmp(this.filePanelId); },

    /**
     * Нажатие кнопки "Развернуть всё".
     * @param {Ext.button.Button} me
     * @param {Event} e
     * @param {Object} eOpts
     */
    onExpandFolders: function (me, e, eOpts) { this.view.expandAll(); },

    /**
     * Нажатие кнопки "Свернуть всё".
     * @param {Ext.button.Button} me
     * @param {Event} e
     * @param {Object} eOpts
     */
    onCollpaseFolders: function (me, e, eOpts) { this.view.collapseAll(); },

    /**
     * Нажатие кнопки "Редактировать медиапапку".
     * @param {Ext.button.Button} me
     * @param {Event} e
     * @param {Object} eOpts
     */
    onEditFolder: function (me, e, eOpts) { 
        let nodes = this.view.getSelectionModel().getSelection(); 
        if (nodes.length > 0)
            Gm.getApp().widget.load('@backend/references/media-folders/form/view/' + nodes[0].get('folderId'));
        else
            Ext.Msg.warning(me.msgMustSelect);
    },

    /**
     * Нажатие кнопки "Редактировать профиль медиапапки".
     * @param {Ext.button.Button} me
     * @param {Event} e
     * @param {Object} eOpts
     */
     onEditFolderProfile: function (me, e, eOpts) { 
        let nodes = this.view.getSelectionModel().getSelection(); 
        if (nodes.length > 0) {
            let pid = nodes[0].get('profileId');
            if (pid)
                Gm.getApp().widget.load('@backend/references/folder-profiles/form/view/' + nodes[0].get('profileId'));
            else
                Ext.Msg.warning(me.msgNoProfile);
        } else
            Ext.Msg.warning(me.msgMustSelect);
    },

    /**
     * Нажатие кнопки "Настройка медиапапок".
     * @param {Ext.button.Button} me
     * @param {Event} e
     * @param {Object} eOpts
     */
    onFolderSettings: function (me, e, eOpts) { Gm.getApp().widget.load('@backend/references/media-folders'); },

    /**
     * Нажатие кнопки "Обновить".
     * @param {Ext.button.Button} me
     * @param {Event} e
     * @param {Object} eOpts
     */
    onRefreshFolders: function (me, e, eOpts) { this.view.getStore().reload(); },

    /**
     * Нажатие по элементу дерева. 
     * @param {Ext.tree.Panel} me
     * @param {Ext.data.Model} record
     * @param {HTMLElement} item
     * @param {Number} index
     * @param {Ext.event.Event} e
     * @param {Object} eOpts
     */
    onFolderClick: function (me, record, item, index, e, eOpts) { 
        this.getFilePanel().load(record.id); 
    },

    /**
     * Добавляет папку в указанный узел. 
     * @param {String} name
     * @param {String} parentFolderId 
     */
    add: function (name, parentFolderId) {
        let parentFolder = this.view.getStore().getNodeById(parentFolderId);
        if (parentFolder !== null) {
            if (parentFolder.isLoaded()) {
                let isRoot = this.getFilePanel().isFolderRoot(parentFolder.id);
                parentFolder.appendChild({
                    id: isRoot ? name : parentFolder.id + '/' + name,
                    text: name,
                    leaf: false
                });
            }
        }
    },

    /**
     * Добавляет папку в указанный узел. 
     * @param {Array} foldersId
     * @param {String} parentFolderId 
     */
    remove: function (foldersId, parentFolderId) {
        let store = this.view.getStore(),
            parentFolder = store.getNodeById(parentFolderId);
        if (parentFolder !== null) {
            if (parentFolder.isLoaded()) {
                for (let folderId of foldersId) {
                    let folder = store.getNodeById(folderId);
                    if (folder !== null) {
                        parentFolder.removeChild(folder);
                    }
                }
            }
        }
    },

    /**
     * Переименовывает указанную папку. 
     * @param {String} newName
     * @param {String} folderId 
     */
    rename: function (newName, folderId) {
        let node = this.view.getStore().getNodeById(folderId);
        if (node !== null) {
            node.set('text', newName);
        }
    }
});