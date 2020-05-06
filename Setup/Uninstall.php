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

namespace Mageplaza\ProductFeedSampleData\Setup;

use Exception;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * Class Uninstall
 * @package Mageplaza\ProductFeedSampleData\Setup
 */
class Uninstall implements UninstallInterface
{
    /**
     * @var State
     */
    private $state;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * Uninstall constructor.
     * @param State $state
     * @param Registry $registry
     * @param ProductFactory $productFactory
     */
    public function __construct(
        State $state,
        Registry $registry,
        ProductFactory $productFactory
    ) {
        $this->state = $state;
        $this->registry = $registry;
        $this->productFactory = $productFactory;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws LocalizedException
     * @throws Exception
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);
        $this->registry->register('isSecureArea', true);
        $product = $this->productFactory->create()->loadByAttribute('sku', 'mageplaza_product_feed_sample_product');
        if ($product) {
            $product->delete();
        }
        $this->registry->unregister('isSecureArea');

        $connection = $setup->getConnection();

        $tables = ['mageplaza_productfeed_history', 'mageplaza_productfeed_feed'];
        foreach ($tables as $tableName) {
            $table = $setup->getTable($tableName);
            $connection->delete($table);
            $connection->query(sprintf('ALTER TABLE %s AUTO_INCREMENT = 1', $table));
        }
    }
}
