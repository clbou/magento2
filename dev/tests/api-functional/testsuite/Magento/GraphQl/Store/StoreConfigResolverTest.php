<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Store;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreConfigInterface;
use Magento\Store\Api\StoreConfigManagerInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\StoreResolverInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test the GraphQL endpoint's StoreConfigs and AvailableStores queries
 */
class StoreConfigResolverTest extends GraphQlAbstract
{

    /** @var  ObjectManager */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoApiDataFixture Magento/Store/_files/store.php
     * @magentoConfigFixture default_store store/information/name Default Store
     * @throws NoSuchEntityException
     */
    public function testGetStoreConfig(): void
    {
        /** @var  StoreConfigManagerInterface $defaultStoreConfigsManager */
        $defaultStoreConfigsManager = $this->objectManager->get(StoreConfigManagerInterface::class);
        /** @var StoreResolverInterface $storeResolver */
        $storeResolver = $this->objectManager->get(StoreResolverInterface::class);
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);
        $storeId = $storeResolver->getCurrentStoreId();
        $store = $storeRepository->getById($storeId);
        /** @var StoreConfigInterface $defaultStoreConfig */
        $defaultStoreConfig = current($defaultStoreConfigsManager->getStoreConfigs([$store->getCode()]));
        $query
            = <<<QUERY
{
  storeConfig {
    id,
    code,
    website_id,
    locale,
    base_currency_code,
    default_display_currency_code,
    timezone,
    weight_unit,
    base_url,
    base_link_url,
    base_static_url,
    base_media_url,
    secure_base_url,
    secure_base_link_url,
    secure_base_static_url,
    secure_base_media_url,
    store_name
  }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertArrayHasKey('storeConfig', $response);
        $this->validateStoreConfig($defaultStoreConfig, $response['storeConfig']);
    }

    /**
     * @magentoApiDataFixture Magento/Store/_files/store.php
     * @magentoConfigFixture default_store store/information/name Default Store
     * @magentoConfigFixture test_store store/information/name Test Store
     * @throws Exception
     */
    public function testAvailableStoreConfigs(): void
    {
        /** @var  StoreConfigManagerInterface $defaultStoreConfigsManager */
        $defaultStoreConfigsManager = $this->objectManager->get(StoreConfigManagerInterface::class);
        $storeConfigs = $defaultStoreConfigsManager->getStoreConfigs();

        $query
            = <<<QUERY
{
  availableStores {
    id,
    code,
    website_id,
    locale,
    base_currency_code,
    default_display_currency_code,
    timezone,
    weight_unit,
    base_url,
    base_link_url,
    base_static_url,
    base_media_url,
    secure_base_url,
    secure_base_link_url,
    secure_base_static_url,
    secure_base_media_url,
    store_name
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        $this->assertArrayHasKey('availableStores', $response);
        foreach ($storeConfigs as $key => $storeConfig) {
            $this->validateStoreConfig($storeConfig, $response['availableStores'][$key]);
        }
    }

    /**
     * Validate Store Config Data
     *
     * @param StoreConfigInterface $storeConfig
     * @param array $responseConfig
     */
    private function validateStoreConfig($storeConfig, $responseConfig): void
    {
        /* @var $scopeConfig ScopeConfigInterface */
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $this->assertEquals($storeConfig->getId(), $responseConfig['id']);
        $this->assertEquals($storeConfig->getCode(), $responseConfig['code']);
        $this->assertEquals($storeConfig->getLocale(), $responseConfig['locale']);
        $this->assertEquals($storeConfig->getBaseCurrencyCode(), $responseConfig['base_currency_code']);
        $this->assertEquals(
            $storeConfig->getDefaultDisplayCurrencyCode(),
            $responseConfig['default_display_currency_code']
        );
        $this->assertEquals($storeConfig->getTimezone(), $responseConfig['timezone']);
        $this->assertEquals($storeConfig->getWeightUnit(), $responseConfig['weight_unit']);
        $this->assertEquals($storeConfig->getBaseUrl(), $responseConfig['base_url']);
        $this->assertEquals($storeConfig->getBaseLinkUrl(), $responseConfig['base_link_url']);
        $this->assertEquals($storeConfig->getBaseStaticUrl(), $responseConfig['base_static_url']);
        $this->assertEquals($storeConfig->getBaseMediaUrl(), $responseConfig['base_media_url']);
        $this->assertEquals($storeConfig->getSecureBaseUrl(), $responseConfig['secure_base_url']);
        $this->assertEquals($storeConfig->getSecureBaseLinkUrl(), $responseConfig['secure_base_link_url']);
        $this->assertEquals($storeConfig->getSecureBaseStaticUrl(), $responseConfig['secure_base_static_url']);
        $this->assertEquals($storeConfig->getSecureBaseMediaUrl(), $responseConfig['secure_base_media_url']);
        $this->assertEquals($scopeConfig->getValue(
            'store/information/name',
            ScopeInterface::SCOPE_STORE,
            $storeConfig->getId()
        ), $responseConfig['store_name']);
    }
}
