<?php  // $Id: view.php,v 1.42 2007/08/17 12:15:33 skodak Exp $

	require_once("../../config.php");
	require_once("lib.php");

	$id = optional_param('id', 0, PARAM_INT);  // Course Module ID
	$a  = optional_param('a', 0, PARAM_INT);   // Assignment ID
	$action  = optional_param('action', 0, PARAM_NOTAGS);   // Assignment ID
	if ($id) {
		if (!$plagiarism = get_record("plagiarismdetector", "id", $id)) {
			error("Plagiarism is incorrect");
		}
		if (!$assignment = get_record("assignment", "id", $plagiarism->assignment)) {
			error("Attachment is incorrect");
		}
	}
	else {
		if (!$assignment = get_record("assignment", "id", $a)) {
			error("Attachment is incorrect");
		}
	}
	if (! $course = get_record("course", "id", $assignment->course)) {
		error("Course is misconfigured");
	}
	if (! $cm = get_coursemodule_from_instance("assignment", $assignment->id, $course->id)) {
		error("Course Module ID was incorrect");
	}

	require_login($course, true, $cm);

	$instance = new plagiarismdetector_base($plagiarism,$assignment,$cm,$course);
