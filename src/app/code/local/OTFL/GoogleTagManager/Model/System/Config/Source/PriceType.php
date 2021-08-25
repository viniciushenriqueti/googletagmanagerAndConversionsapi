<?php

/**
 * Used in creating options for Yes|No config value selection
 *
 */
class OTFL_GoogleTagManager_Model_System_Config_Source_PriceType
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label'=>'Preço Normal'),
            array('value' => 1, 'label'=>'Preco a vista (Desconto)'),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            0 => 'Preço Normal',
            1 => 'Preco a vista (Desconto)',
        );
    }

}
