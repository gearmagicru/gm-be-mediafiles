<?php
/**
 * Этот файл является частью расширения модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\MediaFiles\Controller;

use Gm;
use Gm\Panel\Http\Response;
use Gm\Panel\Widget\TabWidget;
use Gm\Panel\Controller\BaseController;
use Gm\Backend\MediaFiles\Widget\Desk as DeskWidget;

/**
 * Контроллер панели менеджера медиафайлов.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Controller
 * @since 1.0
 */
class Desk extends BaseController
{
    /**
     * {@inheritdoc}
     */
    protected string $defaultAction = 'view';

    /**
     * {@inheritdoc}
     */
    public function createWidget(): DeskWidget
    {
        /** @var DeskWidget $desk */
        $desk = new DeskWidget();

        // панель вкладки компонента (Ext.tab.Panel Sencha ExtJS)
        $desk->id = 'tab';  // tab => gm-mediafiles-tab

        /** @var \Gm\Config\Config $settings */
        $settings = $this->module->getSettings();
        // панель дерева папок
        $desk->folderTree->applySettings($settings);
        // панель отображения медиафайлов
        $desk->filePanel->applySettings($settings);
        return $desk;
    }

    /**
     * Действие "view" выводит интерфейс панели.
     * 
     * События:
     * - gm.be.mediafiles:onDeskView
     * 
     * @return Response
     */
    public function viewAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var TabWidget $widget */
        $widget = $this->getWidget();
        // если была ошибка при формировании виджета
        if ($widget === false) {
            return $response;
        }

        // сброс фильтра файлов
        $store = $this->module->getStorage();
        $store->directFilter = null;

        Gm::$app->doEvent($this->makeAppEventName(), [$this, $widget]);

        $response
            ->setContent($widget->run())
            ->meta
                ->addWidget($widget);
        return $response;
    }
}
