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
use Gm\Backend\MediaFiles\Model\FileProperties;

/**
 * Виджет для формирования интерфейса окна редактирования файла.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Widget
 * @since 1.0
 */
class EditWindow extends \Gm\Panel\Widget\EditWindow
{

    /**
     * Идентификатор выбранного файла.
     * 
     * @var string|null
     */
    protected ?string $fileId = '';

    /**
     * Свойства выбранного файла.
     * 
     * @var FileProperties
     */
    protected FileProperties $fileProperties;

    /**
     * {@inheritdoc}
     */
    public array $passParams = [
         'fileProperties', 'fileId'
    ];

    /**
     * {@inheritdoc}
     */
    protected function init(): void
    {
        parent::init();

        // окно компонента (Ext.window.Window Sencha ExtJS)
        $this->cls = 'g-window_profile';
        $this->title = $this->creator->t('{edit.title}', [$this->fileProperties->getBaseName()]);
        $this->titleTpl = $this->title;
        $this->iconCls  = 'g-icon-svg gm-mediafiles__icon-edit';
        $this->layout = 'fit';
        $this->width = 700;
        $this->height = 500;
        $this->responsiveConfig = [
            'height < 700' => ['height' => '99%'],
            'width < 500' => ['width' => '99%'],
        ];
        $this->resizable = true;
        $this->maximizable = true;

        // панель формы (Gm.view.form.Panel GmJS)
        $this->form->router->setAll([
            'route' => Gm::alias('@match', '/edit'),
            'state' => Form::STATE_UPDATE,
            'rules' => [
                'view'   => '{route}/view',
                'update' => '{route}/perfom'
            ]
        ]);
        $this->form->buttons = ExtForm::buttons(
            [
                'help' => ['subject' => 'edit'], 
                'save',
                'cancel'
            ]
        );

        /** @var null|object|\Gm\Stdlib\BaseObject $viewer */
        $viewer = Gm::$app->widgets->get('gm.wd.codemirror', [
            'fileExtension' => $this->fileProperties->getExtension()
        ]);
        if ($viewer)
            $editor = $viewer->run();
        else
            $editor = [
                'xtype' => 'textarea',
                'anchor' => '100% 100%'
            ];
        $editor['value'] = $this->fileProperties->getContent();
        $editor['name']  = 'text';

        $this->form->items = [
            [
                'xtype' => 'hidden',
                'name'  => 'fileId',
                'value' => $this->fileId
            ],
            $editor
        ];
        
        // добавление в ответ скриптов 
        if ($viewer) {
            if (method_exists($viewer, 'initResponse')) {
                $viewer->initResponse($this->creator->controller->getResponse());
            }
        }
    }
}
