<?php

declare(strict_types=1);

namespace Shopsys\FrameworkBundle\Component\UploadedFile\Config;

class UploadedFileEntityConfig
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var \Shopsys\FrameworkBundle\Component\UploadedFile\Config\UploadedFileTypeConfig[]
     */
    protected $types;

    /**
     * @param string $entityName
     * @param string $entityClass
     * @param \Shopsys\FrameworkBundle\Component\UploadedFile\Config\UploadedFileTypeConfig[] $types
     */
    public function __construct(string $entityName, string $entityClass, array $types)
    {
        $this->entityName = $entityName;
        $this->entityClass = $entityClass;
        $this->types = $types;
    }

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @param string $typeName
     * @return \Shopsys\FrameworkBundle\Component\UploadedFile\Config\UploadedFileTypeConfig
     */
    public function getTypeByName(string $typeName = UploadedFileTypeConfig::DEFAULT_TYPE_NAME): UploadedFileTypeConfig
    {
        if (!array_key_exists($typeName, $this->types)) {
            throw new \Shopsys\FrameworkBundle\Component\UploadedFile\Config\Exception\UploadedFileTypeConfigNotFoundException($typeName);
        }

        return $this->types[$typeName];
    }
}
