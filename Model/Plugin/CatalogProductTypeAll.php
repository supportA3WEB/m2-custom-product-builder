<?php
/**
 * Copyright (c) 2017 Indigo Geeks, Inc. All rights reserved.
 *
 * General.
 * The custom product builder software and documentation accompanying this License
 * whether on disk, in read only memory, on any other media or in any other form (collectively
 * the “Software”) are licensed, not sold, to you by copyright holder, Indigo Geeks, Inc.
 * (“Buildateam”) for use only under the terms of this License, and Buildateam reserves all rights
 * not expressly granted to you. The rights granted herein are limited to Buildateam’s intellectual
 * property rights in the Buildateam Software and do not include any other patents or
 * intellectual property rights. You own the media on which the Buildateam Software is
 * recorded but Buildateam and/or Buildateam’s licensor(s) retain ownership of the Software
 * itself.
 *
 * Permitted License Uses and Restrictions.
 * This License allows you to install and use one (1) copy of the Software.
 * This License does not allow the Software to exist on more than one production domain.
 * Except as and only to the extent expressly permitted in this License or by applicable
 * law, you may not copy, decompile, reverse engineer, disassemble, attempt to derive
 * the source code of, modify, or create derivative works of the Software or any part
 * thereof. Any attempt to do so is a violation of the rights of Buildateam and its licensors of
 * the Software. If you breach this restriction, you may be subject to prosecution and
 * damages.
 *
 * Transfer.
 * You may not rent, lease, lend or sublicense the Software.
 *
 * Termination.
 * This License is effective until terminated. Your rights under this
 * License will terminate automatically without notice from Buildateam if you fail to comply
 * with any term(s) of this License. Upon the termination of this License, you shall cease
 * all use of the Buildateam Software and destroy all copies, full or partial, of the Buildateam
 * Software.
 *
 * THIS SOFTWARE IS PROVIDED BY COPYRIGHT HOLDER "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING,
 * BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL COPYRIGHT HOLDER BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * THE SOFTWARE IS NOT INTENDED FOR USE IN WHICH THE FAILURE OF
 * THE SOFTWARE COULD LEAD TO DEATH, PERSONAL INJURY, OR SEVERE PHYSICAL OR ENVIRONMENTAL DAMAGE.
 */

namespace Buildateam\CustomProductBuilder\Model\Plugin;

use \Magento\Catalog\Model\Product;
use \Magento\Framework\App\ProductMetadataInterface;
use \Magento\Framework\DataObject;
use \Magento\Framework\Serialize\SerializerInterface;

class CatalogProductTypeAll
{
    /**
     * @var bool
     */
    private $isJsonInfoByRequest = true;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param ProductMetadataInterface $productMetadata
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        SerializerInterface $serializer
    ) {
        if (version_compare($productMetadata->getVersion(), '2.2.0', '<')) {
            $this->isJsonInfoByRequest = false;
        }
        $this->serializer = $serializer;
    }

    /**
     * @param $subject
     * @param array|string $result
     * @return array|string
     */
    public function afterPrepareForCartAdvanced($subject, $result)
    {
        $this->addOptions($subject, $result);

        return $result;
    }

    /**
     * @param $subject
     * @param callable $proceed
     * @param Product $product
     * @return mixed
     */
    public function aroundGetOrderOptions($subject, callable $proceed, Product $product)
    {
        $optionArr = $proceed($product);
        if ($additionalOptions = $product->getCustomOption('additional_options')) {
            $optionArr['additional_options'] = $this->isJsonInfoByRequest ?
                json_decode($additionalOptions->getValue(), true) :
                $this->serializer->unserialize($additionalOptions->getValue());
        }

        if ($cpbOptions = $product->getCustomOption('options')) {
            $optionArr['options'] = $this->isJsonInfoByRequest ?
                json_decode($cpbOptions->getValue(), true) :
                $this->serializer->unserialize($cpbOptions->getValue());
        }

        return $optionArr;
    }

    /**
     * @param $subject
     * @param callable $proceed
     * @param DataObject $buyRequest
     * @param $product
     * @param $processMode
     * @return mixed
     */
    public function aroundProcessConfiguration($subject, callable $proceed, DataObject $buyRequest, $product)
    {
        $products = $proceed($buyRequest, $product);
        $this->addOptions($subject, $products);

        return $products;
    }

    /**
     * @param $subject
     * @param $result
     */
    public function addOptions($subject, $result)
    {
        /** @var Product $product */
        foreach ($result as &$product) {
            if ($product->getCustomOption('info_buyRequest') === null) {
                continue;
            }

            /* Retrieve technical data of product that was added to cart */
            $buyRequest = $product->getCustomOption('info_buyRequest')->getData('value');
            if ($this->isJsonInfoByRequest) {
                $productInfo = json_decode($buyRequest, true);
            } else {
                $productInfo = $this->serializer->unserialize($buyRequest);
            }

            if (!isset($productInfo['properties']) || (!isset($productInfo['technicalData']['breakdownData'])
                    && $product->getCustomOption('additional_options'))) {
                continue;
            }

            $addOptions = [];
            unset($productInfo['properties']['configId']);
            foreach ($productInfo['properties'] as $propertyName => $propertyValue) {
                $propertyValue = preg_replace('/(.*)(\s+\(.*\))/', '$1', $propertyValue);
                $addOptions[] = [
                    'label' => __($propertyName)->getText(),
                    'value' => $propertyValue,
                    'print_value' => $propertyValue,
                    'option_id' => null,
                    'option_type' => 'text',
                    'custom_view' => false,
                ];
            };
            $product->addCustomOption('additional_options', $this->isJsonInfoByRequest ?
                json_encode($addOptions) : $this->serializer->serialize($addOptions));

            $cpbOptions = [];
            if (isset($productInfo['technicalData']['additionalData']['images']) &&
                count($productInfo['technicalData']['additionalData']['images'])) {
                foreach ($productInfo['technicalData']['additionalData']['images'] as $image) {
                    $cpbOptions[] = [
                        'label' => 'Uploaded Image',
                        'value' => $image,
                        'print_value' => $image,
                        'option_id' => null,
                        'option_type' => 'cpb_download',
                        'custom_view' => true,
                        'option_value' => [
                            'title' => __('Download Image')->getText(),
                            'link' => $image,
                            'direct' => true
                        ]
                    ];
                }
            }
            if (isset($productInfo['technicalData']['additionalData']['texts']) &&
                count($productInfo['technicalData']['additionalData']['texts'])) {
                foreach ($productInfo['technicalData']['additionalData']['texts'] as $texts) {
                    foreach ($texts as $key => $value) {
                        $params = [
                            'label' => $key,
                            'value' => $value,
                            'print_value' => $value,
                            'option_id' => null,
                            'option_type' => $key == 'fontSource' ? 'cpb_download' : 'text',
                            'custom_view' => true,
                        ];

                        if ($key == 'fontSource') {
                            $params['option_value'] = [
                                'title' => __('Download font')->getText(),
                                'link' => $value,
                                'direct' => true
                            ];
                        }
                        $cpbOptions[] = $params;
                    }
                }
            }
            if (!empty($cpbOptions)) {
                $product->addCustomOption('options', $this->isJsonInfoByRequest ?
                    json_encode($cpbOptions) : $this->serializer->serialize($cpbOptions));
            }
        }
    }
}
