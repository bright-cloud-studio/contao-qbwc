<?php

/**
* Bright Cloud Studio's Contao QBWC
*
* Copyright (C) 2024-2025 Bright Cloud Studio
*
* @package    bright-cloud-studio/contao-qbwc
* @link       https://www.brightcloudstudio.com/
* @license    http://opensource.org/licenses/lgpl-3.0.html
**/

namespace Bcs\Module;

class ModReceiveData extends \Contao\Module
{


    // Main function called when the page this is on loads
    protected function compile()
	{
		if (!isset($_GET['keywords']) && Input::post('FORM_SUBMIT') == 'tl_search')
		{
			//$_GET['keywords'] = Input::post('keywords');
		}

        echo "Module: compile()';
        die();
        
	}


    
}
