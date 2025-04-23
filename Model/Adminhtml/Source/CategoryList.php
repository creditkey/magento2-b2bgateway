<?php
namespace CreditKey\B2BGateway\Model\Adminhtml\Source;

class CategoryList implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Catalog\Model\Category\Tree
     */
    private $treeModel;

    /**
     * Stored array of optinos
     *
     * @var array
     */
    private $options;

    /**
     * Construct
     *
     * @param \Magento\Catalog\Model\Category\Tree $treeModel
     */
    public function __construct(\Magento\Catalog\Model\Category\Tree $treeModel)
    {
        $this->treeModel = $treeModel;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            // Get full category tree
            /**
 * @var \Magento\Catalog\Model\Category $rootCategory
*/
            $rootCategory = $this->treeModel->getRootNode();
            /**
 * @var \Magento\Catalog\Api\Data\CategoryTreeInterface $categoryTree
*/
            $categoryTree = $this->treeModel->getTree($rootCategory);

            // build options from tree hierarchy
            $this->buildCategoryOptions([$categoryTree]);
        }
        return $this->options;
    }

    /**
     * Recursively build options from nested category tree hierarchy
     *
     * @param \Magento\Catalog\Api\Data\CategoryTreeInterface[] $categories
     * @param integer                                           $depth
     *
     * @return void
     */
    private function buildCategoryOptions(array $categories, $depth = 0)
    {
        // Use prepend chars to represent nested hierarchy
        $prependLabel = str_repeat(" ~ ", $depth);

        foreach ($categories as $category) {
            $this->options[] = [
                "value" => $category->getId(),
                "label" => $prependLabel . $category->getName()
            ];

            $this->buildCategoryOptions($category->getChildrenData(), $depth + 1);
        }
    }
}
