<?php

use Contao\Config;

$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = str_replace('{files_legend', '{oepnai_legend}, openai_model, openai_prompt, openai_automatic, openai_extensions;{files_legend', $GLOBALS['TL_DCA']['tl_settings']['palettes']['default']);


$GLOBALS['TL_DCA']['tl_settings']['fields'] += [
    'openai_extensions' => [
        'label'             => &$GLOBALS['TL_LANG']['tl_settings']['openai_extensions'],
        'inputType'         => 'text',
        'default'           => 'Based on this image, create a search engine optimized alt text (under 15 words)',
        'eval'              => array('mandatory'=>true, 'tl_class'=>'w50', 'default'=> 'Based on this image, create a search engine optimized alt text (under 15 words)'),
    ]
];
