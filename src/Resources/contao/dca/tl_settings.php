<?php

// Add our custom fields to the palette
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = str_replace('{files_legend', '{qbwc_legend}, qbwc_username, qbwc_password;{files_legend', $GLOBALS['TL_DCA']['tl_settings']['palettes']['default']);

// Create those custom fields
$GLOBALS['TL_DCA']['tl_settings']['fields'] += [
    'qbwc_username' => [
        'label'             => &$GLOBALS['TL_LANG']['tl_settings']['qbwc_username'],
        'inputType'         => 'text',
        'eval'              => ['mandatory' => 'false', 'tl_class' => 'w50'],
    ],
    'qbwc_password' => [
        'label'             => &$GLOBALS['TL_LANG']['tl_settings']['qbwc_password'],
        'inputType'         => 'text',
        'eval'              => ['mandatory' => 'false', 'tl_class' => 'w50'],
    ]
];
