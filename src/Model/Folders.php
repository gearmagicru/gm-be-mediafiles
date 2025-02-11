<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\MediaFiles\Model;

use Gm;
use Gm\Config\Config;
use Gm\Mvc\Module\BaseModule;
use Gm\Panel\Data\Model\NodesModel;
use Gm\Backend\References\MediaFolders\Model\MediaFolder;

/**
 * Модель данных дерева папок.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\Workspace\Model
 * @since 1.0
 */
class Folders extends NodesModel
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Gm\Backend\MediaFiles\Module
     */
    public BaseModule $module;

    /**
     * Стили папок.
     * 
     * Имеет вид: `['folder' => 'style']`.
     * 
     * @var array
     */
    public array $foldersCls = [
        'public'  => 'public',
        'vendor'  => 'vendor',
        'config'  => 'config',
        'runtime' => 'runtime',
    ];

    /**
     * {@inheritdoc}
     */
    public string $nodeParam = 'path';

    /**
     * Настройки модуля.
     * 
     * @var Config|null
     */
    protected ?Config $settings;

    /**
     * Идентификатор корневой папки.
     *
     * @var string
     */
    protected string $rootNodeId;

    /**
     * Псевдоним (виртуальная папка) узла дерева.
     * 
     * Псевдоним установлен, если значение свойства {@see Folders::$nodeId} имеет символ '@'.
     * Псевдоним это виртуальная папка и может иметь ссылку на реальную папку.
     * 
     * @var MediaFolder|null
     */
    public ?MediaFolder $node;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $this->settings = $this->module->getSettings();
        $this->rootNodeId = $this->settings->folderRootId ?: '@media';
    }

    /**
     * @return MediaFolder|null
     * 
     */
    public function getNode(): ?MediaFolder
    {
        if (!isset($this->node)) {
            $this->node = $this->module->createMediaFolder()->getByAlias($this->getIdentifier());
        }
        return $this->node;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): mixed
    {
        if (!isset($this->nodeId)) {
            $this->nodeId = Gm::$app->request->getQuery($this->nodeParam);
        }
        return $this->nodeId;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodes(): array
    {
        $nodes = [];

        /** @var MediaFolder|null $node */
        $node = $this->getNode();
        if ($node && $node->hasChildren()) {
            /** @var array $folders */
            $folders = $node->getChildren();
            foreach ($folders as $folder) {
                $isLeaf = empty($folder['count']);
                $node = [
                    'id'        => $folder['alias'],
                    'folderId'  => $folder['id'], // для редактирования медиапапки из панели инструментов
                    'profileId' => $folder['profile_id'], // для редактирования профиля медиапапки из панели инструментов
                    'text'      => $folder['name'],
                    'leaf'      => $isLeaf,
                    'iconCls'   => 'x-tree-icon-parent'
                ];
                if ($folder['icon_small']) {
                    $node['icon'] = $folder['icon_small'];
                }
                if ($folder['icon_cls']) {
                    $node['iconCls'] = $folder['icon_cls'];
                }
                $nodes[] = $node;
            }
        }
        return $nodes;
    }
}
