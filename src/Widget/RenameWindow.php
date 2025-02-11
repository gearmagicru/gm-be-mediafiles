<?php
/**
 * Этот файл является частью расширения модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\MediaFiles\Widget;

use Gm;
use Gm\Panel\Widget\Form;
use Gm\Panel\Helper\ExtForm;

/**
 * Виджет для формирования интерфейса окна изменения имени файла или папки.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Widget
 * @since 1.0
 */
class RenameWindow extends \Gm\Panel\Widget\EditWindow
{
    /**
     * Идентификатор выбранного файла / папки.
     * 
     * @var string|null
     */
    protected ?string $fileId = '';

    /**
     * Псевдоним диалога.
     * 
     * @var string|null
     */
    protected ?string $dialogAlias = '';

    /**
     * Имя файла / папки, который будет переименован(а).
     * 
     * @var string
     */
    protected string $filename = '';

    /**
     * Действие: 'file', 'folder'.
     * 
     * @var string
     */
    protected string $actionName = '';

    /**
     * {@inheritdoc}
     */
    public array $passParams = [
        'fileId', 'dialogAlias', 'actionName', 'filename'
    ];

    /**
     * {@inheritdoc}
     */
    protected function init(): void
    {
        parent::init();

        // окно компонента (Ext.window.Window Sencha ExtJS)
        $this->width = 450;
        $this->autoHeight = true;
        $this->resizable = false;
        $this->title = $this->creator->t(
            '{rename.' . $this->actionName . '.title}', 
            [$this->filename]
        );
        $this->titleTpl = $this->title;
        $this->iconCls  = 'g-icon-svg gm-mediafiles__icon-rename';

        // панель формы (Gm.view.form.Panel GmJS)
        $this->form->router->setAll([
            'route' => Gm::alias('@match', '/rename'),
            'state' => Form::STATE_UPDATE,
            'rules' => [
                'folder' => '{route}/folder',
                'file'   => '{route}/file',
                'update' => '{route}/perfom'
            ]
        ]);
        $this->form->bodyPadding = 10;
        $this->form->buttons = ExtForm::buttons(
            [
                'help' => ['subject' => 'rename'], 
                'save' => ['text' => '#Rename', 'iconCls' => ''], 
                'cancel'
            ]
        );
        $this->form->items = [
            [
                'xtype' => 'hidden',
                'name'  => 'type',
                'value' => $this->actionName
            ],
            [
                'xtype' => 'hidden',
                'name'  => 'oldName',
                'value' => $this->fileId
            ],
            [
                'xtype'      => 'textfield',
                'name'       => 'newName',
                'fieldLabel' => '#New name',
                'labelWidth' => 120,
                'labelAlign' => 'right',
                'allowBlank' => false,
                'value'      => $this->filename,
                'anchor'     => '100%'
            ]
        ];

        // если вызывает диалог
        if ($this->dialogAlias) {
            $this->form->items[] = [
                'xtype' => 'hidden',
                'name'  => 'dialog',
                'value' => $this->dialogAlias
            ];
        }
    }
}
