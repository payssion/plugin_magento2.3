<?php
/**
 * Created by PhpStorm.
 * User: chankit
 * Date: 2019-04-29
 * Time: 21:21
 */
namespace Payssion\Payment\Model;
class Log extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'payssion_log';

    protected $_cacheTag = 'payssion_log';

    protected $_eventPrefix = 'payssion_log';

    protected function _construct()
    {
        $this->_init('Payssion\Payment\Model\ResourceModel\Log');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }
}