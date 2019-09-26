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

namespace Buildateam\CustomProductBuilder\Setup;

use \Magento\Framework\Setup\UpgradeSchemaInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class UpgradeSchema
 * @package Buildateam\CustomProductBuilder\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '0.1.3', '<')) {
            $this->_changeAttributeColumnType($setup);
        }

        if (version_compare($context->getVersion(), '0.1.4', '<')) {
            $this->_createCpbProductConfigTable($setup);
        }

        if (version_compare($context->getVersion(), '0.1.5', '<')) {
            $this->_addImagePathToProductConfigTable($setup);
        }

        if (version_compare($context->getVersion(), '0.1.6', '<')) {
            $this->_changeVariationColumnName($setup);
        }

        $setup->endSetup();
    }

    /**
     * Change attribute value column type
     *
     * @param SchemaSetupInterface $setup
     */
    private function _changeAttributeColumnType(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->changeColumn(
            $setup->getTable('catalog_product_entity_text'),
            'value',
            'value',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '16M',
                'nullable' => true,
                'comment' => 'Value'
            ]
        );
    }

    private function _createCpbProductConfigTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('cpb_product_config'))
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity ID'
            )
            ->addColumn(
                'config_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                20,
                ['nullable' => false],
                'Config ID'
            )
            ->addColumn(
                'technical_data',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '16M',
                ['nullable' => true],
                'Technical data'
            )
            ->setComment('Custom Product Builder Product Config Table');
        $setup->getConnection()->createTable($table);
    }

    /**
     * Add image path column
     *
     * @param SchemaSetupInterface $setup
     */
    private function _addImagePathToProductConfigTable($setup)
    {
        $setup->getConnection()->dropColumn($setup->getTable('cpb_product_config'), 'image');
        $setup->getConnection()->addColumn(
            $setup->getTable('cpb_product_config'),
            'image',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Image path'
            ]
        );

        $setup->getConnection()->changeColumn(
            $setup->getTable('cpb_product_config'),
            'config_id',
            'config_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => false,
                'comment' => 'Config ID'
            ]
        );
    }

    /**
     * Change variation column name
     *
     * @param SchemaSetupInterface $setup
     */
    private function _changeVariationColumnName($setup)
    {
        $setup->getConnection()->changeColumn(
            $setup->getTable('cpb_product_config'),
            'config_id',
            'variation_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => false,
                'comment' => 'Config ID'
            ]
        );
    }
}
