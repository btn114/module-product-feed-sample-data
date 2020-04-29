<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_ProductFeedSampleData
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\ProductFeedSampleData\Model;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Mageplaza\ProductFeed\Helper\Data;
use Mageplaza\ProductFeed\Model\FeedFactory;
use Mageplaza\ProductFeed\Model\HistoryFactory;

/**
 * Class ProductFeed
 * @package Mageplaza\ProductFeedSampleData\Model
 */
class ProductFeed
{
    /**
     * @var array
     */
    protected $idMapFields = [];
    /**
     * @var FixtureManager
     */
    protected $fixtureManager;

    /**
     * @var Csv
     */
    protected $csvReader;

    /**
     * @var File
     */
    protected $file;
    /**
     * @var FeedFactory
     */
    protected $feedFactory;

    protected $viewDir = '';
    /**
     * @var Reader
     */
    protected $moduleReader;
    /**
     * @var Filesystem\Io\File
     */
    protected $ioFile;

    /**
     * @var WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var HistoryFactory
     */
    protected $historyFactory;
    /**
     * @var ProductFactory
     */
    protected $productFactory;
    /**
     * @var ProductRepository
     */
    protected $productRepository;
    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var StockItemInterfaceFactory
     */
    protected $stockItemInterfaceFactory;
    /**
     * @var Data
     */
    protected $helperData;
    /**
     * @var CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * ProductFeed constructor.
     * @param SampleDataContext $sampleDataContext
     * @param File $file
     * @param Filesystem\Io\File $ioFile
     * @param Filesystem $filesystem
     * @param ProductFactory $productFactory
     * @param ProductRepository $productRepository
     * @param StockItemInterfaceFactory $stockItemInterfaceFactory
     * @param DirectoryList $directoryList
     * @param CollectionFactory $categoryCollectionFactory
     * @param Data $helperData
     * @param FeedFactory $feedFactory
     * @throws FileSystemException
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        File $file,
        Filesystem\Io\File $ioFile,
        Filesystem $filesystem,
        ProductFactory $productFactory,
        ProductRepository $productRepository,
        StockItemInterfaceFactory $stockItemInterfaceFactory,
        DirectoryList $directoryList,
        CollectionFactory $categoryCollectionFactory,
        Data $helperData,
        FeedFactory $feedFactory
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->file = $file;
        $this->feedFactory = $feedFactory;
        $this->ioFile = $ioFile;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->directoryList = $directoryList;
        $this->stockItemInterfaceFactory = $stockItemInterfaceFactory;
        $this->helperData = $helperData;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @param array $fixtures
     *
     * @throws Exception
     */
    public function install(array $fixtures)
    {
        $productId = $this->createNewSampleProduct();
        foreach ($fixtures as $fileName) {
            $file = $this->fixtureManager->getFixture($fileName);
            if (!$this->file->isExists($file)) {
                continue;
            }

            $rows = $this->csvReader->getData($file);

            $header = array_shift($rows);
            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                $data = $this->processFeedData($data);
                $feed = $this->feedFactory->create()
                    ->addData($data)
                    ->save();
                $feed->setMatchingProductIds([$productId]);
                $this->helperData->generateAndDeliveryFeed($feed);
            }
            break;
        }
    }

    /**
     * @throws FileSystemException
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    protected function createNewSampleProduct()
    {
        // check product is exists
        try {
            $product = $this->productRepository->get('mageplaza_product_feed_sample_product');
        } catch (NoSuchEntityException $e) {
            $product = null;
        }

        // create new sample product if not exits
        if (!$product || !$product->getId()) {

            /** @var Product $product */
            $product = $this->productFactory->create();

        }

        $catCollection = $this->categoryCollectionFactory->create();
        $ids = $catCollection->getAllIds();

        $product->setTypeId('simple')
            ->setAttributeSetId(4)
            ->setName('Mageplaza Product Feed Sample Product')
            ->setSku('mageplaza_product_feed_sample_product')
            ->setDescription('Description for product')
            ->setCategoryIds($ids)
            ->setPrice(0.01)
            ->setQty(100)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED);
        $product = $this->setProductImage($product, 'https://picsum.photos/400');

        /** @var StockItemInterface $stockItem */
        $stockItem = $this->stockItemInterfaceFactory->create();
        $stockItem->setQty(100)
            ->setIsInStock(true);
        $extensionAttributes = $product->getExtensionAttributes();
        $extensionAttributes->setStockItem($stockItem);

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->productRepository;
        $productRepository->save($product);

        return $product->getId();
    }

    /**
     * @param Product $product
     * @param $imageUrl
     * @param bool $visible
     * @param array $imageType
     * @return bool|string
     * @throws FileSystemException
     * @throws Exception
     */
    public function setProductImage(
        $product,
        $imageUrl,
        $visible = false,
        $imageType = ['image', 'small_image', 'thumbnail']
    ) {
        /** @var string $tmpDir */
        $tmpDir = $this->getMediaDirTmpDir();
        /** create folder if it is not exists */
        $this->ioFile->checkAndCreateFolder($tmpDir);
        $pathInfo = $this->ioFile->getPathInfo($imageUrl);
        $fileName = $pathInfo['basename'] . '.jpg';
        /** @var string $newFileName */
        $newFileName = $tmpDir . $fileName;
        /** read file from URL and copy it to the new destination */
        $result = $this->ioFile->read($imageUrl, $newFileName);
        if ($result) {
            /** add saved file to the $product gallery */
            $product->addImageToMediaGallery($newFileName, $imageType, true, $visible);
        }
        return $product;
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    protected function getMediaDirTmpDir()
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'tmp';
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function processFeedData($data)
    {
        unset($data['feed_id']);

        return $data;
    }
}
