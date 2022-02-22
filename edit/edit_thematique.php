<?php


$context = context_course::instance($courseid);


$summary = $DB->get_records('block_summary',array('courseid'=>$courseid));


// Set up page parameters
$PAGE->set_course($course);
//$PAGE->requires->css('/blocks/summary/edit.php');
$PAGE->set_url('/blocks/summary/edit.php', array('id' => $courseid));
$PAGE->set_context($context);
$title = get_string('edit', 'block_summary');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);
$PAGE->set_pagelayout('admin');
$PAGE->requires->css(new moodle_url('/blocks/summary/custom_nestable.css'));

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');

// Check user is logged in and capable of grading
require_login($course, false);
require_capability('block/summary:managepages', $context);




$newsectionstartid = 1000000000;

$submit = optional_param('isSubmited', null, PARAM_ALPHA);
$treedata = optional_param('treedata', null, PARAM_RAW);

$binexpiry = get_config('tool_recyclebin', 'coursebinexpiry') / 86400;

if ($submit != false && $treedata != false && strlen($treedata) > 5)
{
    $data = json_decode($treedata);
    process_section_thematique($courseid, $data, $newsectionstartid);
}

// Get specific block config
//$block = $DB->get_record('block_instances', array('id' => $id));
//$config = unserialize(base64_decode($block->configdata));

// Start page output
echo $OUTPUT->header();
echo $OUTPUT->heading($title, 2);
echo $OUTPUT->container_start('block_summary');

$delbutton = '<button class="del fa fa-times"></button>';
$hidebutton = '<button class="hide fa fa-eye"></button>';
$dragbutton = '<button class="move fa fa-arrows"></button>';
$editbutton = '<button class="edit fa fa-pencil"></button>';
$buttonspacer = '<span class="dd-buttonsblockspacer"></span>';
$buttons = $buttonspacer.$editbutton.$buttonspacer.$hidebutton.$buttonspacer.$delbutton;
$coursenextweight = get_course_next_weight($courseid)+1;

$PAGE->requires->js_call_amd('block_summary/thematique', 'init', array($newsectionstartid, $coursenextweight, $dragbutton, $buttonspacer, $buttons));

echo get_course_tree_html($courseid);
/*
echo '
<div id="dd" class="dd">
    <ol class="dd-list">
        <li class="dd-item" data-id="1">
            <div class="dd-handle">Page 1</div>'.$buttons.'
        </li>
        <li class="dd-item" data-id="2">
            <div class="dd-handle">Page 2</div>'.$buttons.'
        </li>
        <li class="dd-item" data-id="3">
            <div class="dd-handle">Page 3</div>'.$buttons.'
            <ol class="dd-list">
                <li class="dd-item" data-id="4">
                    <div class="dd-handle">Page 4</div>'.$buttons.'
                </li>
                <li class="dd-item" data-id="5">
                    <div class="dd-handle">Page 5</div>'.$buttons.'
                </li>

            </ol>
        </li>
		<li class="dd-item" data-id="6">
            <div class="dd-handle">Page 6</div>'.$buttons.'
        </li>
    </ol>
</div>';
*/
echo '
<form id="form" action="" method="POST">
<button id="addsection"><i class="fa fa-plus" aria-hidden="true"></i> Ajouter une section</button>
<input type="hidden" id="treedata" name="treedata" />
<input type="hidden" id="isSubmited" name="isSubmited" />
<input type="submit" id="save" name="save" value="Enregistrer les modifications" />
</form>

<div id="dialog-confirm" title="Confirmation de la suppression de la section" style="display:none">
  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Attention : La section ainsi que son contenu seront supprimés lors de la validation du formulaire !<br/>Cette modification est irréversible !</p>
</div>
<div id="dialog-confirm-save" title="Confirmation de la sauvegarde de modifications" style="display:none">
  <p>
    <span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>
    Attention : Les sections suivantes vont être supprimées :
    <ol id="dialog-confirm-save-list">
    
    </ol>
    <br/>
    Il n\'existe pas de corbeille de sections, ces dernières seront donc supprimées définitivement. Les activités contenues dans ces sections seront stockées '.$binexpiry.' jours dans la corbeille du parcours.
  </p>
</div>
<script type="text/javascript">

</script>';

echo '<div id="main_form_div">' . $OUTPUT->container_end() . '</div>';


echo $OUTPUT->footer();
