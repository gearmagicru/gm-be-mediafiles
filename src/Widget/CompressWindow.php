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
 * Виджет для формирования интерфейса окна архивирования файлов / папок.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Widget
 * @since 1.0
 */
class CompressWindow extends \Gm\Panel\Widget\EditWindow
{
    /**
     * Форматы архивов.
     * 
     * @var array<int, mixed>
     */
    protected array $archiveFormats = [];

    /**
     * Имя архива в текством поле.
     * 
     * @var string
     */
    protected string $archiveName = '';

    /**
     * Идентификатор пути (папка).
     * 
     * @var string|null
     */
    protected ?string $pathId = null;

    /**
     * {@inheritdoc}
     */
    public array $passParams = [
        'archiveFormats', 'archiveName', 'pathId'
    ];

    /**
     * {@inheritdoc}
     */
    protected function init(): void
    {
        parent::init();

        // окно компонента (Ext.window.Window Sencha ExtJS)
        $this->width = 400;
        $this->autoHeight = true;
        $this->resizable = false;
        $this->title = $this->creator->t('{compress.title}');
        $this->titleTpl = $this->title;
        $this->iconCls  = 'g-icon-svg gm-mediafiles__icon-compress';

        // панель формы (Gm.view.form.Panel GmJS)
        $this->form->bodyPadding = 10;
        $this->form->defaults = [
            'labelWidth' => 110,
            'labelAlign' => 'right',
            'width'      => '100%',
            'allowBlank' => false
        ];
        $this->form->router->setAll([
            'route' => Gm::alias('@match', '/compress'),
            'state' => Form::STATE_CUSTOM,
            'rules' => [
                'perfom' => '{route}/perfom'
            ]
        ]);
        $this->form->setStateButtons(
            Form::STATE_CUSTOM,
            ExtForm::buttons([
                'help' => ['subject' => 'compress'], 
                'submit' => [
                    'text'        => '#Compress', 
                    'iconCls'     => 'g-icon-svg g-icon_size_14 gm-mediafiles__icon-compress', 
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
            ExtCombo::local(
                '#Archive type', 
                'format', 
                [
                    'fields' => ['id', 'name'],
                    'data'   => $this->archiveFormats
                ],
                [
                    'allowBlank' => false
                ]
            ),
            [
                'xtype'      => 'textfield',
                'fieldLabel' => '#Archive name',
                'name'       => 'name',
                'value'      => $this->archiveName,
                'maxLength'  => 50
            ]
        ];
    }
}
