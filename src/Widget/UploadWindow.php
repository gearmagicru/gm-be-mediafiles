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
use Gm\Helper\Html;
use Gm\Panel\Widget\Form;
use Gm\Panel\Helper\ExtForm;

/**
 * Виджет для формирования интерфейса окна загрузки файла.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Widget
 * @since 1.0
 */
class UploadWindow extends \Gm\Panel\Widget\EditWindow
{
    /**
     * Атрибуты профиля медиапапки, которые используются при загрузке файла.
     * 
     * @var array<string, mixed>
     */
    protected array $folderProfile = [];

    /**
     * Атрибуты медиапапки, которые используются при загрузке файла.
     * 
     * @var array<string, mixed>
     */
    protected array $mediaFolder = [];

    /**
     * Идентификатор пути (папка).
     * 
     * @var string|null
     */
    protected ?string $pathId = null;

    /**
     * Псевдоним диалога.
     * 
     * @var string|null
     */
    protected ?string $dialogAlias = '';

    /**
     * Является ли выбранный путь (папка) папкой диалога.
     * 
     * @var bool
     */
    protected bool $isDialogFolder = false;

    /**
     * {@inheritdoc}
     */
    public array $passParams = [
        'folderProfile', 'mediaFolder', 'pathId', 'isDialogFolder', 'dialogAlias'
    ];

    /**
     * {@inheritdoc}
     */
    protected function init(): void
    {
        parent::init();

        // панель формы (Gm.view.form.Panel GmJS)
        $this->form->autoScroll = true;
        $this->form->router->setAll([
            'route' => Gm::alias('@match', '/upload'),
            'state' => Form::STATE_CUSTOM,
            'rules' => [
                'submit' => '{route}/perfom'
            ] 
        ]);

        $this->form->setStateButtons(
            Form::STATE_CUSTOM,
            ExtForm::buttons([
                'help' => ['subject' => 'upload'], 
                'submit' => [
                    'text'        => '#Upload', 
                    'iconCls'     => 'g-icon-svg g-icon_size_14 g-icon-m_upload', 
                    'handler'     => 'onFormAction',
                    'handlerArgs' => [
                        'routeRule' => 'submit',
                        'confirm'   => 'upload'
                    ]
                ],
                'cancel'
            ])
        );

        $this->form->items = [
            [
                'xtype'    => 'container',
                'padding'  => 7,
                'defaults' => [
                    'labelAlign' => 'right',
                    'labelWidth' => 110,
                    'width'      => '100%',
                    'allowBlank' => false
                ],
                'items' => [
                    // т.к. параметры ("_csrf", "X-Gjax") не передаются через заголовок, 
                    // то передаём их через метод POST
                    [
                        'xtype' => 'hidden',
                        'name'  => 'X-Gjax',
                        'value' => true
                    ],
                    [
                        'xtype' => 'hidden',
                        'name'  => Gm::$app->request->csrfParamName,
                        'value' => Gm::$app->request->getCsrfTokenFromHeader()
                    ],
                    [
                        'xtype' => 'hidden',
                        'name'  => 'path',
                        'value' => $this->pathId
                    ],
                    [
                        'xtype' => 'hidden',
                        'name'  => 'isDialogFolder',
                        'value' => $this->isDialogFolder ? 1 : 0
                    ],
                    [
                        'xtype' => 'hidden',
                        'name'  => 'dialog',
                        'value' => $this->dialogAlias
                    ],
                    [
                        'xtype'      => 'filefield',
                        'name'       => 'uploadFile',
                        'fieldLabel' => '#File name'
                    ]
                ]
            ]
        ];

        $this->form->items[] = [
            'xtype' => 'label',
            'ui'    => 'note',
            'html'  => 
                $this->folderProfile ? 
                    $this->creator->t(
                        'The file(s) will be downloaded according to the profile "{0}" of the media folder', 
                        [
                            $this->folderProfile['name'], 
                            Html::a(
                                $this->creator->t('(more details)'), 
                                '#', 
                                [
                                    'onclick' => ExtForm::jsAppWidgetLoad('@backend/references/folder-profiles/form/view/' . $this->folderProfile['id'])
                                ]
                            )
                        ]
                    ) :
                    $this->creator->t(
                        'The file(s) will be downloaded according to the parameters for downloading resources to the server {0}', 
                        [
                            Html::a(
                                $this->creator->t('(more details)'), 
                                '#', 
                                [
                                    'onclick' => ExtForm::jsAppWidgetLoad('@backend/config/upload')
                                ]
                            )
                        ]
                    )
        ];
        

        // окно компонента (Ext.window.Window Sencha ExtJS)
        $this->title = '#{upload.title}';
        $this->titleTpl = $this->title;
        $this->width = 470;
        $this->autoHeight = true;
        $this->layout = 'fit';
        $this->resizable = false;
        $this->iconCls = 'g-icon-m_upload';        
    }
}
