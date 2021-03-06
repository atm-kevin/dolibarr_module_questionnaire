<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/questionnaire.php
 * 	\ingroup	questionnaire
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/questionnaire.lib.php';
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/class/invitation.class.php');
dol_include_once('abricot/includes/lib/admin.lib.php');

// Translations
$langs->load("questionnaire@questionnaire");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$type='questionnaire';
$label = GETPOST('label','alpha');
$scandir = GETPOST('scan_dir','alpha');


/*
 * Actions
 */
if ($action == 'updateMask') {
	
	$maskconstrefleter = GETPOST('maskconstrefletter', 'alpha');
	$maskrefletter = GETPOST('maskrefletter', 'alpha');
	if ($maskconstrefleter) $res = dolibarr_set_const($db, $maskconstrefleter, $maskrefletter, 'chaine', 0, '', $conf->entity);
		
	if (! $res > 0) $error ++;
	if (! $error) setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	else setEventMessage($langs->trans("Error"), 'errors');
	
} elseif($action === 'setmod') dolibarr_set_const($db, "QUESTIONNAIRE_ADDON", $value, 'chaine', 0, '', $conf->entity);
elseif($action === 'setmodanswer') dolibarr_set_const($db, "QUESTIONNAIRE_ANSWER_ADDON", $value, 'chaine', 0, '', $conf->entity);
else if (preg_match('/set_(.*)/',$action,$reg)) {
	
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code,'none'), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else dol_print_error($db);
	
}
	
if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

// Activate a model
if ($action == 'set')
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

if ($action == 'del')
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		if ($conf->global->QUESTIONNAIRE_ADDON_PDF == "$value") dolibarr_del_const($db, 'QUESTIONNAIRE_ADDON_PDF',$conf->entity);
	}
}

// Set default model
if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "QUESTIONNAIRE_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
	
		$conf->global->QUESTIONNAIRE_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}



/*
 * View
 */
$page_name = "questionnaireSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = questionnaireAdminPrepareHead();
dol_fiche_head(
    $head,
    'answer',
    $langs->trans("Module104961Name"),
    1,
    "questionnaire@questionnaire"
);

// Setup page goes here
$dirmodels = array_merge(array (
		'/'
), ( array ) $conf->modules_parts['models']);
/*
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . "</td>\n";
print '<td>' . $langs->trans("Description") . "</td>\n";
print '<td nowrap>' . $langs->trans("Example") . "</td>\n";
print '<td align="center" width="60">' . $langs->trans("Status") . '</td>';
print '<td align="center" width="16">' . $langs->trans("Infos") . '</td>';
print '</tr>' . "\n";

clearstatcache();

$form = new Form($db);

foreach ( $dirmodels as $reldir ) {
	$dir = dol_buildpath($reldir . "core/modules/questionnaire/");
	
	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			$var = true;
			
			while ( ($file = readdir($handle)) !== false ) {
				
				if (preg_match('/mod_questionnaire_/', $file) && substr($file, dol_strlen($file) - 3, 3) == 'php') {
					$file = substr($file, 0, dol_strlen($file) - 4);
					require_once $dir . $file . '.php';
					
					$module = new $file();
					
					// Show modules according to features level
					if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
						continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
							continue;
							
							if ($module->isEnabled()) {
								$var = ! $var;
								print '<tr ' . $bc[$var] . '><td>' . $module->nom . "</td><td>\n";
								print $module->info();
								print '</td>';
								
								// Show example of numbering module
								print '<td class="nowrap">';
								$tmp = $module->getExample();
								if (preg_match('/^Error/', $tmp))
									print '<div class="error">' . $langs->trans($tmp) . '</div>';
									elseif ($tmp == 'NotConfigured')
									print $langs->trans($tmp);
									else
										print $tmp;
										print '</td>' . "\n";
										
										print '<td align="center">';
										if ($conf->global->QUESTIONNAIRE_ADDON == "$file") {
											print img_picto($langs->trans("Activated"), 'switch_on');
										} else {
											print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmod&amp;value=' . $file . '">';
											print img_picto($langs->trans("Disabled"), 'switch_off');
											print '</a>';
										}
										print '</td>';
										
										$businesscase = new Questionnaire($db);
										$businesscase->initAsSpecimen();
										
										// Info
										$htmltooltip = '';
										$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
										$nextval = $module->getNextValue($user->id, 'contract', '', '');
										// Keep " on nextval
										if ("$nextval" != $langs->trans("NotAvailable")) {
											$htmltooltip .= '' . $langs->trans("NextValue") . ': ';
											if ($nextval) {
												$htmltooltip .= $nextval . '<br>';
											} else {
												$htmltooltip .= $langs->trans($module->error) . '<br>';
											}
										}
										
										print '<td align="center">';
										print $form->textwithpicto('', $htmltooltip, 1, 0);
										print '</td>';
										
										print "</tr>\n";
							}
				}
			}
			closedir($handle);
		}
	}
}*/
//print "</table><br>\n";


print '<table class="noborder" width="100%">';
setup_print_title('QUESTIONNAIRE_ANSWER_CONFIG');
// DEFAULT AFTER ANSWER HTML
setup_print_on_off('QUESTIONNAIRE_ANSWER_TEXTAREA_WYSWYG');
print '</table>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . "</td>\n";
print '<td>' . $langs->trans("Description") . "</td>\n";
print '<td nowrap>' . $langs->trans("Example") . "</td>\n";
print '<td align="center" width="60">' . $langs->trans("Status") . '</td>';
print '<td align="center" width="16">' . $langs->trans("Infos") . '</td>';
print '</tr>' . "\n";

clearstatcache();

$form = new Form($db);

foreach ( $dirmodels as $reldir ) {
	$dir = dol_buildpath($reldir . "core/modules/answer/");
	
	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			$var = true;
			
			while ( ($file = readdir($handle)) !== false ) {
				
				if (preg_match('/mod_answer_/', $file) && substr($file, dol_strlen($file) - 3, 3) == 'php') {
					$file = substr($file, 0, dol_strlen($file) - 4);
					require_once $dir . $file . '.php';
					
					$module = new $file();
					
					// Show modules according to features level
					if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
						continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
							continue;
							
							if ($module->isEnabled()) {
								$var = ! $var;
								print '<tr ' . $bc[$var] . '><td>' . $module->nom . "</td><td>\n";
								print $module->info();
								print '</td>';
								
								// Show example of numbering module
								print '<td class="nowrap">';
								$tmp = $module->getExample();
								if (preg_match('/^Error/', $tmp))
									print '<div class="error">' . $langs->trans($tmp) . '</div>';
									elseif ($tmp == 'NotConfigured')
									print $langs->trans($tmp);
									else
										print $tmp;
										print '</td>' . "\n";
										
										print '<td align="center">';
										if ($conf->global->QUESTIONNAIRE_ANSWER_ADDON == "$file") {
											print img_picto($langs->trans("Activated"), 'switch_on');
										} else {
											print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmodanswer&amp;value=' . $file . '">';
											print img_picto($langs->trans("Disabled"), 'switch_off');
											print '</a>';
										}
										print '</td>';
										
										$businesscase = new InvitationUser($db);
										$businesscase->initAsSpecimen();
										
										// Info
										$htmltooltip = '';
										$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
										$nextval = $module->getNextValue($user->id, 'contract', '', '');
										// Keep " on nextval
										if ("$nextval" != $langs->trans("NotAvailable")) {
											$htmltooltip .= '' . $langs->trans("NextValue") . ': ';
											if ($nextval) {
												$htmltooltip .= $nextval . '<br>';
											} else {
												$htmltooltip .= $langs->trans($module->error) . '<br>';
											}
										}
										
										print '<td align="center">';
										print $form->textwithpicto('', $htmltooltip, 1, 0);
										print '</td>';
										
										print "</tr>\n";
							}
				}
			}
			closedir($handle);
		}
	}
}




print "</table><br>\n";

/*
 * MODELS PDF
 */
// Module to build doc
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$type."'";
$sql.= " AND entity = ".$conf->entity;
$resql=$db->query($sql);
if ($resql)
{
	$i = 0;
	$num_rows=$db->num_rows($resql);
	while ($i < $num_rows)
	{
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
}
else
{
	dol_print_error($db);
}

print '<br>';

// PDF DOCUMENT MODELS

print load_fiche_titre($langs->trans("AnswerDocumentTemplates"), '', '');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

clearstatcache();

$var=true;
foreach ($dirmodels as $reldir)
{
    foreach (array('','/doc') as $valdir)
    {
    	$dir = dol_buildpath($reldir."core/modules/questionnaire".$valdir);
        if (is_dir($dir))
        {
            $handle=opendir($dir);
            if (is_resource($handle))
            {
                while (($file = readdir($handle))!==false)
                {
                    $filelist[]=$file;
                }
                closedir($handle);
                arsort($filelist);

                foreach($filelist as $file)
                {
                    if (preg_match('/\.modules\.php$/i',$file) && preg_match('/^(pdf_|doc_)/',$file))
                    {

                    	if (file_exists($dir.'/'.$file))
                    	{
                    		$name = substr($file, 4, dol_strlen($file) -16);
	                        $classname = substr($file, 0, dol_strlen($file) -12);

	                        require_once $dir.'/'.$file;
	                        $module = new $classname($db);

	                        $modulequalified=1;
	                        if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified=0;
	                        if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified=0;

	                        if ($modulequalified)
	                        {
	                            $var = !$var;
	                            print '<tr class="oddeven"><td width="100">';
	                            print (empty($module->name)?$name:$module->name);
	                            print "</td><td>\n";
	                            if (method_exists($module,'info')) print $module->info($langs);
	                            else print $module->description;
	                            print '</td>';

	                            // Active
	                            if (in_array($name, $def))
	                            {
	                            	print '<td align="center">'."\n";
	                            	print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&value='.$name.'">';
	                            	print img_picto($langs->trans("Enabled"),'switch_on');
	                            	print '</a>';
	                            	print '</td>';
	                            }
	                            else
	                            {
	                                print '<td align="center">'."\n";
	                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
	                                print "</td>";
	                            }

	                            // Defaut
	                            print '<td align="center">';
	                            if ($conf->global->QUESTIONNAIRE_ADDON_PDF == $name)
	                            {
	                                print img_picto($langs->trans("Default"),'on');
	                            }
	                            else
	                            {
	                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	                            }
	                            print '</td>';

	                           // Info
		    					$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
					    		$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
			                    if ($module->type == 'pdf')
			                    {
			                        $htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
			                    }
					    		$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
					    		$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
					    		$htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang,1,1);


	                            print '<td align="center">';
	                            print $form->textwithpicto('',$htmltooltip,1,0);
	                            print '</td>';

	                         

	                            print "</tr>\n";
	                        }
                    	}
                    }
                }
            }
        }
    }
}


print '</table>';

/*
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

// Example with a yes / no select
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParamLabel").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_CONSTNAME">';
print $form->selectyesno("CONSTNAME",$conf->global->CONSTNAME,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParamLabel").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">'; // Keep form because ajax_constantonoff return single link with <a> if the js is disabled
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_CONSTNAME">';
print ajax_constantonoff('CONSTNAME');
print '</form>';
print '</td></tr>';

print '</table>';*/

llxFooter();

$db->close();