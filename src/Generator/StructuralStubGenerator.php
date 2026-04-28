<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Generator;

final class StructuralStubGenerator
{
    public function mageFactories(): string
    {
        return <<<'PHP'
<?php

class Mage
{
    /**
     * @param string $modelClass
     * @param mixed $arguments
     * @return object|null
     */
    public static function getModel($modelClass = '', $arguments = []) {}

    /**
     * @param string $modelClass
     * @param mixed $arguments
     * @return object|null
     */
    public static function getSingleton($modelClass = '', $arguments = []) {}

    /**
     * @param string $blockClass
     * @param mixed $arguments
     * @return object|null
     */
    public static function getBlockSingleton($blockClass = '', $arguments = []) {}

    /**
     * @param string $modelClass
     * @param mixed $arguments
     * @return object|null
     */
    public static function getResourceModel($modelClass, $arguments = []) {}

    /**
     * @param string $name
     * @return object
     */
    public static function helper($name) {}

    /**
     * @param mixed $code
     * @param string $type
     * @param mixed $options
     * @return object
     */
    public static function app($code = '', $type = 'store', $options = []) {}

    /**
     * @return object
     */
    public static function getConfig() {}

    /**
     * @param string $path
     * @param mixed $store
     * @return mixed
     */
    public static function getStoreConfig($path, $store = null) {}

    /**
     * @param string $path
     * @param mixed $store
     * @return bool
     */
    public static function getStoreConfigFlag($path, $store = null) {}

    /**
     * @param mixed $message
     * @param mixed $level
     * @param string $file
     * @param bool $forceLog
     * @return void
     */
    public static function log($message, $level = null, $file = '', $forceLog = false) {}

    /**
     * @param string $message
     * @return never
     */
    public static function throwException($message) {}
}

PHP;
    }

    public function magentoCore(): string
    {
        return <<<'PHP'
<?php

class Mage_Core_Block_Abstract extends Varien_Object
{
    /**
     * @param string $text
     * @param mixed ...$args
     * @return string
     */
    public function __($text, ...$args) {}

    /**
     * @param string $data
     * @param string|null $allowedTags
     * @return string
     */
    public function escapeHtml($data, $allowedTags = null) {}

    /**
     * @param string $path
     * @param array<string, mixed> $params
     * @return string
     */
    public function getUrl($path = '', array $params = []) {}

    /**
     * @return string
     */
    public function getFormKey() {}

    /**
     * @return object
     */
    public function getLayout() {}

    /**
     * @return object
     */
    public function getRequest() {}

    /**
     * @param string $name
     * @param mixed $block
     * @return $this
     */
    public function setChild($name, $block) {}

    /**
     * @param string $template
     * @return $this
     */
    public function setTemplate($template) {}

    /**
     * @param string $value
     * @return string
     */
    public function jsQuoteEscape($value) {}

    /**
     * @return void
     */
    protected function _construct() {}

    /**
     * @return $this
     */
    protected function _prepareLayout() {}
}

class Mage_Adminhtml_Block_Widget extends Mage_Core_Block_Abstract
{
    /**
     * @param mixed $value
     * @return $this
     */
    public function setId($value) {}
}

class Mage_Adminhtml_Block_Widget_Container extends Mage_Adminhtml_Block_Widget
{
    /** @var string */
    protected $_controller;

    /** @var string */
    protected $_blockGroup;

    /** @var string */
    protected $_headerText;

    /** @var string */
    protected $_mode;

    /**
     * @param string $buttonId
     * @return $this
     */
    public function _removeButton($buttonId) {}

    /**
     * @param string $buttonId
     * @param string $key
     * @param mixed $data
     * @return $this
     */
    public function _updateButton($buttonId, $key, $data) {}
}

class Mage_Adminhtml_Block_Widget_Grid extends Mage_Adminhtml_Block_Widget
{
    /**
     * @param mixed $value
     * @return $this
     */
    public function setDefaultSort($value) {}

    /**
     * @param string $value
     * @return $this
     */
    public function setDefaultDir($value) {}

    /**
     * @param bool $value
     * @return $this
     */
    public function setSaveParametersInSession($value) {}

    /**
     * @param mixed $collection
     * @return $this
     */
    public function setCollection($collection) {}

    /**
     * @param string $columnId
     * @param array<string, mixed> $column
     * @return $this
     */
    public function addColumn($columnId, array $column) {}

    /**
     * @param string $columnId
     * @return $this
     */
    public function removeColumn($columnId) {}

    /**
     * @param bool $value
     * @return $this
     */
    public function setUseAjax($value) {}

    /**
     * @return $this
     */
    protected function _prepareCollection() {}

    /**
     * @return $this
     */
    protected function _prepareColumns() {}
}

class Mage_Adminhtml_Block_Widget_Grid_Container extends Mage_Adminhtml_Block_Widget_Container {}
class Mage_Adminhtml_Block_Widget_Form extends Mage_Adminhtml_Block_Widget
{
    /**
     * @param Varien_Data_Form $form
     * @return $this
     */
    public function setForm(Varien_Data_Form $form) {}

    /**
     * @return $this
     */
    protected function _prepareForm() {}
}
class Mage_Adminhtml_Block_Widget_Form_Container extends Mage_Adminhtml_Block_Widget_Container {}
class Mage_Core_Block_Template extends Mage_Core_Block_Abstract {}
class Mage_Adminhtml_Block_Template extends Mage_Core_Block_Template {}
interface Mage_Adminhtml_Block_Widget_Tab_Interface {}

class Mage_Core_Model_Layout extends Varien_Object
{
    /**
     * @param string $type
     * @param string $name
     * @param array<string, mixed> $attributes
     * @return object|null
     */
    public function createBlock($type, $name = '', array $attributes = []) {}
}

class Mage_Adminhtml_Block_System_Config_Form_Field extends Mage_Adminhtml_Block_Widget
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {}
}

class Mage_Core_Controller_Front_Action extends Mage_Core_Controller_Varien_Action {}
class Mage_Adminhtml_Controller_Action extends Mage_Core_Controller_Varien_Action {}
class Mage_Core_Controller_Varien_Action
{
    /**
     * @return object
     */
    public function getRequest() {}

    /**
     * @return object
     */
    public function getResponse() {}

    /**
     * @return object
     */
    public function getLayout() {}

    /**
     * @param mixed $handles
     * @param bool $generateBlocks
     * @param bool $generateXml
     * @return $this
     */
    public function loadLayout($handles = null, $generateBlocks = true, $generateXml = true) {}

    /**
     * @param string $output
     * @return $this
     */
    public function renderLayout($output = '') {}

    /**
     * @param string $path
     * @param array<string, mixed> $arguments
     * @return $this
     */
    public function _redirect($path, $arguments = []) {}

    /**
     * @param string $action
     * @param string|null $controller
     * @param string|null $module
     * @param array<string, mixed>|null $params
     * @return void
     */
    public function _forward($action, $controller = null, $module = null, array $params = null) {}
}

class Mage_Core_Model_Resource_Setup
{
    /**
     * @return $this
     */
    public function startSetup() {}

    /**
     * @return $this
     */
    public function endSetup() {}

    /**
     * @return object
     */
    public function getConnection() {}

    /**
     * @param string|array<int, string> $tableName
     * @return string
     */
    public function getTable($tableName) {}

    /**
     * @param string $sql
     * @return $this
     */
    public function run($sql) {}

    /**
     * @param string|array<int, string> $tableName
     * @param string|array<int, string> $fields
     * @param string $indexType
     * @return string
     */
    public function getIdxName($tableName, $fields, $indexType = '') {}
}

class Mage_Sales_Model_Entity_Setup extends Mage_Core_Model_Resource_Setup
{
    /**
     * @param string $entityTypeId
     * @param string $code
     * @param array<string, mixed> $attr
     * @return $this
     */
    public function addAttribute($entityTypeId, $code, array $attr) {}
}

class Mage_Core_Model_Config
{
    /**
     * @return string
     */
    public function getTablePrefix() {}
}

class Mage_Core_Helper_Abstract
{
    /**
     * @param string $text
     * @param mixed ...$args
     * @return string
     */
    public function __($text, ...$args) {}

    /**
     * @param mixed $data
     * @return string
     */
    public function jsonEncode($data) {}
}

class Mage_Adminhtml_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @param string $path
     * @param array<string, mixed> $params
     * @return string
     */
    public function getUrl($path = '', array $params = []) {}
}

class Mage_Core_Helper_Data extends Mage_Core_Helper_Abstract {}

class Mage_Core_Model_Abstract extends Varien_Object
{
    /**
     * @param string $resourceModel
     * @return void
     */
    protected function _init($resourceModel) {}

    /**
     * @return Varien_Data_Collection
     */
    public function getCollection() {}
}

class Mage_Core_Model_Resource_Db_Abstract {}
class Mage_Core_Model_Resource_Db_Collection_Abstract extends Varien_Data_Collection {}
class Mage_Adminhtml_Model_Session extends Varien_Object
{
    /**
     * @param bool $clear
     * @return mixed
     */
    public function getFormData($clear = false) {}
}

class Mage_Shipping_Model_Carrier_Abstract extends Mage_Core_Model_Abstract {}
interface Mage_Shipping_Model_Carrier_Interface {}
class Mage_Adminhtml_Model_System_Config_Backend_Shipping_Tablerate extends Varien_Object {}
class Mage_Shipping_Model_Resource_Carrier_Tablerate extends Mage_Core_Model_Resource_Db_Abstract {}
class Mage_Shipping_Model_Resource_Carrier_Tablerate_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {}
class Mage_Sales_Model_Order extends Mage_Core_Model_Abstract {}
class Mage_Sales_Model_Order_Shipment extends Mage_Core_Model_Abstract {}
class Mage_Sales_Model_Order_Invoice extends Mage_Core_Model_Abstract {}
class Mage_Adminhtml_System_ConfigController extends Mage_Adminhtml_Controller_Action {}

PHP;
    }

    public function varien(): string
    {
        return <<<'PHP'
<?php

class Varien_Object
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = []) {}

    /**
     * @param string $key
     * @param mixed $index
     * @return mixed
     */
    public function getData($key = '', $index = null) {}

    /**
     * @param string|array<string, mixed> $key
     * @param mixed $value
     * @return $this
     */
    public function setData($key, $value = null) {}

    /**
     * @param string|null $key
     * @return $this
     */
    public function unsetData($key = null) {}

    /**
     * @param string $key
     * @return bool
     */
    public function hasData($key = '') {}

    /**
     * @param array<string, mixed> $arr
     * @return $this
     */
    public function addData(array $arr) {}

    /**
     * @return mixed
     */
    public function getId() {}

    /**
     * @param mixed $value
     * @return $this
     */
    public function setId($value) {}
}

class Varien_Data_Form extends Varien_Object
{
    /**
     * @param string $elementId
     * @param string $type
     * @param array<string, mixed> $config
     * @param mixed $after
     * @return mixed
     */
    public function addField($elementId, $type, $config, $after = false) {}

    /**
     * @param string $elementId
     * @param array<string, mixed> $config
     * @return Varien_Data_Form_Element_Fieldset
     */
    public function addFieldset($elementId, array $config) {}

    /**
     * @param array<string, mixed> $values
     * @return $this
     */
    public function setValues(array $values) {}

    /**
     * @param bool $flag
     * @return $this
     */
    public function setUseContainer($flag) {}
}

class Varien_Data_Form_Element_Abstract extends Varien_Object {}

class Varien_Data_Form_Element_Fieldset extends Varien_Data_Form_Element_Abstract
{
    /**
     * @param string $elementId
     * @param string $type
     * @param array<string, mixed> $config
     * @param mixed $after
     * @return mixed
     */
    public function addField($elementId, $type, $config, $after = false) {}
}

/**
 * @implements IteratorAggregate<int|string, mixed>
 */
class Varien_Data_Collection implements IteratorAggregate, Countable
{
    /**
     * @param mixed $item
     * @return $this
     */
    public function addItem($item) {}

    /**
     * @return array<int|string, mixed>
     */
    public function getItems() {}

    /**
     * @param string|array<int, string> $field
     * @param mixed $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null) {}

    /**
     * @return Traversable<int|string, mixed>
     */
    public function getIterator(): Traversable {}

    public function count(): int {}
}

class Varien_Db_Adapter_Interface {}
class Varien_Db_Adapter_Pdo_Mysql {}
class Varien_Db_Select {}
class Varien_Db_Ddl_Table {}

PHP;
    }
}
