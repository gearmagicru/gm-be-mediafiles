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
use Gm\Stdlib\BaseObject;
use Gm\Mvc\Module\BaseModule;
use Gm\Filesystem\Filesystem as Fs;

/**
 * Базовый класс свойств файла / папки.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Model
 * @since 1.0
 */
class Properties extends BaseObject
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Gm\Backend\MediaFiles\Module
     */
    public BaseModule $module;

    /**
     * Идентификатор файла / папки.
     * 
     * @var string|null
     */
    public ?string $id = null;

    /**
     * URL-адрес к значкам файлов.
     * 
     * @var string
     */
    public string $fileIconsUrl = '';

    /**
     * Название файла / папки.
     * 
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * {@inheritdoc}
     */
    public function configure(array $config): void
    {
        parent::configure($config);

        $this->setId($this->id);
    }

    /**
     * Устанавливает идентификатор файла / папки.
     * 
     * @param string|null $value Идентификатор файла / папки.
     * 
     * @return void
     */
    public function setId(?string $value): void
    {
        $this->id = $value;
        if ($value) {
            $name = $this->module->getSafePath($value);
            $this->name = $name ?: '';
        }
    }

    /**
     * Возвращает URL-адрес значка.
     * 
     * @return bool
     */
    public function getIcon(): string
    {
        if ($this->fileIconsUrl) {
            return $this->fileIconsUrl . 'list/file.svg';
        }
        return '';
    }

    /**
     * Возвращает URL-адрес предварительного просмотра файла / папки.
     * 
     * @return bool
     */
    public function getPreview(): string
    {
        return $this->getIcon();
    }

    /**
     * Проверяет, является ли файл / папка системной.
     * 
     * @return bool
     */
    public function isSystem(): bool
    {
        return false;
    }

    /** Проверяет, являются ли свойства файла.
     * 
     * @return bool
     */
    public function isFile(): bool
    {
        return false;
    }

    /** Проверяет, являются ли свойства папки.
     * 
     * @return bool
     */
    public function isFolder(): bool
    {
        return false;
    }

    /**
     * Определяет, доступен ли файл / папка для записи.
     * 
     * @return bool
     */
    public function isWritable(): bool
    {
        return is_writable($this->name);
    }

    /**
     * Определяет существование файла / папки и доступен ли он для чтения.
     * 
     * @return bool
     */
    public function isReadable(): bool
    {
        return is_readable($this->name);
    }

    /**
     * Возвращает время изменения индексного дескриптора файла.
     * 
     * @return string|false
     */
    public function getChangeTime(): string|false
    {
        $dateTimeFormat = Gm::$app->formatter->dateTimeFormat;

        /** @var int|false $time */
        $time = @filectime($this->name);
        return $time ? Gm::$app->formatter->toDateTime($time, $dateTimeFormat) : false;
    }

    /**
     *  Возвращает время последнего изменения файла.
     * 
     * @return string|false
     */
    public function getModifiedTime(): string|false
    {
        $dateTimeFormat = Gm::$app->formatter->dateTimeFormat;

        /** @var int|false $time */
        $time = @filemtime($this->name);
        return $time ? Gm::$app->formatter->toDateTime($time, $dateTimeFormat) : false;
    }

    /**
     * Возвращает время последнего доступа к файлу / папке.
     * 
     * @return string|false
     */
    public function getAccessTime(): string|false
    {
        $dateTimeFormat = Gm::$app->formatter->dateTimeFormat;

        /** @var int|false $time */
        $time = @fileatime($this->name);
        return $time ? Gm::$app->formatter->toDateTime($time, $dateTimeFormat) : false;
    }

    /**
     * Возвращает MIME-тип содержимого файла / папки.
     * 
     * @return string|false
     */
    public function getMimeType(): string|false
    {
        return $this->name ? mime_content_type($this->name) : false;
    }

    /**
     * Возвращает идентификатор владельца файла.
     * 
     * @return int|false
     */
    public function getOwnerId(): int|false
    {
        return @fileowner($this->name);
    }

    /**
     * Возвращает размер файла / папки.
     * 
     * @return string
     */
    public function getSize(): string
    {
        return '';
    }

    /**
     * Получает идентификатор группы файла / папки.
     * 
     * @return int|false
     */
    public function getGroupId(): int|false
    {
        return @filegroup($this->name);
    }

    /**
     * Возвращает права доступа файла / папки.
     * 
     * @param string $digit  Отображение прав доступа в виде восьмеричного числа
     * @param string $fullAccess Отображение полных прав доступа.
     * 
     * @return string|false
     */
    public function getPermissions(bool $digit = true, bool $fullAccess = true): string|false
    {
        return Fs::permissions($this->name, $digit, $fullAccess);
    }

    /**
     * Возвращает тип файла / папки.
     * 
     * @return string|false
     */
    public function getType(): string|false
    {
        return @filetype($this->name);
    }

    /**
     * Возвращает имя файла / папки.
     * 
     * @return string
     */
    public function getBaseName(): string
    {
        $info = $this->getPathInfo();
        return $this->name ? $info['basename'] : '';
    }

    /**
     * Возвращает путь к файлу / папке.
     * 
     * @return string
     */
    public function getDirName(): string
    {
        $info = $this->getPathInfo();
        return $this->name ? $info['dirname'] : '';
    }

    /**
     * @see Properties::getPathInfo()
     * 
     * @var array
     */
    protected array $_pathInfo;

    /**
     * Возвращает информацию о файле / папке.
     * 
     * @return array
     */
    public function getPathInfo(): array
    {
        if (!isset($this->_pathInfo)) {
            $this->_pathInfo = pathinfo($this->name);
        }
        return $this->_pathInfo;
    }

    /**
     * Проверяет, существует ли файл / папка.
     * 
     * @param bool $check Проверяет, является ли это  файл / папка.
     * 
     * @return bool
     */
    public function exists(bool $check = false): bool
    {
        return file_exists($this->name);
    }
}
