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
use Gm\Filesystem\Filesystem as Fs;

/**
 * Виджет для формирования интерфейса окна прав доступа файлу / папки.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Widget
 * @since 1.0
 */
class PermissionsWindow extends \Gm\Panel\Widget\EditWindow
{
    /**
     * Идентификатор выбранного файла.
     * 
     * @var string|null
     */
    protected ?string $fileId = '';

    /**
     * Действие: 'file', 'folder'.
     * 
     * @var string
     */
    protected string $actionName = '';

    /**
     * Название файла.
     * 
     * @var string
     */
    protected string $filename = '';

    /**
     * Разрешение.
     * 
     * @var string
     */
    protected string $permissions = '';

    /**
     * {@inheritdoc}
     */
    public array $passParams = [
        'actionName', 'fileId', 'permissions', 'filename'
    ];

    /**
     * {@inheritdoc}
     */
    protected function init(): void
    {
        parent::init();

        /** @var array $groups Группы прав доступа */
        $groups = Fs::permissionsToArray(intval($this->permissions, 8));

        // окно компонента (Ext.window.Window Sencha ExtJS)
        $this->width = 500;
        $this->autoHeight = true;
        $this->resizable = false;
        $this->title = $this->creator->t(
            '{permissions.' . $this->actionName . '.title}', 
            [$this->filename]
        );
        $this->titleTpl = $this->title;
        $this->iconCls  = 'g-icon-svg gm-mediafiles__icon-permissions';

        // панель формы (Gm.view.form.Panel GmJS)
        $this->form->makeViewID(); // для того, чтобы сразу использовать `$window->form->id`
        $this->form->controller = 'gm-be-mediafiles-pms';
        $this->form->router->setAll([
            'route' => Gm::alias('@match', '/permissions'),
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
                'help' => ['subject' => 'permissions'], 
                'save'  => ['text' => '#Apply', 'iconCls' => ''], 
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
                'name'  => 'fileId',
                'value' => $this->fileId
            ],
            [
                'xtype'  => 'container',
                'height' => 'auto',
                'layout' => 'column',
                'margin' => '0 0 5px 0',
                'items'  => [
                    [
                        'xtype'       => 'fieldset',
                        'columnWidth' => '0.333',
                        'margin'      => '2',
                        'title'       => '#Owner permission',
                        'defaults'    => [
                            'ui'         => 'switch',
                            'xtype'      => 'checkbox',
                            'inputValue' => 1,
                            'listeners'  => ['change'=> 'onCheckPermission']
                        ],
                        'items' => [
                            [
                                'boxLabel' => '#Read',
                                'id'       => $this->form->id . '__or',
                                'name'     => 'groups[owner][r]',
                                'value'    => !empty($groups['owner']['r'])
                            ],
                            [
                                'boxLabel' => '#Write',
                                'id'       => $this->form->id . '__ow',
                                'name'     => 'groups[owner][w]',
                                'value'    => !empty($groups['owner']['w'])
                            ],
                            [
                                'boxLabel' => '#Execution',
                                'id'       => $this->form->id . '__ox',
                                'name'     => 'groups[owner][x]',
                                'value'    => !empty($groups['owner']['x'])
                            ]
                        ]
                    ],
                    [
                        'xtype'       => 'fieldset',
                        'columnWidth' => '0.333',
                        'margin'      => '2',
                        'title'       => '#Group permission',
                        'defaults'    => [
                            'ui'         => 'switch',
                            'xtype'      => 'checkbox',
                            'inputValue' => 1,
                            'listeners'  => ['change'=> 'onCheckPermission']
                        ],
                        'items'=> [
                            [
                                'boxLabel' => '#Read',
                                'id'       => $this->form->id . '__gr',
                                'name'     => 'groups[group][r]',
                                'value'    => !empty($groups['group']['r'])
                            ],
                            [
                                'boxLabel' => '#Write',
                                'id'       => $this->form->id . '__gw',
                                'name'     => 'groups[group][w]',
                                'value'    => !empty($groups['group']['w'])
                            ],
                            [
                                'boxLabel' => '#Execution',
                                'id'       => $this->form->id . '__gx',
                                'name'     => 'groups[group][x]',
                                'value'    => !empty($groups['group']['x'])
                            ]
                        ]
                    ],
                    [
                        'xtype'       => 'fieldset',
                        'columnWidth' => '0.333',
                        'margin'      => '2',
                        'title'       => '#World permission',
                        'defaults'    => [
                            'ui'         => 'switch',
                            'xtype'      => 'checkbox',
                            'inputValue' => 1,
                            'listeners'  => ['change'=> 'onCheckPermission']
                        ],
                        'items'=> [
                            [
                                'boxLabel' => '#Read',
                                'id'       => $this->form->id . '__wr',
                                'name'     => 'groups[world][r]',
                                'value'    => !empty($groups['world']['r'])
                            ],
                            [
                                'boxLabel' => '#Write',
                                'id'       => $this->form->id . '__ww',
                                'name'     => 'groups[world][w]',
                                'value'    => !empty($groups['world']['w'])
                            ],
                            [
                                'boxLabel' => '#Execution',
                                'id'       => $this->form->id . '__wx',
                                'name'     => 'groups[world][x]',
                                'value'    => !empty($groups['world']['x'])
                            ]
                        ]
                    ]
                ]
           ],
            [
                'id'         => $this->form->id . '__permissions',
                'xtype'      => 'textfield',
                'fieldLabel' => '#Numerical value',
                'labelAlign' => 'right',
                'labelWidth' => 156,
                'name'       => 'permissions',
                'value'      => $this->permissions,
                'maxLength'  => 10,
                'width'      => 315
            ]
        ];
        $this
            ->setNamespaceJS('Gm.be.mediafiles')
            ->addRequire('Gm.be.mediafiles.PermissionsController');
    }
}
