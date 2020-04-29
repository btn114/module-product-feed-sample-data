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
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
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
     * ProductFeed constructor.
     * @param SampleDataContext $sampleDataContext
     * @param File $file
     * @param Reader $moduleReader
     * @param Filesystem\Io\File $ioFile
     * @param Filesystem $filesystem
     * @param FeedFactory $feedFactory
     * @param HistoryFactory $historyFactory
     * @throws FileSystemException
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        File $file,
        Reader $moduleReader,
        Filesystem\Io\File $ioFile,
        Filesystem $filesystem,
        FeedFactory $feedFactory,
        HistoryFactory $historyFactory
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->file = $file;
        $this->feedFactory = $feedFactory;
        $this->historyFactory = $historyFactory;
        $this->moduleReader = $moduleReader;
        $this->ioFile = $ioFile;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * @param array $fixtures
     *
     * @throws Exception
     */
    public function install(array $fixtures)
    {
        foreach ($fixtures as $fileName) {
            $file = $this->fixtureManager->getFixture($fileName);
            if (!$this->file->isExists($file)) {
                continue;
            }

            $rows = $this->csvReader->getData($file);

            $header = array_shift($rows);
$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/mp_test.log');
$logger = new \Zend\Log\Logger();
$logger->addWriter($writer);
$logger->info('a');
            switch ($fileName) {
                case 'Mageplaza_ProductFeedSampleData::fixtures/mageplaza_productfeed_feed.csv':
                    $logger->info('b');

                    foreach ($rows as $row) {
                        $data = [];
                        foreach ($row as $key => $value) {
                            $data[$header[$key]] = $value;
                        }
                        $oldId = $data['feed_id'];
                        $data = $this->processFeedData($data);
                        $feed = $this->feedFactory->create()
                            ->addData($data)
                            ->save();
                        $this->idMapFields[$oldId] = $feed->getId();
                    }
                    break;
                case 'Mageplaza_ProductFeedSampleData::fixtures/mageplaza_productfeed_history.csv':
                    foreach ($rows as $row) {
                        $logger->info('c');

                        $data = [];
                        foreach ($row as $key => $value) {
                            $data[$header[$key]] = $value;
                        }
                        $data = $this->processHistoryData($data);
                        $this->historyFactory->create()
                            ->addData($data)
                            ->save();
                    }
                    break;
                default:
                    return;
            }
        }
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

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function processHistoryData($data)
    {
        unset($data['id']);
        $data['feed_id'] = $this->idMapFields[$data['feed_id']];
        $fileName = $data['file'];
        $file = $this->getFilePath('/files/feed/' . $fileName);
        $this->ioFile->checkAndCreateFolder('pub/media/mageplaza/feed');

        $destinationFilePath = $this->mediaDirectory->getAbsolutePath('mageplaza/feed/' . $fileName);
        if ($this->ioFile->fileExists($destinationFilePath)) {
            return $data;
        }

        $this->ioFile->cp($file, $destinationFilePath);

        return $data;
    }

    /**
     * @param $path
     * @return string
     */
    protected function getFilePath($path)
    {
        if (!$this->viewDir) {
            $this->viewDir = $this->moduleReader->getModuleDir(
                Dir::MODULE_VIEW_DIR,
                'Mageplaza_ProductFeedSampleData'
            );
        }

        return $this->viewDir . $path;
    }
}
