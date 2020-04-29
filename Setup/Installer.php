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
use Magento\Framework\Setup;
use Mageplaza\ProductFeedSampleData\Model\ProductFeed;

/**
 * Class Installer
 * @package Mageplaza\ProductFeedSampleData\Setup
 */
class Installer implements Setup\SampleData\InstallerInterface
{
    /**
     * @var ProductFeed
     */
    private $abandonedCart;

    /**
     * Installer constructor.
     *
     * @param ProductFeed $abandonedCart
     */
    public function __construct(
        ProductFeed $abandonedCart
    ) {
        $this->abandonedCart = $abandonedCart;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function install()
    {
        $this->abandonedCart->install([
            'Mageplaza_ProductFeedSampleData::fixtures/mageplaza_productfeed_feed.csv',
            'Mageplaza_ProductFeedSampleData::fixtures/mageplaza_productfeed_history.csv'
        ]);
    }
}
