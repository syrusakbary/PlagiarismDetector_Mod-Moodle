<?php  //$Id: edit_form.php,v 1.37.2.17 2009/02/13 10:01:15 stronk7 Exp $

require_once($CFG->libdir.'/formslib.php');


class plagiarismdetectorform extends moodleform {
	var $plugins;
	function plagiarismdetectorform ($plugins) {
		$this->plugins = $plugins;
		parent::moodleform();
	}
	function definition() {
		global $USER, $CFG;
		global $id, $aid,$pid, $block, $action, $choices, $basefile;
	
		/* First we create the form */
		$mform =& $this->_form;
		$mform->addElement('header', 'general', get_string('general', 'form'));
	
		/* Create the name of the Plagiarism Detector Instance input*/
		$mform->addElement('text', 'name', get_string('editname', 'plagiarismdetector'), '');
	
		/* Create the Plugin selector */
		$mform->addElement('select', 'plugin', get_string('editplugins', 'plagiarismdetector'), $this->plugins);
		$mform->setType('plugin', PARAM_NOTAGS);


		/* Create the Programing-Language selector */
		$choices = array('auto' => 'Auto','ada' => 'Ada', 'ascii' => 'ASCII', 'a8086' => 'a8086 assembly', 'c' => 'C', 'cc' => 'C++', 'csharp' => 'C#', 'fortran' => 'FORTRAN', 'haskell' => 'Haskell', 'java' => 'Java', 'javascript' => 'Javascript', 'lisp' => 'Lisp', 'matlab' => 'Matlab', 'mips' => 'MIPS assembly', 'ml' => 'ML', 'modula2' => 'Modula2', 'pascal' => 'Pascal', 'perl' => 'Perl', 'plsql' => 'PLSQL', 'prolog' => 'Prolog', 'python' => 'Python', 'scheme' => 'Scheme', 'spice' => 'Spice', 'vhdl' => 'VHDL', 'vb' => 'Visual Basic');
		$mform->addElement('select', 'language', get_string('editlanguage', 'plagiarismdetector'), $choices);
		$mform->setDefault('language', 'auto');
		$mform->setType('language', PARAM_NOTAGS);
	
		/* Create the Sensitivity input */
		$mform->addElement('text', 'sensitivity', get_string('editsensitivity', 'plagiarismdetector'), 'size="2"');
		$mform->setType('sensitivity', PARAM_INT);
		$mform->setDefault('sensitivity', '60');
	
		/* Create the Autonotify selector */
		$choices = array('1' => get_string('yes'),'0' => get_string('no'));
		$mform->addElement('select', 'autonotify', get_string('enableautonotify', 'plagiarismdetector'), $choices);
		$mform->setDefault('autonotify', '1');


		if ($action == 'create') 
			$str = get_string('create');
		else $str = get_string('update');
			$this->add_action_buttons(false, $str);

		if ($id) {
			$mform->addElement('hidden', 'id', $id);
			$mform->setType('id', PARAM_INT);
		}

		$mform->addElement('hidden', 'a', $aid);
		$mform->setType('a', PARAM_INT);
		$mform->addElement('hidden', 'action', $action);
		$mform->setType('action', PARAM_ACTION);
	}
}
?>
