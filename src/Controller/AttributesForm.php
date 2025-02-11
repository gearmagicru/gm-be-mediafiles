<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\MediaFiles\Controller;

use Gm;
use Gm\Panel\Http\Response;
use Gm\Mvc\Module\BaseModule;
use Gm\Panel\Widget\EditWindow;
use Gm\Panel\Controller\FormController;
use Gm\Backend\MediaFiles\Model\Properties;
use Gm\Backend\MediaFiles\Widget\AttributesWindow;

/**
 * Контроллер информации о файле / папке.
 * 
 * Маршруты контроллера:
 * - 'attributes/file', выводит интерфейс формы c информацией о файле;
 * - 'attributes/folder', выводит интерфейс формы c информацией о папке.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Controller
 * @since 1.0
 */
class AttributesForm extends FormController
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Gm\Backend\MediaFiles\Module
     */
    public BaseModule $module;

    /**
     * Идентификатор выбранного файла / папки.
     * 
     * @var string|null
     */
    protected ?string $fileId = null;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $this
            ->on(self::EVENT_BEFORE_ACTION, function ($controller, $action, &$result) {
                switch ($action) {
                    case 'file': 
                    case 'folder': 
                        $this->fileId = Gm::$app->request->getPost('id');
                        if ($this->fileId) {
                            /** @var Properties $model */
                            $model = $this->getModel();
                            if (!$model->exists(true)) {
                                $this->getResponse()
                                    ->meta->error(Gm::t('app', 'Parameter "{0}" not specified', ['file']));
                                $result = false;
                                return;
                            }
                        } else {
                            $this->getResponse()
                                ->meta->error(Gm::t('app', 'Parameter "{0}" not specified', ['file']));
                            $result = false;
                            return;
                        }

                        // проверка папки выбранного файла, имеет ли она права на редактирование
                        if (!$this->module->mediaFolderCan('viewAttr', dirname($this->fileId))) {
                            $this->getResponse()
                                ->meta->error(
                                    $this->t('No media folder permission to perform this action'), 
                                    '', null, 'g-icon-svg g-icon_dlg-forbidden'
                            );
                            $result = false;
                            return;
                        }
                        break;
                }
            });
    }

    /**
     * {@inheritdoc}
     * 
     * @return Properties
     */
    public function getModel(string $name = null, array $config = []): ?Properties
    {
        // определение названия модели: 'FileProperties', 'FolderProperties'
        if ($name === null) {
            $name = ucfirst($this->actionName) . 'Properties';

            /** @var \Gm\Config\Config $settings */
            $settings = $this->module->getSettings();
            // значки файлов
            if ($settings && $settings->icons) {
                $config['icons'] = $settings->icons;
            }
            // идентификатор файла / папки
            $config['id'] = $this->fileId;
            // URL-адрес к значкам
            $config['fileIconsUrl'] = $this->module->getFileIconsUrl();
        }

        return parent::getModel($name, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function createWidget(): AttributesWindow
    {
        return new AttributesWindow([
            'properties' => $this->getModel(),
            'actionName' => $this->actionName
        ]);
    }

    /**
     * Действие "folder" выводит интерфейса формы "Информация о папке".
     * 
     * @return Response
     */
    public function folderAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var EditWindow $widget */
        $widget = $this->getWidget();
        $response
            ->setContent($widget->run())
            ->meta
                ->addWidget($widget);
        return $response;
    }

    /**
     * Действие "file" выводит интерфейса формы "Информация о файле".
     * 
     * @return Response
     */
    public function fileAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var \Gm\Panel\Widget\EditWindow $widget */
        $widget = $this->getWidget();
        $response
            ->setContent($widget->run())
            ->meta
                ->addWidget($widget);
        return $response;
    }
}
