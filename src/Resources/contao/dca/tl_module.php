<?php

/** Palettes */
$GLOBALS['TL_DCA']['tl_module']['palettes']['mod_receive_data']	= '{title_legend},name,type; {receive_data_legend}, last_run, minute_notification_threshold;';

/** Fields */
$GLOBALS['TL_DCA']['tl_module']['fields']['last_run'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['last_run'],
	'inputType'               => 'text',
	'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'mandatory'=>false, 'tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_module']['fields']['minute_notification_threshold'] = array
(
  'label'                   => &$GLOBALS['TL_LANG']['tl_module']['minute_notification_threshold'],
  'inputType'               => 'text',
  'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50')
);
