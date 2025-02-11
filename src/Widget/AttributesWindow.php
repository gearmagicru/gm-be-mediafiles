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
use Gm\Panel\Helper\HtmlNavigator as Nav;
use Gm\Backend\MediaFiles\Model\Archive;
use Gm\Backend\MediaFiles\Model\Properties;

/**
 * Виджет для формирования интерфейса окна загрузки файла.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Widget
 * @since 1.0
 */
class AttributesWindow extends \Gm\Panel\Widget\EditWindow
{
    /**
     * Свойства файла / папки.
     * 
     * @var Properties
     */
    protected Properties $properties;

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
        'properties', 'actionName'
    ];

    /**
     * {@inheritdoc}
     */
    protected function init(): void
    {
        parent::init();

        /** @var Gm\Backend\MediaFiles\Model\FileProperties|Gm\Backend\MediaFiles\Model\FolderProperties $properties */
        $properties = $this->properties;

        // окно компонента (Ext.window.Window Sencha ExtJS)
        $this->ui = 'light';
        $this->cls = 'g-window_profile';
        $this->width = 550;
        $this->autoHeight = true;
        $this->autoScroll = true;
        $this->responsiveConfig = [
            'height < 550' => ['height' => '99%'],
            'width < 550' => ['width' => '99%'],
        ];
        $this->resizable = false;
        $this->title = $this->creator->t(
            '{attributes.' . $this->actionName . '.title}', [$properties->getBaseName()]
        );
        $this->titleTpl = $this->title;
        $this->iconCls  = 'g-icon-svg g-icon-m_color_base gm-mediafiles__icon-attributes';

        // панель формы (Gm.view.form.Panel GmJS)
        $this->form->router->setAll([
            'route' => Gm::alias('@match', '/attributes'),
            'state' => Form::STATE_CUSTOM,
            'rules' => [
                'folder' => '{route}/folder',
                'file'   => '{route}/file'
            ]
        ]);
        $this->form->bodyPadding = '5 10 5 10';
        $this->form->defaults = [
            'xtype'      => 'displayfield',
            'ui'         => 'parameter',
            'labelWidth' => 150,
            'labelAlign' => 'right'
        ];
        $this->form->items = [
            [
                'xtype'      => 'container',
                'height'     => 150,
                'style'      => [
                    'background'    => 'center / contain no-repeat url(' .  $properties->getPreview() . ')',
                    'margin-bottom' => '7px'
                ]
            ],
            [
                'ui'         => 'parameter-head',
                'fieldLabel' => '#Name',
                'labelWidth' => 60,
                'name'       => 'name',
                'value'      => $properties->getBaseName()
            ],
            [
                'ui'           => 'parameter-head',
                'labelWidth'   => 60,
                'fieldLabel'   => '#Type',
                'fieldBodyCls' => ($value = $properties->getType()) ? '' : 'gm-mediafiles-error',
                'name'         => 'type',
                'value'        => '#' . ($value ?: 'impossible to determine')
            ],
            [
                'fieldLabel' => '#Path',
                'name'       => 'path',
                'labelWidth' => 60,
                'value'      => $properties->getDirName()
            ],
            [
                'xtype' => 'label',
                'ui'    => 'header-line',
                'style' => 'border-bottom:1px solid #e0e0e0'
            ],
            [
                'fieldLabel' => '#Size',
                'name'       => 'size',
                'value'      => $properties->isFile() ? $properties->getSize() : '#unknown'
            ],
            [
                'fieldLabel'   => '#Permissions',
                'fieldBodyCls' => ($value = $properties->getPermissions()) ? '' : 'gm-mediafiles-error',
                'name'         => 'permissions',
                'value'        => $value ?: '#impossible to determine'
            ],
            [
                'fieldLabel'   => '#MIME-type',
                'fieldBodyCls' => ($value = $properties->getMimeType()) ? '' : 'gm-mediafiles-error',
                'name'         => 'mime',
                'value'        => $value ?: '#impossible to determine'
            ],
            [
                'fieldLabel'   => '#Owner ID',
                'fieldBodyCls' => ($value = $properties->getOwnerId()) ? '' : 'gm-mediafiles-error',
                'name'         => 'ownerId',
                'value'        => $value ?: '#impossible to determine'
            ],
            [
                'fieldLabel'   => '#Group ID',
                'fieldBodyCls' => ($value = $properties->getGroupId()) ? '' : 'gm-mediafiles-error',
                'name'         => 'groupId',
                'value'        => $value ?: '#impossible to determine'
            ],
            [
                'fieldLabel' => '#Readable',
                'name'       => 'readable',
                'value'      => Nav::checkIcon($properties->isReadable(), 17)
            ],
            [
                'fieldLabel' => '#Writable',
                'name'       => 'writable',
                'value'      => Nav::checkIcon($properties->isWritable(), 17),
            ],
            [
                'fieldLabel' => $properties->isFile() ? '#The configuration file' : '#System folder',
                'hidden'     => !$properties->isSystem(),
                'name'       => 'system',
                'value'      => Nav::checkIcon($properties->isSystem(), 17),
            ],
            [
                'xtype' => 'label',
                'ui'    => 'header-line',
                'style' => 'border-bottom:1px solid #e0e0e0'
            ],
            [
                'fieldLabel'   => $properties->isFile() ? '#Changing a file' : '#Changing a folder',
                'fieldBodyCls' => ($value = $properties->getChangeTime()) ? '' : 'gm-mediafiles-error',
                'name'         => 'ctime',
                'value'        => $value ?: '#impossible to determine'
            ],
            [
                'fieldLabel'   => '#Access time',
                'fieldBodyCls' => ($value = $properties->getAccessTime()) ? '' : 'gm-mediafiles-error',
                'name'         => 'actime',
                'value'        => $value ?: '#impossible to determine'
            ],
            [
                'fieldLabel'   => '#Change time',
                'fieldBodyCls' => ($value = $properties->getModifiedTime()) ? '' : 'gm-mediafiles-error',
                'name'         => 'mtime',
                'value'        => $value ?: '#impossible to determine'
            ],
        ];

        if ($properties->isFile()) {
            // если архив
            if ($properties->isArchive()) {
                $info = (new Archive(['filename' => $properties->getFilename()]))->getInfo();
                if ($info) {
                    $this->form->items[] = [
                        'xtype' => 'label',
                        'ui'    => 'header-line',
                        'style' => 'border-bottom:1px solid #e0e0e0'
                    ];
                    $this->form->items[] = [
                        'fieldLabel'   => '#Archive type',
                        'fieldBodyCls' => $info['name'] ? '' : 'gm-mediafiles-error',
                        'name'         => 'archiveType',
                        'value'        => $info['name'] ? $this->creator->t($info['name']) : '#impossible to determine'
                    ];
                    $this->form->items[] = [
                        'fieldLabel'   => '#Files in the archive',
                        'fieldBodyCls' => ($value = $info['count']) ? '' : 'gm-mediafiles-error',
                        'name'         => 'archiveFiles',
                        'value'        => $value ?: '#impossible to determine'
                    ];
                }
            }
            // если изображение
            else if ($properties->isImage()) {
                $info = $properties->getImageInfo();
                if ($info) {
                    $this->form->items[] = [
                        'xtype' => 'label',
                        'ui'    => 'header-line',
                        'style' => 'border-bottom:1px solid #e0e0e0'
                    ];
                    $this->form->items[] = [
                        'fieldLabel'   => '#Width',
                        'fieldBodyCls' => ($value = $info['width']) ? '' : 'gm-mediafiles-error',
                        'name'         => 'width',
                        'value'        => $value ?: '#impossible to determine'
                    ];
                    $this->form->items[] = [
                        'fieldLabel'   => '#Height',
                        'fieldBodyCls' => ($value = $info['height']) ? '' : 'gm-mediafiles-error',
                        'name'         => 'height',
                        'value'        => $value ?: '#impossible to determine'
                    ];
                    $this->form->items[] = [
                        'fieldLabel' => '#Color',
                        'name'       => 'color',
                        'value'      => $info['color'] ? '#yes' : '#no'
                    ];
                    if ($info['comment']) {
                        $this->form->items[] = [
                            'fieldLabel' => '#Comment',
                            'name'       => 'comment',
                            'value'      => $info['comment']
                        ];
                    }
                    if ($info['copyright']) {
                        $this->form->items[] = [
                            'fieldLabel' => 'Copyright',
                            'name'       => 'copyright',
                            'value'      => $info['copyright']
                        ];
                    }
                    if ($info['software']) {
                        $this->form->items[] = [
                            'fieldLabel' => 'Software',
                            'name'       => 'software',
                            'value'      => $info['software']
                        ];
                    }
                }
            }
        }    
    }
}
