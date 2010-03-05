<?php

require_once($CFG->dirroot.'/mod/assignment/lib.php');

$text = new admin_setting_configfile('plagiarismdetector_source', get_string('plagiarismsource', 'plagiarismdetector'), get_string('configsource', 'plagiarismdetector'), $CFG->dirroot.'/mod/plagiarismdetector/vendor/PlagiarismDetector-PHP/plagiarismDetector.php', PARAM_NOTAGS);
$settings->add($text);

$options= array();
if (file_exists($CFG->plagiarismdetector_source)) {
	include_once($CFG->plagiarismdetector_source);
	$pla =new plagiarismDetector();
	$plugins = $pla->aviablePlugins();
	
	foreach ($plugins as $plugin) {
		$info = $pla->info($plugin);
		$options[$plugin] = $info['name'];
	}
	$settings->add(new admin_setting_configmulticheckbox('plagiarismdetector_plugins', get_string('plugins', 'plagiarismdetector'), get_string('configplugins', 'plagiarismdetector'), false, $options));
}
$settings->add(new admin_setting_configcheckbox('plagiarismdetector_enableautonotify', get_string('enableautonotify', 'plagiarismdetector'), get_string('configautonotify', 'plagiarismdetector'), 1));

$settings->add(new admin_setting_configcheckbox('plagiarismdetector_enableunpack', get_string('enableunpack', 'plagiarismdetector'), get_string('configunpack', 'plagiarismdetector'), 1));
//$settings->add(new admin_setting_heading('plagiarismdetector_ac_heading', get_string('pluginac', 'plagiarismdetector')));
