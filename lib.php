<?PHP  // $Id$
/**
 * Standard base class
 */
class plagiarismdetector_base {

	var $cm;
	var $course;
	var $assignment;
	var $strassignment;
	var $strassignments;
	var $strsubmissions;
	var $strlastmodified;
	var $pagetitle;
	var $usehtmleditor;
	var $defaultformat;
	var $context;
	var $type;
	var $plagiarism;
	var $plagiarismDetector;
	
	function plagiarismdetector_base($plagiarism=NULL,$assignment=NULL,$cm=NULL,$course=NULL) {
		global $COURSE;
		global $CFG;
		$this->cfg = $CFG;
		
		if ($cm) {
			$this->cm = $cm;
		}
		else if (! $this->cm = get_coursemodule_from_id('assignment', $cm->id)) {
			error('Course Module ID was incorrect');
		}

		$this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);

		if ($course) {
			$this->course = $course;
		}
		else if ($this->cm->course == $COURSE->id) {
			$this->course = $COURSE;
		}
		else if (! $this->course = get_record('course', 'id', $this->cm->course)) {
			error('Course is misconfigured');
		}

		if ($assignment) {
			$this->assignment = $assignment;
		} else if (! $this->assignment = get_record('assignment', 'id', $this->cm->instance)) {
			error('assignment ID was incorrect');
		}
		
		if (!file_exists($this->cfg->plagiarismdetector_source))
			error("Please check/change Plagiarism Detector source:<br>{$this->cfg->plagiarismdetector_source}",$this->cfg->wwwroot.'/admin/settings.php?section=modsettingplagiarismdetector');
		
		require_once($this->cfg->plagiarismdetector_source);
		$this->plagiarismDetector = new plagiarismDetector();
		if ($plagiarism) {
			$this->plagiarism = $plagiarism;
		}

	}
	function getPlagiarisms ($assignment) {
		return get_records('plagiarismdetector', 'assignment', $assignment);
	}
	function getPlugins () {
		$formattedPlugin = array();
		$plugins = explode(',',$this->cfg->plagiarismdetector_plugins);
		foreach ($plugins as $plugin) {
			$info = $this->plagiarismDetector->info($plugin);
			$formattedPlugin[$plugin] = $info["name"];
		}
		return $formattedPlugin;
	}
	function confirmedPlagiarismsSimilarities ($id) {
		$sql = "SELECT COUNT(*) FROM mdl_plagiarismdetector_similarities where (plagiarismid={$id}) AND (confirmed=1);";
		return count_records_sql($sql);
	}
	function totalPlagiarismsSimilarities ($id) {
		$sql = "SELECT COUNT(*) FROM mdl_plagiarismdetector_similarities where (plagiarismid={$id});";
		return count_records_sql($sql);
	}
	function getPlagiarismSimilarities ($id) {
		$sql = "SELECT * FROM mdl_plagiarismdetector_similarities where (plagiarismid={$id}) AND (confirmed=1) ORDER BY similarity DESC";
		return get_records_sql($sql);
	}
	function plagiarismSimilarities ($id) {
		$course     = $this->course;
		$assignment = $this->assignment;
		$cm         = $this->cm;

		$currentgroup = groups_get_activity_group($cm);

		if ($users = get_users_by_capability($this->context, 'mod/assignment:submit', 'u.id', '', '', '', $currentgroup, '', false)) {
			$users = array_keys($users);
		}

		// if groupmembersonly used, remove users who are not in any group
		if ($users and !empty($this->cfg->enablegroupings) and $cm->groupmembersonly) {
			if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
				$users = array_intersect($users, array_keys($groupingusers));
			}
		}
 		$similarities = get_records('plagiarismdetector_similarities','plagiarismid',$id);
 		$userssim = array();
 		$arrsim;
 		$arrusers = array();
 		foreach ($similarities as $sim) {
 			$userssim[] = $sim->user1;
 			$userssim[] = $sim->user2;
 			if (!isset($arrsim[$sim->user1])) $arrsim[$sim->user1] = array();
 			if (!isset($arrsim[$sim->user2])) $arrsim[$sim->user2] = array();
 			$arrsim[$sim->user1][$sim->user2] = new stdClass;
 			$arrsim[$sim->user1][$sim->user2]->similarity = $sim->similarity;
 			$arrsim[$sim->user1][$sim->user2]->confirmed = $sim->confirmed;
 			$arrsim[$sim->user1][$sim->user2]->id = $sim->id;
 			
 			$arrsim[$sim->user2][$sim->user1] = $arrsim[$sim->user1][$sim->user2];
 			
 			$id = $sim->user1;
 			$usa = new stdClass;
			$usa->firstname = $id;
			$usa->lastname = '(false)';
			$usa->id = $id;
			$arrusers[$id] = $usa;
 		}
 		
 		$userssim = array_unique($userssim);
 		$users = array_intersect($users,$userssim);
 		
		$select = 'SELECT u.id, u.firstname, u.lastname, u.picture, u.imagealt ';
		$sql = 'FROM '.$this->cfg->prefix.'user u '.
		       'WHERE u.id IN ('.implode(',',$users
		).') ';
		$infousers = get_records_sql( $select.$sql);
		
		foreach ($infousers as $us) {
			$id = $us->id;
			$arrusers[$id] = $us;
		}
		$ret = new stdClass;
		$ret->users = $arrusers;
		$ret->similarities = $arrsim;
		
		return $ret;
	}
	function updatePlagiarismSimilarities ($id, $sen) {
		$similarity = $this->sensSimilarity($sen);
		$sql = $sql = "UPDATE mdl_plagiarismdetector_similarities SET confirmed=IF(similarity>={$similarity},1,0) where (plagiarismid={$id})";
  		execute_sql($sql,false);
	}
	function setConfirmedPlagiarismSimilarities ($id,$confirmed) {
		$sql = "UPDATE mdl_plagiarismdetector_similarities SET confirmed=IF(id IN (".implode(',',$confirmed).") ,1,0)";
		execute_sql($sql,false);
	}
	function plagiarismId ($id) {
		return get_record("plagiarismdetector", "id", $id);
	}
	function deletePlagiarism ($id) {
		delete_records("plagiarismdetector", "id", $id);
		delete_records("plagiarismdetector_similarities", "plagiarismid", $id);
	}
	function sensSimilarity ($sim) {
		return 1-$sim/100;
	}
	function getScale ($similarity) {
		for ($i=0;$i<10;$i++) {
			$j = ($i)*10;
			$level = 10-$i-1;
			$sim = $this->sensSimilarity ($level*10);
			if ($sim >= $similarity) {
				return $i;
			}
		}
	}
    	function get_links () {
    		return array (
			"view" => "view.php?id=".$this->plagiarism->id,
			"detect" => "detect.php?id=".$this->plagiarism->id,
			"detect-confirmed" => "detect.php?id=".$this->plagiarism->id."&confirmed",
			"delete" => "delete.php?id=".$this->plagiarism->id,
			"delete-confirmed" => "delete.php?id=".$this->plagiarism->id."&confirmed",
			"edit" => "edit.php?id=".$this->plagiarism->id,
			"index" => "index.php?a=".$this->assignment->id,
			"create" => "create.php?a=".$this->assignment->id
		);
	}
	
	function view() {
		$this->print_view("view");
		
		$total = $this->totalPlagiarismsSimilarities($this->plagiarism->id);
		if (!$total) {
			notify(get_string('nodetectionsfound','plagiarismdetector'));
			$this->print_footer();
			return;
		}
		
		$confirmed = is_string(optional_param("confirmed"));
		if ($confirmed) {
			$confirmedarr = explode(',',optional_param("confirmed"));
			$this->setConfirmedPlagiarismSimilarities($this->plagiarism->id,$confirmedarr);
		}
		echo get_string('plagiarismsdetected','plagiarismdetector').': <b>'.$this->confirmedPlagiarismsSimilarities($this->plagiarism->id).'</b><br>';
		echo '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js" type="text/javascript"></script>';
		echo "<script type=\"text/javascript\" src=\"{$this->cfg->wwwroot}/mod/plagiarismdetector/table.js\" charset=\"utf-8\"></script>";
		echo '<link rel="stylesheet" type="text/css" href="'.$this->cfg->wwwroot.'/mod/plagiarismdetector/table.css" /> ';
		//echo ' <style type="text/css">@import "'.$this->cfg->wwwroot.'/mod/plagiarismdetector/table.css";</style> ';
		$simdata = ($this->plagiarismSimilarities($this->plagiarism->id));
		$strcols = array();
		$strrows = array();
		$data = array();
		foreach ($simdata->users as $simuser) {
			$name = $simuser->firstname.' '.$simuser->lastname;
			$id1 = $simuser->id;
			$strrows[] = "<tr>\n<td>".$name."</td>\n</tr>\n";
			$strcols[] = "<tr>\n<td>".$name."</td>\n</tr>\n";
			$data[] = "<tr>\n";
			foreach ($simdata->users as $simuser2) {
				if ($simuser2 != $simuser) {
					$id2 = $simuser2->id;
					$datss =  $simdata->similarities[$id1][$id2];
					$similarity = $datss->similarity;
					$confirmed = $datss->confirmed;
					$punct = $similarity;
					$scale = $this->getScale($similarity);
					$data[] = '<td><a href="" title="'.$datss->id.'" class="level'.($scale?$scale:'0').' '.($confirmed?"confirmed":"").'">Marcar</a></td>'."\n";
				}
				else {
					$punct = 'Own';$similarity=1;$confirmed=false;$data[] = '<td><a class="level">&nbsp;</a></td>';
				}
			}
			$data[] = "\n</tr>";
		}
		echo "\n".'<div class="prewrapper">
		</div>
		<div class="wrapper left">
		<table cellspacing="5">'.join($strrows,'').'</table>
		</div>
		<div class="wrapper content">
		<table cellspacing="5">'.join($data,'').'</table>
		</div>
		<div class="wrapper legend">
		<table cellspacing="5">
		<tr><td>
		<a href="#" class="level9">90-100%</a>90-100%
		</td><tr>
		<tr><td>
		<a href="#" class="level8">80-90%</a>80-90%
		</td><tr>
		<tr><td>
		<a href="#" class="level7">70-80%</a>70-80%
		</td><tr>
		<tr><td>
		<a href="#" class="level6">60-70%</a>60-70%
		</td><tr>
		<tr><td>
		<a href="#" class="level5">50-60%</a>50-60%
		</td><tr>
		<tr><td>
		<a href="#" class="level4">40-50%</a>40-50%
		</td><tr>
		<tr><td>
		<a href="#" class="level3">30-40%</a>30-40%
		</td><tr>
		<tr><td>
		<a href="#" class="level2">20-30%</a>20-30%
		</td><tr>
		<tr><td>
		<a href="#" class="level1">10-20%</a>10-20%
		</td><tr>
		<tr><td>
		<a href="#" class="level0">0-10%</a>0-10%
		</td><tr>
		</table>
		<h3>'.get_string('legend', 'plagiarismdetector').'</h3>
		</div>
		<div class="wrapper bottom">
		<div class="format">
		<table cellspacing="5">'.join($strcols,'').'</table>
		</div>
		<div class="clear"></div></div>';	
		/* Top Plagiarism section */
		echo "<h4>".get_string('topplagiarismusers','plagiarismdetector')."</h4>";
		
		$table->head  = array (get_string('user'), get_string('plagiarismsdetected', 'plagiarismdetector'));
		$table->align = array ("left", "center");
		
		$sql = 'SELECT firstname,lastname,u,sum(c) as num from ((SELECT user1 as u,count(*) as c FROM `mdl_plagiarismdetector_similarities` where confirmed=1 Group by user1 order by c desc) UNION 
		(SELECT user2 as u,count(*) as c FROM `mdl_plagiarismdetector_similarities` where confirmed=1 Group by user2 order by c desc)) as s join mdl_user as us on u=us.id GROUP BY u ORDER BY c desc';
		$allPlagiarisms = get_records_sql($sql);
		

		foreach ($allPlagiarisms as $plag) {
			$table->data[] = array(
				ucfirst($plag->firstname)." ".ucfirst($plag->lasttname),
				"{$plag->num}"
			);
		}
		print_table($table);

		$this->print_footer();
	}

	function edit () {
		$this->print_view('edit');
		$this->print_edit();
		$this->print_footer();
	}
	function detect () {
		$this->print_view('detect');
		$confirmed = is_string(optional_param("confirmed"));
		$total = (int) $this->totalPlagiarismsSimilarities($this->plagiarism->id);
		$links = $this->get_links();
		if ($total!=0  && !$confirmed) {
			notice_yesno(get_string('confirmdetectagain', 'plagiarismdetector'),
			$links["detect-confirmed"],
			$links["view"]);
			$this->print_footer();
			return;
		}
		print_simple_box_start('center', '', '', 0, 'generalbox', 'intro');
		echo get_string('initialization','plagiarismdetector');
		$judger = $this->plagiarismDetector->plugin($this->plagiarism->plugin);
		delete_records('plagiarismdetector_similarities', 'plagiarismid',$this->plagiarism->id);
		$assignmentdir = $this->cfg->dataroot.'/'. $this->course->id.'/moddata/assignment/'.$this->assignment->id;
		
		if ($this->cfg->plagiarismdetector_enableunpack) {
			$plagiarismdetectordir = $this->cfg->dataroot.'/'. $this->course->id.'/moddata/plagiarismdetector/'.$this->assignment->id;
			echo "<br>[PlagiarismDetector directory: {$plagiarismdetectordir}]<br>";
			if ($dh = opendir($assignmentdir)) {
				while (($file = readdir($dh)) !== false) {
					$adir = $assignmentdir."/".$file;
					$pdir = $plagiarismdetectordir."/".$file;
					if ($file != "." && $file != ".." && is_dir($adir)) {
						echo "Creating directory for user {$file}:";
						mkdir ($pdir,NULL,true);
						//echo 'cp '.$adir.'/* '.$pdir.'/';
						//$msg = shell_exec('cp '.$adir.'/* '.$pdir.'/;');
						//echo $msg;
						//echo "filename: $file : filetype: " . filetype($dir . $file) . "\n";
						//for i in *.zip; do unzip $i; done
						echo "&nbsp;&nbsp;Copying & Unpacking files...";
						flush();
						ob_flush();
						/*$msg2 = */shell_exec('cp '.$adir.'/* '.$pdir.'/; cd '.$pdir.'; for i in *.zip; do unzip $i; rm $i; done; for i in *.tar.gz; do tar -xzvf $i; rm $i; done;');
						echo "FINISHED<br>";
						flush();
						ob_flush();
						//echo $msg.$msg2;
						//for i in *.tar.gz; do tar -xzvf $i; done
					}
				}
				closedir($dh);
			}
			$finaldir = $plagiarismdetectordir;
		}
		else {
			echo "<br>[Assignment directory: {$assignmentdir}]<br>";
			$finaldir = $assignmentdir;
		}
		$results = $judger->compareDir($finaldir)->getResults();
		foreach ($results as $result) {
			$data_similarity = new stdClass();
			$data_similarity->user1 = (string)$result['users'][0];
			$data_similarity->user2 = (string)$result['users'][1];
			$data_similarity->similarity = (float)@$result['similarity'];
			$data_similarity->plagiarismid = $this->plagiarism->id;
			$data_similarity->date = time();
			insert_record('plagiarismdetector_similarities', $data_similarity);
		}
		
		if ($this->plagiarism->autonotify) {
			$this->updatePlagiarismSimilarities($this->plagiarism->id, $this->plagiarism->sensitivity);
		}
		echo get_string('finalization','plagiarismdetector');
		print_simple_box_end();
		print_continue($links["view"]);
		$this->print_footer();
		
   	}
	function delete () {
		$this->print_view('delete');
		$confirmed = is_string(optional_param("confirmed"));
		$links = $this->get_links();
		if (!$confirmed) {
    			notice_yesno(get_string('confirmdelete', 'plagiarismdetector'),
           		$links["delete-confirmed"],
           		$links["view"]);
    		}
		else {
			$this->deletePlagiarism($this->plagiarism->id);
			notice(get_string('deleteok', 'plagiarismdetector'),
   			$links["index"]);
		}
		$this->print_footer();
	}
	function create () {
		$this->print_index('create');
		$this->print_edit();
		$this->print_footer();
	}
	function index() {
		$this->print_index('index');
		$links = $this->get_links();
		if (!$plagiarisms = $this->getPlagiarisms($this->assignment->id)) {
			notice(get_string('noplagiarisms', 'plagiarismdetector'), $links["create"]);
			$this->print_footer();
			return;
		}
	
		$table->head  = array (
			get_string('judger', 'plagiarismdetector'),
			get_string('plagiarismsdetected', 'plagiarismdetector'),  
			get_string('plugin', 'plagiarismdetector'),
			get_string('language', 'plagiarismdetector'),
			get_string('sensitivity', 'plagiarismdetector'),
			get_string('actions')
		);
		$table->align = array ("center", "left","left","left","left", "right");
		
		$allPlagiarisms = $this->getPlagiarisms($this->assignment->id);
		foreach ($allPlagiarisms as $plag) {
			$actions = array();
			$actions[] = "<a title=\"".get_string('detect','plagiarismdetector')."\" href=\"{$this->cfg->wwwroot}/mod/plagiarismdetector/detect.php?id={$plag->id}\"><img src=\"{$this->cfg->wwwroot}/mod/plagiarismdetector/t/detect.gif\" class=\"iconsmall\" alt=\"".get_string('detect','plagiarismdetector')."\" /></a>\n";
			$actions[] = "<a title=\"".get_string('edit')."\" href=\"{$this->cfg->wwwroot}/mod/plagiarismdetector/edit.php?id={$plag->id}\"><img src=\"{$this->cfg->pixpath}/t/edit.gif\" class=\"iconsmall\" alt=\"".get_string('edit')."\" /></a>\n";
			$actions[] = "<a title=\"".get_string('delete')."\" href=\"{$this->cfg->wwwroot}/mod/plagiarismdetector/delete.php?id={$plag->id}\"><img src=\"{$this->cfg->pixpath}/t/delete.gif\" class=\"iconsmall\" alt=\"".get_string('delete')."\" /></a>\n";
			$tot = $this->totalPlagiarismsSimilarities($plag->id);
			
			$table->data[] = array(
				"<a title=\"".get_string('go')."\" href=\"{$this->cfg->wwwroot}/mod/plagiarismdetector/view.php?id={$plag->id}\">{$plag->name}</a>",
				($tot==0)?'-':$this->confirmedPlagiarismsSimilarities($plag->id),
				$plag->plugin,
				$plag->language,
				$plag->sensitivity.'%',
				implode('',$actions)
			);
		}
		print_table($table);
		
		$this->print_footer();
	}
	function print_edit () {
		global $CFG,$aid;
		$aid = $this->assignment->id;
		require_once('edit_form.php');
		$pid = $plagiarism->id;
		$id = $this->assignment->id;

		$mform = new plagiarismdetectorform($this->getPlugins());
		
		if ($data=$mform->get_data()) {
			$data->assignment = $this->assignment->id;
			if (empty($this->plagiarism)) {
				$id = insert_record('plagiarismdetector', $data);
				$data->id = $id;
			}
			else {
				if (@$this->plagiarism->p)
					$data->id = @$this->plagiarism->p;
				update_record('plagiarismdetector', $data);
				$this->updatePlagiarismSimilarities($data->id,$data->sensitivity);
			}
			if ($id) 
				notify (get_string('editsave', 'plagiarismdetector'),'notifysuccess');
			$this->plagiarism->id = $data->id;
			$links = $this->get_links();
			notice_yesno(get_string('confirmdetect', 'plagiarismdetector'),
				$links["detect"],
				$links["view"]);
		}
		else {
			if (isset($this->plagiarism->id))  $this->plagiarism->p = $this->plagiarism->id;
			$mform->set_data($this->plagiarism);
			$mform->display();
		}
	}

	function print_index ($action) {
		/* First verify if user have the permission for view this content */
		require_capability('mod/plagiarismdetector:'.$action, $this->context);
		$links = $this->get_links();
		add_to_log($this->course->id, "plagiarismdetector:".$action, $action, $links[$action] ,$this->assignment->id, $this->cm->id);
		$this->print_header();
		print_heading($this->plagiarism->name);
		$tabs[] = array(
			new tabobject('index', $links["index"], get_string('list')),
			new tabobject('create', $links["create"], get_string('createnew', 'plagiarismdetector')),
		);

		/* Print out the tabs */
		print_tabs($tabs, $action);
		
	}
	
	function print_view ($action) {
		/* First verify if user have the permission for view this content */
		require_capability('mod/plagiarismdetector:'.$action, $this->context);
		$links = $this->get_links();
		add_to_log($this->course->id, "plagiarismdetector:".$action, $action, $links[$action] ,$this->assignment->id, $this->cm->id);
		$headerlinks = array(
			array('name' => $this->plagiarism->name, 'link' => $links["view"], 'type' => 'title'),
			array('name' => get_string($action, 'plagiarismdetector'), 'link' => $links[$action], 'type' => 'title')
		);
		$this->print_header($headerlinks);
		print_heading($this->plagiarism->name);
		$tabs[] = array(
			new tabobject('view', $links["view"], get_string('view', 'plagiarismdetector')),
			new tabobject('detect', $links["detect"], get_string('detect', 'plagiarismdetector')),
			new tabobject('edit', $links["edit"], get_string('edit')),
			new tabobject('delete', $links["delete"], get_string('delete')),
		);

		/* Print out the tabs */
		print_tabs($tabs, $action);
		
	}
	
	function print_header($sub=array()) {
		global $CFG;
		$strassignments = get_string('modulenameplural', 'assignments');
		if ($sub) $navlinks = $sub;
		else $navlinks = array();
		array_unshift($navlinks, 
		array('name' => get_string('modulename', 'plagiarismdetector'), 'link' => "index.php?a={$this->assignment->id}", 'type' => 'title')
		);
		$navigation = build_navigation($navlinks, $this->cm);
		$pagetitle = strip_tags($this->course->shortname.': '.$strassignments.': '.format_string($this->assignment->name,true).': '.get_string('modulename', 'plagiarismdetector'));
		print_header($pagetitle, $this->course->fullname, $navigation, "", "", true, '', navmenu($this->course));
	}
	
	function print_footer() {
		print_footer($this->course);
	}
}
