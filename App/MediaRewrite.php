<?php
/**
 * Copyright © 2021 MagestyApps. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MagestyApps\WebImages\App;

use Closure;
use Magento\Catalog\Model\Config\CatalogMediaConfig;
use Magento\Catalog\Model\View\Asset\PlaceholderFactory;
use Magento\Catalog\Model\Product\Media\ConfigInterface as MediaConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\MediaStorage\App\Media;
use Magento\MediaStorage\Model\File\Storage\ConfigFactory;
use Magento\MediaStorage\Model\File\Storage\Response;
use Magento\MediaStorage\Model\File\Storage\SynchronizationFactory;
use Magento\MediaStorage\Service\ImageResize;
use MagestyApps\WebImages\Helper\ImageHelper;

class MediaRewrite extends Media
{
    /**
     * @var
     */
    private $relativeFileName;

    /**
     * @var MediaConfig
     */
    private $imageConfig;

    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $directoryPub;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $directoryMedia;

    /**
     * MediaRewrite constructor.
     * @param ConfigFactory $configFactory
     * @param SynchronizationFactory $syncFactory
     * @param Response $response
     * @param Closure $isAllowed
     * @param string $mediaDirectory
     * @param string $configCacheFile
     * @param string $relativeFileName
     * @param Filesystem $filesystem
     * @param PlaceholderFactory $placeholderFactory
     * @param State $state
     * @param ImageResize $imageResize
     * @param File $file
     * @param MediaConfig $imageConfig
     * @param ImageHelper $imageHelper
     * @param CatalogMediaConfig|null $catalogMediaConfig
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        ConfigFactory $configFactory,
        SynchronizationFactory $syncFactory,
        Response $response,
        Closure $isAllowed,
        $mediaDirectory,
        $configCacheFile,
        $relativeFileName,
        Filesystem $filesystem,
        PlaceholderFactory $placeholderFactory,
        State $state,
        ImageResize $imageResize,
        File $file,
        MediaConfig $imageConfig,
        ImageHelper $imageHelper,
        CatalogMediaConfig $catalogMediaConfig = null
    ) {
        parent::__construct(
            $configFactory,
            $syncFactory,
            $response,
            $isAllowed,
            $mediaDirectory,
            $configCacheFile,
            $relativeFileName,
            $filesystem,
            $placeholderFactory,
            $state,
            $imageResize,
            $file,
            $catalogMediaConfig
        );

        $this->relativeFileName = $relativeFileName;
        $this->imageConfig = $imageConfig;
        $this->imageHelper = $imageHelper;

        $this->directoryPub = $filesystem->getDirectoryWrite(
            DirectoryList::PUB,
            Filesystem\DriverPool::FILE
        );
        $this->directoryMedia = $filesystem->getDirectoryWrite(
            DirectoryList::MEDIA,
            Filesystem\DriverPool::FILE
        );
    }

    /**
     * Do not resize vector images,
     * just copy them to the cache folder instead
     *
     * @return ResponseInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function launch(): ResponseInterface
    {
        if ($this->imageHelper->isVectorImage($this->relativeFileName)) {
            $originalImage = $this->getOriginalImage($this->relativeFileName);
            $originalImagePath = $this->directoryMedia->getAbsolutePath(
                $this->imageConfig->getMediaPath($originalImage)
            );

            $this->directoryMedia->copyFile(
                $originalImagePath,
                $this->directoryPub->getAbsolutePath($this->relativeFileName)
            );
        }

        return parent::launch(); // TODO: Change the autogenerated stub
    }

    /**
     * Find the path to the original image of the cache path
     *
     * @param string $resizedImagePath
     * @return string
     */
    private function getOriginalImage(string $resizedImagePath): string
    {
        return preg_replace('|^.*?((?:/([^/])/([^/])/\2\3)?/?[^/]+$)|', '$1', $resizedImagePath);
    }
}
