<?php

include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");
include_once("./Modules/Test/classes/class.ilObjTest.php");

class ilFlexNowPassViewUIHookGUI extends ilUIHookPluginGUI {

  function getHTML($a_comp, $a_part, $a_par = array())
  {
    $pl = new ilFlexNowPassViewPlugin();
    $ret = array();
    $ret["html"] = $a_par["html"];
    if ($a_par["tpl_id"] == "Services/Table/tpl.table2.html" && strpos($ret["html"], "tst_eval_all_table_nav=answered"))
    {
      $re = "(<th\s*>\n.*tst_eval_all_table_nav=answered.*\s*<\/a>\n.*<\/th>)";
      $append_str = '<th>'.$pl->txt("pass").'</th>';
      preg_match_all($re, $ret["html"], $matches, PREG_SET_ORDER, 0);
      $ret["html"] = str_replace($matches[0][0], $append_str.$matches[0][0], $ret["html"]);
      $ret["mode"] = ilUIHookPluginGUI::REPLACE;
      $test_id = intval($_GET["ref_id"]);
      $test_obj = new ilObjTest($test_id);
      $eval = new ilTestEvaluationData($test_obj);
      $repl = array();
      $passes = array();
      preg_match_all("/active_id=(\d*)&amp;cmd=detailedEvaluation/", $ret["html"], $matches);
      foreach ($matches[1] as $value) {
        $active_id = intval($value);
        $userdata = $eval->getParticipant($active_id);
        $udf = (new ilObjUser($userdata->getUserID()))->getUserDefinedData();
        $passes[] = $udf['f_1'];
        $repl[] = "/flexnow_pass_value/";
      }
      $ret["html"] = preg_replace($repl,$passes,$ret["html"],1);
      return $ret;
    }
    if ($a_par["tpl_id"] == "Modules/Test/tpl.table_evaluation_all.html") {
        $search_str = '<!-- BEGIN ects_grade --><td class="std" style="vertical-align:top;">{ECTS_GRADE}</td><!-- END ects_grade -->';
        $append_str = '<td class="std" style="vertical-align:top;">flexnow_pass_value</td>';
        $ret["html"] = str_replace($search_str, $append_str, $ret["html"]);
        $ret['mode'] = ilUIHookPluginGUI::REPLACE;
        return $ret;
    }
  }
}
?>
