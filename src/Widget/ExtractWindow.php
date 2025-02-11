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
use Gm\Panel\Helper\ExtCombo;

/**
 * Виджет для формирования интерфейса окна разархивирования файлов / папок.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Widget
 * @since 1.0
 */
class ExtractWindow extends \Gm\Panel\Widget\EditWindow
{
    /**
     * Идентификатор пути (папка).
     * 
     * @var string|null
     */
    protected ?string $pathId = null;

    /**
     * Идентификатор выбранного файла архива.
     * 
     * @var string
     */
    protected string $fileId = '';

    /**
     * {@inheritdoc}
     */
    public array $passParams = ['pathId', 'fileId'];

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
        $this->title = $this->creator->t('{extract.title}', [basename($this->fileId)]);
        $this->titleTpl = $this->title;
        $this->iconCls  = 'g-icon-svg gm-mediafiles__icon-extract';

        // панель формы (Gm.view.form.Panel GmJS)
        $this->form->makeViewID(); // для того, чтобы сразу использовать `$window->form->id`
        $this->form->controller = 'gm-be-mediafiles-extract';
        $this->form->router->setAll([
            'route' => Gm::alias('@match', '/extract'),
            'state' => Form::STATE_CUSTOM,
            'rules' => [
                'perfom' => '{route}/perfom'
            ]
        ]);
        $this->form->bodyPadding = 10;
        $this->form->defaults = [
            'labelWidth' => 90,
            'labelAlign' => 'right',
            'width'      => '100%'
        ];
        $this->form->setStateButtons(
            Form::STATE_CUSTOM,
            ExtForm::buttons([
                'help' => ['subject' => 'extract'], 
                'submit' => [
                    'text'        => '#Extract', 
                    'iconCls'     => 'g-icon-svg g-icon_size_14 gm-mediafiles__icon-extract', 
                    'handler'     => 'onFormSubmit',
                    'handlerArgs' => [
                        'routeRule' => 'perfom'
                    ]
                ],
                'cancel'
            ])
        );
        $this->form->items = [
            [
                'xtype' => 'hidden',
                'name'  => 'path',
                'value' => $this->pathId
            ],
            [
                'xtype' => 'hidden',
                'name'  => 'file',
                'value' => $this->fileId
            ],
            ExtCombo::local(
                '#Where', 
                'where', 
                [
                    'fields' => ['id', 'name'],
                    'data'   => [
                        ['separate', '#To a separate folder'],
                        ['current', '#To current folder']
                    ]
                ],
                [
                    'value'      => 'separate',
                    'allowBlank' => false,
                    'listeners'  => [
                        'select' => 'onSelectWhere'
                    ]
                ]
            ),
            [
                'id'         => $this->form->id . '__folder',
                'xtype'      => 'textfield',
                'fieldLabel' => '#Folder name',
                'name'       => 'folderName',
                'value'      => pathinfo($this->fileId, PATHINFO_FILENAME),
                'maxLength'  => 50,
                'allowBlank' => true
            ],
            [
                'ui'         => 'switch',
                'xtype'      => 'checkbox',
                'inputValue' => 1,
                'padding'    => '0 0 0 95px',
                'name'       => 'deleteAfter',
                'boxLabel'   => '#Delete archive after extraction',
            ]
        ];
        $this
            ->setNamespaceJS('Gm.be.mediafiles')
            ->addRequire('Gm.be.mediafiles.ExtractController');
    }
}
