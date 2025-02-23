<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogSearch\Test\Unit\Model\Layer\Filter;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory;
use Magento\Catalog\Model\Layer\Filter\Item;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\CatalogSearch\Model\Layer\Filter\Category
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryTest extends TestCase
{
    private $itemDataBuilder;

    /**
     * @var Category|MockObject
     */
    private $category;

    /**
     * @var Collection|MockObject
     */
    private $fulltextCollection;

    /**
     * @var Layer|MockObject
     */
    private $layer;

    /**
     * @var \Magento\Catalog\Model\Layer\Filter\DataProvider\Category|MockObject
     */
    private $dataProvider;

    /**
     * @var \Magento\CatalogSearch\Model\Layer\Filter\Category
     */
    private $target;

    /** @var RequestInterface|MockObject */
    private $request;

    /** @var  ItemFactory|MockObject */
    private $filterItemFactory;

    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $dataProviderFactory = $this->getMockBuilder(
            CategoryFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();

        $this->dataProvider = $this->getMockBuilder(\Magento\Catalog\Model\Layer\Filter\DataProvider\Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCategoryId', 'getCategory'])
            ->getMock();

        $dataProviderFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->dataProvider);

        $this->category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getChildrenCategories', 'getIsActive'])
            ->getMock();

        $this->dataProvider->expects($this->any())
            ->method('getCategory', 'isValid')
            ->willReturn($this->category);

        $this->layer = $this->getMockBuilder(Layer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getState', 'getProductCollection'])
            ->getMock();

        $this->fulltextCollection = $this->getMockBuilder(
            Collection::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['addCategoryFilter', 'getFacetedData', 'getSize'])
            ->getMock();

        $this->layer->expects($this->any())
            ->method('getProductCollection')
            ->willReturn($this->fulltextCollection);

        $this->itemDataBuilder = $this->getMockBuilder(DataBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addItemData', 'build'])
            ->getMock();

        $this->filterItemFactory = $this->getMockBuilder(
            ItemFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $filterItem = $this->getMockBuilder(
            Item::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['setFilter', 'setLabel', 'setValue', 'setCount'])
            ->getMock();
        $filterItem->expects($this->any())
            ->method($this->anything())->willReturnSelf();
        $this->filterItemFactory->expects($this->any())
            ->method('create')
            ->willReturn($filterItem);

        $escaper = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->setMethods(['escapeHtml'])
            ->getMock();
        $escaper->expects($this->any())
            ->method('escapeHtml')
            ->willReturnArgument(0);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->target = $objectManagerHelper->getObject(
            \Magento\CatalogSearch\Model\Layer\Filter\Category::class,
            [
                'categoryDataProviderFactory' => $dataProviderFactory,
                'layer' => $this->layer,
                'itemDataBuilder' => $this->itemDataBuilder,
                'filterItemFactory' => $this->filterItemFactory,
                'escaper' => $escaper,
            ]
        );
    }

    /**
     * @param $requestValue
     * @param $idValue
     * @param $isIdUsed
     * @dataProvider applyWithEmptyRequestDataProvider
     */
    public function testApplyWithEmptyRequest($requestValue, $idValue)
    {
        $requestField = 'test_request_var';
        $idField = 'id';

        $this->target->setRequestVar($requestField);

        $this->category->expects($this->once())
            ->method('getChildrenCategories')
            ->willReturn([]);

        $this->request->expects($this->at(0))
            ->method('getParam')
            ->with($requestField)
            ->willReturnCallback(
                function ($field) use ($requestField, $idField, $requestValue, $idValue) {
                    switch ($field) {
                        case $requestField:
                            return $requestValue;
                        case $idField:
                            return $idValue;
                    }
                }
            );

        $result = $this->target->apply($this->request);
        $this->assertSame($this->target, $result);
    }

    /**
     * @return array
     */
    public function applyWithEmptyRequestDataProvider()
    {
        return [
            [
                'requestValue' => null,
                'id' => 0,
            ],
            [
                'requestValue' => 0,
                'id' => false,
            ],
            [
                'requestValue' => 0,
                'id' => null,
            ]
        ];
    }

    public function testApply()
    {
        $categoryId = 123;
        $requestVar = 'test_request_var';

        $this->target->setRequestVar($requestVar);
        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnCallback(
                function ($field) use ($requestVar, $categoryId) {
                    $this->assertContains($field, [$requestVar, 'id']);
                    return $categoryId;
                }
            );

        $this->dataProvider->expects($this->once())
            ->method('setCategoryId')
            ->with($categoryId)->willReturnSelf();

        $this->category->expects($this->once())
            ->method('getId')
            ->willReturn($categoryId);

        $this->category->expects($this->once())
            ->method('getChildrenCategories')
            ->willReturn([]);

        $this->fulltextCollection->expects($this->once())
            ->method('addCategoryFilter')
            ->with($this->category)->willReturnSelf();

        $this->target->apply($this->request);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetItems()
    {
        $this->category->expects($this->any())
            ->method('getIsActive')
            ->willReturn(true);

        $category1 = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getName', 'getIsActive'])
            ->getMock();
        $category1->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(120);
        $category1->expects($this->once())
            ->method('getName')
            ->willReturn('Category 1');
        $category1->expects($this->once())
            ->method('getIsActive')
            ->willReturn(true);

        $category2 = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getName', 'getIsActive'])
            ->getMock();
        $category2->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(5641);
        $category2->expects($this->once())
            ->method('getName')
            ->willReturn('Category 2');
        $category2->expects($this->once())
            ->method('getIsActive')
            ->willReturn(true);

        $category3 = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getName', 'getIsActive'])
            ->getMock();
        $category3->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(777);
        $category3->expects($this->never())
            ->method('getName');
        $category3->expects($this->once())
            ->method('getIsActive')
            ->willReturn(true);

        $categories = [
            $category1,
            $category2,
            $category3,
        ];
        $this->category->expects($this->once())
            ->method('getChildrenCategories')
            ->willReturn($categories);

        $facetedData = [
            120 => ['count' => 10],
            5641 => ['count' => 45],
            777 => ['count' => 80],
        ];

        $this->fulltextCollection->expects($this->once())
            ->method('getSize')
            ->willReturn(50);

        $this->fulltextCollection->expects($this->once())
            ->method('getFacetedData')
            ->with('category')
            ->willReturn($facetedData);

        $builtData = [
            [
                'label' => 'Category 1',
                'value' => 120,
                'count' => 10,
            ],
            [
                'label' => 'Category 2',
                'value' => 5641,
                'count' => 45,
            ],
        ];

        $this->itemDataBuilder->expects($this->at(0))
            ->method('addItemData')
            ->with(
                'Category 1',
                120,
                10
            )
            ->willReturnSelf();
        $this->itemDataBuilder->expects($this->at(1))
            ->method('addItemData')
            ->with(
                'Category 2',
                5641,
                45
            )
            ->willReturnSelf();
        $this->itemDataBuilder->expects($this->once())
            ->method('build')
            ->willReturn($builtData);

        $this->target->getItems();
    }
}
