<?php

namespace Buildateam\CustomProductBuilder\Block\Adminhtml\Product\Edit;

use \Magento\Framework\View\Element\Template;


class Builder extends Template
{
    public function __construct(Template\Context $context, array $data = [])
    {
        $this->setTemplate('product/edit/builder.phtml');

        parent::__construct($context, $data);
    }


    protected function _toHtml()
    {
        return parent::_toHtml(); // TODO: Change the autogenerated stub
    }


}