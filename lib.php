<?php

function get_course_next_weight($courseid)
{
	global $DB;

	$weight = $DB->get_record_sql('
SELECT MAX(cs.section) as maxsec
FROM {course_sections} cs
LEFT JOIN {block_summary} bs ON (cs.id = bs.sectionid)
WHERE cs.course = '.$courseid.' AND cs.section > 0  ORDER BY cs.section ASC');

	return $weight->maxsec;
}


function get_course_tree_html($courseid)
{
	$tree = get_course_tree($courseid);



	$delbutton = '<button class="del fa fa-times"></button>';
	//$hidebutton = '<button class="hide"></button>';
	//$buttons = $delbutton.$hidebutton;

	$html = '<div id="dd" class="dd">';
	$html .= '<ol class="dd-list">';

	$dragbutton = '<button class="move fa fa-arrows"></button>';
	$editbutton = '<button class="edit fa fa-pencil"></button>';
	$buttonspacer = '<span class="dd-buttonsblockspacer"></span>';

	foreach($tree as $section)
	{
		$hidebutton = '<button class="hide fa'.($section->visible?' fa-eye':' fa-eye-slash ishidden').'"></button>';
		$buttons = $buttonspacer.$editbutton.$buttonspacer.$hidebutton.$buttonspacer.$delbutton;

		$html .= '<li class="dd-item" data-id="'.$section->sectionid.'">';
		if (strlen($section->name) < 1){
			$section->name = '(Section '.$section->section.')';
		}
		else {
			$section->name = $section->name;
		}
		$html .= '<div class="dd-itemdiv">';
		$html .= '<div class="dd-div">'.$dragbutton.'</div>';
		$html .= $buttonspacer;
		$html .= '<div class="dd-handle">'.$section->name.'</div><div class="dd-buttonsblock">'.$buttons.'</div>';
		$html .= '</div>';

		if (isset($section->children) && count($section->children) > 0)
		{
			$html .= '<ol class="dd-list">';

			foreach($section->children as $child)
			{
				$hidebutton = '<button class="hide fa'.($child->visible?' fa-eye':' fa-eye-slash ishidden').'"></button>';
				$buttons = $buttonspacer.$editbutton.$buttonspacer.$hidebutton.$buttonspacer.$delbutton;

				$html .= '<li class="dd-item" data-id="'.$child->sectionid.'">';
				if (strlen($child->name) < 1){
					$child->name = '(Section '.$child->section.')';
				}
				else {
					$child->name= $child->name;
				}
				$html .= '<div class="dd-itemdiv">';
				$html .= '<div class="dd-div">'.$dragbutton.'</div>';
				$html .= $buttonspacer;
				$html .= '<div class="dd-handle">'.$child->name.'</div><div class="dd-buttonsblock">'.$buttons.'</div>';
				$html .= '</div>';

				$html .= '</li>';
			}

			$html .= '</ol>';
		}


		$html .= '</li>';

	}

	$html .= '</ol>';
	$html .= '</div>';

	return $html;

}


function get_course_tree($courseid)
{
	global $DB;
	$sections = $DB->get_records_sql('
SELECT cs.id, cs.id AS sectionid, cs.course AS courseid, bs.parentid, bs.weight, cs.name, cs.visible, cs.section
FROM {course_sections} cs
LEFT JOIN {block_summary} bs ON (cs.id = bs.sectionid)
WHERE cs.course = '.$courseid.' AND cs.section > 0  ORDER BY bs.parentid,cs.section ASC');

	/*
	 if ($sections === false || count($sections) < 1)
	 {
	 return get_course_tree_from_section($courseid);
	 }*/

    // Get section details
    $modinfo = get_fast_modinfo($courseid);
    $coursesections = $modinfo->get_section_info_all();

	$sec = array();
	foreach($sections AS $key=>$section)
	{
		$sec[$key] = new stdClass();
		$sec[$key]->parentid = $section->parentid;
		$sec[$key]->sectionid = $section->sectionid;
		$sec[$key]->section = $section->section;
		$sec[$key]->courseid = $section->courseid;
		$sec[$key]->name = $section->name;
		$sec[$key]->visible = $section->visible;
        $sec[$key]->uservisible = $coursesections[$section->section]->uservisible;
        $sec[$key]->availableinfo = $coursesections[$section->section]->availableinfo;
		$sec[$key]->weight = $section->section;
	}



	foreach($sec AS $key=>$se)
	{
		if ($sec[$key]->parentid != null && $sec[$key]->parentid > 0)
		{
			if (isset($sec[$sec[$key]->parentid]->children) && !is_array($sec[$sec[$key]->parentid]->children))
			{
				$sec[$sec[$key]->parentid]->children = array();
			}

			if(!$sec[$sec[$key]->parentid]->uservisible && $sec[$key]->uservisible){
                $sec[$key]->uservisible = false;
            }


			$sec[$sec[$key]->parentid]->children[$key] = $sec[$key];
			unset($sec[$key]);
		}

	}

	return $sec;
}



function get_course_tree_from_section($courseid)
{
	global $DB;
	$sections = $DB->get_records('course_sections', array('course'=>$courseid));

	$sec = array();

	foreach($sections AS $key=>$section)
	{
		if ($section->section == 0)
		{
			continue;
		}
		$sec[$key] = new stdClass();
		$sec[$key]->parentid = null;
		$sec[$key]->sectionid = $section->id;
		$sec[$key]->section = $section->section;
		$sec[$key]->courseid = $section->course;
		$sec[$key]->name = $section->name;
		$sec[$key]->visible = $section->visible;
		$sec[$key]->weight = $section->section;
	}


	return $sec;
}

function build_html_tree_modular($tree)
{
    $html = '';

    $html .= html_writer::start_div('dd section-part', array('data-type' => 'intro'));

    if(count($tree['intro']) > 0)
    {
        $html .= '<ol class="dd-list">';
        foreach($tree['intro'] as $section){
            $html .= build_html_tree_node($section);
        }
        $html .= '</ol>';
    }

    $html .= html_writer::end_div();


    $html .= html_writer::start_div('dd section-part', array('data-type' => 'module'));

    if(count($tree['module']) > 0)
    {
        $html .= '<ol class="dd-list">';
        foreach($tree['module'] as $section)
        {
            $html .= build_html_tree_node($section);
        }
        $html .= '</ol>';
    }

    $html .= html_writer::end_div();


    $html .= html_writer::start_div('dd section-part', array('data-type' => 'end'));

    if(count($tree['end']) > 0)
    {
        $html .= '<ol class="dd-list">';
        foreach($tree['end'] as $section)
        {
            $html .= build_html_tree_node($section);
        }
        $html .= '</ol>';
    }



    $html .= html_writer::end_div();

    return $html;
}

function build_html_tree_node($node)
{
    $html = '';

    $dragbutton = '<button class="move fa fa-arrows"></button>';
    $editbutton = '<button class="edit fa fa-pencil"></button>';
    $buttonspacer = '<span class="dd-buttonsblockspacer"></span>';
    $delbutton = '<button class="del fa fa-times"></button>';
    $hasnavbutton = '<button class="hasnav fa-stack">
<span class="fa fa-sort fa-rotate-90 fa-stack-1x"></span>
</button>';
    $hasnonavbutton = '<button class="hasnav fa-stack nonav fa fa-slash fa-stack-1x">
<span class="fa fa-sort fa-rotate-90 fa-stack-1x"></span>
</button>';

    $classhidebutton = ($node->visible?' fa-eye':' fa-eye-slash ishidden');
    $hidebutton = '<button class="hide fa'.$classhidebutton.'"></button>';

    $hascontentbutton = '';
    $parentclass = '';
    $buttons = '';
    if($node->type == 'module'){
        $haschildren = (count($node->children) > 0);
        if($haschildren){
            $parentclass = ' dd-handle-parent';
            if($node->hasContent){
                $hascontentbutton = $buttonspacer.'<button class="hascontent"></button>';
            }else{
                $hascontentbutton = $buttonspacer.'<button class="hascontent nocontent"></button>';
            }
        }

        if(!$node->parentid && $haschildren){
            if($node->hasNavigation){
                $buttons .= $hasnavbutton;
            }else{
                $buttons .= $hasnonavbutton;
            }
        }
    }

    $buttons .= $hascontentbutton.$buttonspacer.$editbutton.$buttonspacer.$hidebutton.$buttonspacer.$delbutton;

    $html .= '<li class="dd-item" data-id="'.$node->id.'" data-numsection="'.$node->numsection.'">';
    $name = $node->name;



    $html .= '<div class="dd-itemdiv">';
    $html .= '<div class="dd-div">'.$dragbutton.'</div>';
    $html .= $buttonspacer;
    $html .= '<div class="dd-handle'.$parentclass.'">'.$name.'</div><div class="dd-buttonsblock">'.$buttons.'</div>';
    $html .= '</div>';


    if(!empty($node->children)){
        $html .= '<ol class="dd-list">';
        foreach($node->children as $c){
            $html .= build_html_tree_node($c);
        }
        $html .= '</ol>';
    }

    $html .= '</li>';

    return $html;
}


function section_is_completed($sectionid)
{
    $section_completion = section_is_completed_rec($sectionid);

    if ($section_completion->count > 0){
        //- si tous cochés => section cochée, else section non cochée
        if ($section_completion->countUncompleted == 0){
            return true;
        }
        else {
            return false;
        }
    }
    elseif (
        $section_completion->countmodules_achievementrequired > 0 && $section_completion->countmodulesnotcompleted == 0
        && $section_completion->completed
        ) {
        return true;
    }
    return false;
}

function section_is_completed_rec($sectionid)
{
    global $DB, $USER;

    $section = $DB->get_record('course_sections', array('id'=>$sectionid));

    if ($section === false)
    {

        return false;
    }

    $course = $DB->get_record('course', array('id'=>$section->course));

    if ($course->format != 'topics' && $course->format != 'magistere_topics' && $course->format != 'modular')
    {
        return false;
    }

    // prendre que les sections visibles ?
    if ($course->format == 'topics' or $course->format == 'magistere_topics')
    {
        $childs = $DB->get_records('block_summary', array('parentid'=>$sectionid));
    }else{
        $childs = $DB->get_records_sql("SELECT * FROM {course_format_options} WHERE courseid=:courseid AND format=:format AND name=:name AND value=:value", array('courseid'=>$course->id,'format'=>'modular','value'=>$section->section,'name'=>'parentid'));
    }

    $childcount = 0;
    $childcountUncompleted = 0;
    $childscompleted = true;
    $childcountmodulesnotcompleted = 0;
    $childcountmodules_achievementrequired = 0;
    if (count($childs) > 0)
    {
        $childscompleted = true;
        foreach($childs AS $child)
        {
            $childcmp = section_is_completed_rec($child->sectionid);
            if ($childcmp->count > 0 && ($childcmp->completed == false && $childcmp->countUncompleted > 0))
            {
                $childscompleted = false;
            }
            if ($childcmp->count == 0 && ($childcmp->countmodules_achievementrequired > 0 && $childcmp->countmodulesnotcompleted > 0)){
                $childscompleted = false;
            }
            if ($childcmp->count == 0 && $childcmp->countmodules_achievementrequired == 0){
                $childscompleted = false;
            }

            $childcountUncompleted += $childcmp->countUncompleted + $childcmp->childcountUncompleted;
            $childcountUncompleted += $childcmp->countUncompleted;
            $childcount += $childcmp->count + $childcmp->childcount;

            $childcountmodules_achievementrequired += $childcmp->countmodules_achievementrequired + $childcmp->childcountmodules_achievementrequired;
            $childcountmodulesnotcompleted += $childcmp->countmodulesnotcompleted + $childcmp->childcountmodulesnotcompleted;
        }
    }
    
    // Recherche des modules de type marqueur d'achevement
    $module = $DB->get_record('modules',array('name'=>'completionmarker'));
    if($module){
        $course_modules = $DB->get_records_sql("SELECT cm.*, cmc.completionstate FROM {course_modules} cm 
LEFT JOIN {course_modules_completion} cmc ON (cmc.coursemoduleid = cm.id AND (cmc.userid = '".$USER->id."' OR cmc.userid IS NULL))
WHERE 
cm.course = '".$section->course."'
AND cm.module = '".$module->id."'
AND cm.section = '".$section->id."'
");

        $completed = true;
        $uncompleted = 0;
        foreach($course_modules AS $course_module)
        {
            if ($course_module->completionstate != 1)
            {
                $completed = false;
                $uncompleted++;
            }
        }
    }else{
        $course_modules = [];
        $uncompleted = 0;
        $completed = false;
    }
 
    // Recherche des modules de la section differents de marqueur d'achevement où la completion est requise
    $modulesnotcompleted = [];
    if ($sectionmodules = $DB->get_records_sql("
        SELECT cm.*, cmc.completionstate 
        FROM {course_modules} cm 
        LEFT JOIN {course_modules_completion} cmc ON (cmc.coursemoduleid = cm.id AND (cmc.userid = :user OR cmc.userid IS NULL))
        WHERE 
        cm.visible=1 AND cm.course = :course AND cm.section = :section AND cm.module != :achevementmodule AND cm.completion IN (1,2)", 
        ['user' => $USER->id, 'course' => $section->course, 'section' => $section->id, 'achevementmodule'=> $module->id]) ) {
        
        $modulesnotcompleted = array_filter($sectionmodules, function($mod){
            return $mod->completionstate != 1; 
        });
    }

    $res = new stdClass();
    $res->sectionid = $sectionid;
    $res->count = count($course_modules);
    $res->countUncompleted = $uncompleted;

    $res->childcount = $childcount;
    $res->childcountUncompleted = $childcountUncompleted;
    $res->completed = $completed && $childscompleted && $res->countUncompleted == 0; 
    
    $res->countmodules_achievementrequired = count($sectionmodules);
    $res->childcountmodules_achievementrequired = $childcountmodules_achievementrequired;
    $res->countmodulesnotcompleted = count($modulesnotcompleted);
    $res->childcountmodulesnotcompleted = $childcountmodulesnotcompleted;

    return $res;
} 

function process_section_modular($courseid, $data)
{
    global $DB;

    $formatmodular = course_get_format($courseid);
    $mapids = array();
    $numsec = 1;
    $sid = -1;

    // first step : delete all old sections
    $sectionstodelete = $DB->get_records_sql('SELECT cs.id, cs.section
FROM {course_sections} cs
WHERE cs.course=? AND cs.section > 0 
AND cs.id <> '.implode(' AND cs.id <> ', $data->ids).'
ORDER BY cs.section DESC', array($courseid));

    foreach($sectionstodelete as $s){
        course_delete_section($courseid, $s->section);
    }

    // second step : update sections or create new sections
    //print_r($data->nodes);
    foreach($data->nodes as $section){
        $section->section = $numsec++;
        $section->course = $courseid;

        if($section->parentid != null){
            $section->parentid = $mapids[$section->parentid];
        }

        $mapids[$section->id] = $section->section;

        if($section->id > 0){
            $formatmodular->update_section($section);
            continue;
        }

        $sid = $formatmodular->create_section($section);
    }
    return $sid; // besoin du dernier id inséré seulement pour la duplication de section (1 seul insert sera réalisé)
}

function process_section_thematique($courseid, $data, $newsectionstartid = 1000000000)
{
    global $DB;

    $sid = -1;
    $sectionids = array();

    foreach($data as $section)
    {
        $sectionids[] = $section->id;
        if (isset($section->children) && count($section->children) > 0)
        {
            foreach($section->children AS $child)
            {
                $sectionids[] = $child->id;
            }
        }
    }

    // Delete all removed section
    $sections_delete = $DB->get_records('course_sections', array('course'=>$courseid), 'section DESC');

    foreach($sections_delete AS $section)
    {
        if (!in_array($section->id,$sectionids) && $section->section> 0)
        {
            course_delete_section($courseid,$section->section);
        }
    }

    // Purge old block_summary records
    $block_summary_delete = $DB->get_records('block_summary', array('courseid'=>$courseid));

    foreach($block_summary_delete AS $summary)
    {
        if (!in_array($summary->sectionid,$sectionids))
        {
            $DB->delete_records('block_summary',array('id'=>$summary->id));
        }
    }


    $weight = 1;
    $parent = null;
    foreach($data as $section)
    {
        $sec = $DB->get_record('block_summary', array('sectionid'=>$section->id));

        // N'existe pas dans summary
        if ($sec === false)
        {

            // Nouvelle section?
            if ($section->id >= $newsectionstartid)
            {
                // Avoid duplicate key conflict
                $oldsec = $DB->get_record('course_sections', array('course'=>$courseid,'section'=>$weight));
                if ($oldsec !== false)
                {
                    $oldsec->section = $oldsec->section+10000;
                    $DB->update_record('course_sections', $oldsec);
                }

                $course_section = new stdClass();
                $course_section->course = $courseid;
                if (isset($section->name) && strlen(trim($section->name)) > 1)
                {
                    $course_section->name = trim($section->name);
                }
                else
                {
                    $course_section->name = 'Nouvelle page '.$weight;
                }

                $course_section->summaryformat = 1;
                $course_section->section = $weight;
                $course_section->visible = ($section->hidden?0:1);

                $section->id = $DB->insert_record('course_sections', $course_section, true);
                $sid = $section->id;

            }
            else{
                $course_section = $DB->get_record('course_sections', array('id'=>$section->id));

                // La section n'existe pas?
                if ($course_section === false)
                {
                    continue;
                }

                $course_section_updated = false;

                if ($course_section->visible == $section->hidden)
                {
                    $course_section->visible = ($section->hidden?0:1);
                    $course_section_updated= true;
                }

                if ($course_section->section != $weight)
                {
                    // Avoid duplicate key conflict
                    $oldsec = $DB->get_record('course_sections', array('course'=>$courseid,'section'=>$weight));
                    if ($oldsec !== false)
                    {
                        $oldsec->section = $oldsec->section+10000;
                        $DB->update_record('course_sections', $oldsec);
                    }
                    $course_section->section = $weight;

                    $course_section_updated= true;
                }

                if (isset($section->name) && strlen(trim($section->name)) > 1)
                {
                    $course_section->name = trim($section->name);
                    $course_section_updated = true;
                }
                else if (strlen($course_section->name) < 1)
                {
                    $course_section->name = 'Nouvelle page '.$weight;
                    $course_section_updated= true;
                }

                if ($course_section_updated)
                {
                    $DB->update_record('course_sections', $course_section);
                }

            }


            $blocksummary_colision = $DB->get_record('block_summary', array('courseid'=>$courseid,'weight'=>$weight));
            if ($blocksummary_colision!== false)
            {
                $blocksummary_colision->weight= $blocksummary_colision->weight+10000;
                $DB->update_record('block_summary', $blocksummary_colision);
            }

            $blocksummary = new stdClass();
            $blocksummary->courseid = $courseid;
            $blocksummary->sectionid = $section->id;
            $blocksummary->parentid = null;
            $blocksummary->weight = $weight;

            $DB->insert_record('block_summary', $blocksummary);

        }else{

            $course_section = $DB->get_record('course_sections', array('id'=>$section->id));

            if ($course_section === false)
            {
                continue;
            }

            $course_section_updated = false;

            if ($course_section->visible == $section->hidden)
            {
                $course_section->visible = ($section->hidden?0:1);
                $course_section_updated= true;
            }

            if ($course_section->section != $weight)
            {
                // Avoid duplicate key conflict
                $oldsec = $DB->get_record('course_sections', array('course'=>$courseid,'section'=>$weight));
                if ($oldsec !== false)
                {
                    $oldsec->section = $oldsec->section+10000;
                    $DB->update_record('course_sections', $oldsec);
                }
                $course_section->section = $weight;
                $course_section_updated= true;
            }

            if (isset($section->name) && strlen(trim($section->name)) > 1)
            {
                $course_section->name = trim($section->name);
                $course_section_updated = true;
            }
            else if (strlen($course_section->name) < 1)
            {
                $course_section->name = 'Nouvelle page '.$weight;
                $course_section_updated = true;
            }

            if ($course_section_updated)
            {
                $DB->update_record('course_sections', $course_section);
            }

            $blocksummary_colision = $DB->get_record('block_summary', array('courseid'=>$courseid,'weight'=>$weight));
            if ($blocksummary_colision !== false)
            {
                $blocksummary_colision->weight= $blocksummary_colision->weight+10000;
                $DB->update_record('block_summary', $blocksummary_colision);
            }

            $sec->parentid = null;
            $sec->weight = $weight;

            $DB->update_record('block_summary', $sec);

        }




        // children
        if (isset($section->children) && count($section->children) > 0)
        {

            foreach($section->children AS $child)
            {

                $weight = $weight + 1;


                $sec2 = $DB->get_record('block_summary', array('sectionid'=>$child->id));

                // N'existe pas dans summary
                if ($sec2 === false)
                {

                    // Nouvelle section?
                    if ($child->id >= $newsectionstartid)
                    {
                        // Avoid duplicate key conflict
                        $oldsec = $DB->get_record('course_sections', array('course'=>$courseid,'section'=>$weight));
                        if ($oldsec !== false)
                        {
                            $oldsec->section = $oldsec->section+10000;
                            $DB->update_record('course_sections', $oldsec);
                        }

                        $course_section = new stdClass();
                        $course_section->course = $courseid;
                        if (isset($child->name) && strlen(trim($child->name)) > 1)
                        {
                            $course_section->name = trim($child->name);
                        }
                        else
                        {
                            $course_section->name = 'Nouvelle page '.$weight;
                        }
                        $course_section->section = $weight;
                        $course_section->visible = ($child->hidden?0:1);

                        $child->id = $DB->insert_record('course_sections', $course_section, true);
                        $sid = $child->id;
                    }
                    else{
                        $course_section = $DB->get_record('course_sections', array('id'=>$child->id));

                        // La section n'existe pas?
                        if ($course_section === false)
                        {
                            continue;
                        }

                        $course_section_updated = false;

                        if ($course_section->visible == $child->hidden)
                        {
                            $course_section->visible = ($child->hidden?0:1);
                            $course_section_updated= true;
                        }

                        if ($course_section->section != $weight)
                        {
                            // Avoid duplicate key conflict
                            $oldsec = $DB->get_record('course_sections', array('course'=>$courseid,'section'=>$weight));
                            if ($oldsec !== false)
                            {
                                $oldsec->section = $oldsec->section+10000;
                                $DB->update_record('course_sections', $oldsec);
                            }
                            $course_section->section = $weight;
                            $course_section_updated = true;
                        }

                        if (isset($child->name) && strlen(trim($child->name)) > 1)
                        {
                            $course_section->name = trim($child->name);
                            $course_section_updated = true;
                        }
                        else if (strlen($course_section->name) < 1)
                        {
                            $course_section->name = 'Nouvelle page '.$weight;
                            $course_section_updated = true;
                        }

                        if ($course_section_updated)
                        {
                            $DB->update_record('course_sections', $course_section);
                        }

                    }

                    if ($course_section->visible == $child->hidden)
                    {
                        $course_section->visible = ($child->hidden?0:1);
                        $DB->update_record('course_sections', $course_section);
                    }

                    $blocksummary_colision = $DB->get_record('block_summary', array('courseid'=>$courseid,'weight'=>$weight));
                    if ($blocksummary_colision !== false)
                    {
                        $blocksummary_colision->weight= $blocksummary_colision->weight+10000;
                        $DB->update_record('block_summary', $blocksummary_colision);
                    }

                    $blocksummary = new stdClass();
                    $blocksummary->courseid = $courseid;
                    $blocksummary->sectionid = $child->id;
                    $blocksummary->parentid = $section->id;
                    $blocksummary->weight = $weight;

                    $DB->insert_record('block_summary', $blocksummary);

                }else{

                    $course_section = $DB->get_record('course_sections', array('id'=>$child->id));

                    if ($course_section === false)
                    {
                        continue;
                    }

                    $course_section_updated = false;

                    if ($course_section->visible == $child->hidden)
                    {
                        $course_section->visible = ($child->hidden?0:1);
                        $course_section_updated= true;
                    }

                    if ($course_section->section != $weight)
                    {
                        // Avoid duplicate key conflict
                        $oldsec = $DB->get_record('course_sections', array('course'=>$courseid,'section'=>$weight));
                        if ($oldsec !== false)
                        {
                            $oldsec->section = $oldsec->section+10000;
                            $DB->update_record('course_sections', $oldsec);
                        }
                        $course_section->section = $weight;
                        $course_section_updated= true;
                    }

                    if (isset($child->name) && strlen(trim($child->name)) > 1)
                    {
                        $course_section->name = trim($child->name);
                        $course_section_updated = true;
                    }
                    else if (strlen($course_section->name) < 1)
                    {
                        $course_section->name = 'Nouvelle page '.$weight;
                        $course_section_updated= true;
                    }

                    if ($course_section_updated)
                    {
                        $DB->update_record('course_sections', $course_section);
                    }

                    $blocksummary_colision = $DB->get_record('block_summary', array('courseid'=>$courseid,'weight'=>$weight));
                    if ($blocksummary_colision !== false)
                    {
                        $blocksummary_colision->weight = -$blocksummary_colision->weight;
                        $DB->update_record('block_summary', $blocksummary_colision);
                    }

                    $sec2->parentid = $section->id;
                    $sec2->weight = $weight;

                    $DB->update_record('block_summary', $sec2);

                }

            }

        }

        $weight = $weight + 1;
    }

    // Delete all remaining sections to avoid any 10.000 section course
    $sections_delete = $DB->get_records_sql('SELECT * FROM {course_sections} WHERE course='.$courseid.' AND section > 10000 ORDER BY section DESC');
    foreach($sections_delete AS $section)
    {
        course_delete_section($courseid,$section->section);
    }



    // Update course format option with the new number of session
    update_course((object)array('id' => $courseid,'numsections' => $weight-1));

    rebuild_course_cache($courseid);
    return $sid;
}