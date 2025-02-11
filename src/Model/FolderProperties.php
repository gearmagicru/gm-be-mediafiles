<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\MediaFiles\Model;

/**
 * Класс свойств папки.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Model
 * @since 1.0
 */
class FolderProperties extends Properties
{
    /**
     * {@inheritdoc}
     */
    public function isFolder(): bool
    {
        return true;
    }

  /**
     * Определяет, является ли текущая папка медиапапкой.
     * 
     * Например, если идентификатор папки '@media/img', то она медиапапка.
     * 
     * @return bool
     */
    public function isMediaFolder(): bool
    {
        return $this->id && $this->id[0] === '@';
    }

    public function hasAliasPath(): bool
    {
        return $this->id && $this->id[0] === '@';
    }

    /**
     * Возвращает имя папки из указанного ранее идентификатора.
     * 
     * @return string|null
     */
    public function getFolder(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function isSystem(): bool
    {
        if ($this->name) {
            $basename = $this->getBaseName();
            return $basename[0] === '.';
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon(): string
    {
        $basename = $this->getBaseName();
        if ($this->fileIconsUrl && $basename) {
            return $this->fileIconsUrl . 'list/' . ($this->icons[$basename] ?? 'folder') . '.svg';
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function exists(bool $check = false): bool
    {
        if ($check) {
            if (file_exists($this->name)) {
                return !is_file($this->name);
            } else
                return false;
        }
        return file_exists($this->name);
    }
}
