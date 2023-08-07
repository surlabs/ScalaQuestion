<?php

declare(strict_types=1);

/**
 * This file is part of the Scala Question type Plugin for ILIAS
 * Copyright (c) 2023 Universität Rostock
 * Developed and published by Laboratorio de Soluciones del Sur, Sociedad Limitada
 *
 * ScalaQuestion is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 */

/**
 *
 * @author            Jesus Copado <jcopado@surlabs.es>
 * @version           $Id: 0.0$
 * @ingroup           ModulesTestQuestionPool
 *
 * @ilctrl_iscalledby assScalaQuestionGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilQuestionEditGUI, ilTestExpressPageObjectGUI
 * @ilctrl_calls      assScalaQuestionGUI: ilFormPropertyDispatchGUI
 */
class assScalaQuestionGUI extends assQuestionGUI
{
    /**
     * @var ilassScalaQuestionPlugin    The plugin object
     */
    var $plugin = null;

    /**
     * @var assScalaQuestion    The question object
     */
    public $object;

    /**
     * Constructor
     *
     * @param int $id The database id of a question object
     * @access public
     * @throws ilPluginException
     */
    public function __construct($id = -1)
    {
        parent::__construct();

        $this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assScalaQuestion");
        $this->object = new assScalaQuestion();
        if ($id >= 0) {
            $this->object->loadFromDb($id);
        }
    }

    /**
     * Formulario de edición de preguntas
     *
     * @param bool $checkonly
     * @return bool
     */
    public function editQuestion($checkonly = false): bool
    {
        global $DIC;

        //Si la scala no se ha iniciado por que la pregunta de la actual id
        //es nueva (import o nueva) y por tanto id = -1
        //mostrar formulario de importación
        if ($this->object->getScala()->getQuestionId() == -1) {
            $this->importQuestionFromILIASSurveyView($checkonly);
        } elseif ($this->object->getScala() !== null and $this->object->getScala()->getQuestionId() != -1) {
            //Si la pregunta está guardada en la BD
                $this->editQuestionView($checkonly);
        }

        return true;
    }

    /**
     * Evaluates a posted edit form and writes the form data in the question object
     *
     * @param bool $always
     * @return integer A positive value, if one of the required fields wasn't set, else 0
     */
    protected function writePostData($always = false): int
    {
        $hasErrors = (!$always) ? $this->editQuestion(true) : false;
        if (!$hasErrors) {
            $this->writeQuestionGenericPostData();

            // Here you can write the question type specific values
            // Some question types define the maximum points directly,
            // other calculate them from other properties
            $this->object->setPoints((int) $_POST["points"]);

            $this->saveTaxonomyAssignments();
            return 0;
        }
        return 1;
    }

    /**
     * Get the HTML output of the question for a test
     * (this function could be private)
     *
     * @param integer $active_id                     The active user id
     * @param integer $pass                          The test pass
     * @param boolean $is_postponed                  Question is postponed
     * @param boolean $use_post_solutions            Use post solutions
     * @param boolean $show_specific_inline_feedback Show a specific inline feedback
     * @return string
     */
    public function getTestOutput(
        $active_id,
        $pass = null,
        $is_postponed = false,
        $use_post_solutions = false,
        $show_specific_inline_feedback = false
    ): string {
        if (is_null($pass)) {
            $pass = ilObjTest::_getPass($active_id);
        }

        $solution = $this->object->getSolutionSubmit($active_id, $pass, null);
        $value1 = $solution["value1"] ?? "";
        $value2 = $solution["value2"] ?? "";

        // fill the question output template
        // in out example we have 1:1 relation for the database field
        $template = $this->plugin->getTemplate("tpl.il_as_qpl_xqscala_output.html");

        $template->setVariable("QUESTION_ID", $this->object->getId());
        $question_text = $this->object->getQuestion();
        $template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($question_text, true));
        $template->setVariable("LABEL_VALUE1", $this->plugin->txt('label_value1'));
        $template->setVariable("LABEL_VALUE2", $this->plugin->txt('label_value2'));

        $template->setVariable("VALUE1", ilLegacyFormElementsUtil::prepareFormOutput($value1));
        $template->setVariable("VALUE2", ilLegacyFormElementsUtil::prepareFormOutput($value2));

        $question_output = $template->get();
        return $this->outQuestionPage("", $is_postponed, $active_id, $question_output);
    }

    /**
     * Get the output for question preview
     * (called from ilObjQuestionPoolGUI)
     *
     * @param boolean $show_question_only
     * @param bool    $show_inline_feedback
     * @return string
     */
    public function getPreview($show_question_only = false, $show_inline_feedback = false): string
    {
        $solution = is_object($this->getPreviewSession()) ? $this->getPreviewSession()->getParticipantsSolution(
        ) : array('value1' => null, 'value2' => null);

        // Fill the template with a preview version of the question
        $template = $this->plugin->getTemplate("tpl.il_as_qpl_xqscala_output.html");
        $question_text = $this->object->getQuestion();
        $template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($question_text, true));
        $template->setVariable("QUESTION_ID", $this->object->getId());
        $template->setVariable("LABEL_VALUE1", $this->plugin->txt('label_value1'));
        $template->setVariable("LABEL_VALUE2", $this->plugin->txt('label_value2'));

        $template->setVariable("VALUE1", ilUtil::prepareFormOutput($solution['value1']));
        $template->setVariable("VALUE2", ilUtil::prepareFormOutput($solution['value2']));

        $question_output = $template->get();
        if (!$show_question_only) {
            // get page object output
            $question_output = $this->getILIASPage($question_output);
        }
        return $question_output;
    }

    /**
     * Get the question solution output
     * @param integer $active_id             The active user id
     * @param integer $pass                  The test pass
     * @param boolean $graphicalOutput       Show visual feedback for right/wrong answers
     * @param boolean $result_output         Show the reached points for parts of the question
     * @param boolean $show_question_only    Show the question without the ILIAS content around
     * @param boolean $show_feedback         Show the question feedback
     * @param boolean $show_correct_solution Show the correct solution instead of the user solution
     * @param boolean $show_manual_scoring   Show specific information for the manual scoring output
     * @param bool    $show_question_text
     * @return string solution output of the question as HTML code
     * @throws ilTestException
     */
    function getSolutionOutput(
        $active_id,
        $pass = null,
        $graphicalOutput = false,
        $result_output = false,
        $show_question_only = true,
        $show_feedback = false,
        $show_correct_solution = false,
        $show_manual_scoring = false,
        $show_question_text = true
    ): string {
        // get the solution of the user for the active pass or from the last pass if allowed
        if (($active_id > 0) && (!$show_correct_solution)) {
            $solution = $this->object->getSolutionStored($active_id, $pass, true);
            $value1 = isset($solution["value1"]) ? $solution["value1"] : "";
            $value2 = isset($solution["value2"]) ? $solution["value2"] : "";
        } else {
            // show the correct solution
            $value1 = $this->plugin->txt("any_text");
            $value2 = $this->object->getMaximumPoints();
        }

        // get the solution template
        $template = $this->plugin->getTemplate("tpl.il_as_qpl_xqscala_output_solution.html");
        $solutiontemplate = new ilTemplate(
            "tpl.il_as_tst_solution_output.html", true, true, "Modules/TestQuestionPool"
        );

        if (($active_id > 0) && (!$show_correct_solution)) {
            if ($graphicalOutput) {
                // copied from assNumericGUI, yet not really understood
                if ($this->object->getStep() === null) {
                    $reached_points = $this->object->getReachedPoints($active_id, $pass);
                } else {
                    $reached_points = $this->object->calculateReachedPoints($active_id, $pass);
                }

                // output of ok/not ok icons for user entered solutions
                // in this example we have ony one relevant input field (points)
                // so we just need to set the icon beneath this field
                // question types with partial answers may have a more complex output
                if ($reached_points == $this->object->getMaximumPoints()) {
                    $template->setCurrentBlock("icon_ok");
                    $template->setVariable("ICON_OK", ilUtil::getImagePath("icon_ok.svg"));
                    $template->setVariable("TEXT_OK", $this->lng->txt("answer_is_right"));
                    $template->parseCurrentBlock();
                } else {
                    $template->setCurrentBlock("icon_ok");
                    $template->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_not_ok.svg"));
                    $template->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_wrong"));
                    $template->parseCurrentBlock();
                }
            }
        }

        // fill the template variables
        // adapt this to your structure of answers
        $template->setVariable("LABEL_VALUE1", $this->plugin->txt('label_value1'));
        $template->setVariable("LABEL_VALUE2", $this->plugin->txt('label_value2'));

        $template->setVariable(
            "VALUE1", empty($value1) ? "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" : ilUtil::prepareFormOutput($value1)
        );
        $template->setVariable(
            "VALUE2", empty($value2) ? "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" : ilUtil::prepareFormOutput($value2)
        );

        $questiontext = $this->object->getQuestion();
        if ($show_question_text == true) {
            $template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, true));
        }

        $questionoutput = $template->get();

        $feedback = ($show_feedback && !$this->isTestPresentationContext()) ? $this->getGenericFeedbackOutput(
            $active_id, $pass
        ) : "";
        if (strlen($feedback)) {
            $cssClass = ($this->hasCorrectSolution($active_id, $pass) ?
                ilAssQuestionFeedback::CSS_CLASS_FEEDBACK_CORRECT : ilAssQuestionFeedback::CSS_CLASS_FEEDBACK_WRONG
            );

            $solutiontemplate->setVariable("ILC_FB_CSS_CLASS", $cssClass);
            $solutiontemplate->setVariable("FEEDBACK", $this->object->prepareTextareaOutput($feedback, true));
        }
        $solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);

        $solution_output = $solutiontemplate->get();
        if (!$show_question_only) {
            // get page object output
            $solution_output = $this->getILIASPage($solution_output);
        }
        return $solution_output;
    }

    /**
     * Returns the answer specific feedback for the question
     *
     * @param array $userSolution Array with the user solutions
     * @return string HTML Code with the answer specific feedback
     * @access public
     */
    public function getSpecificFeedbackOutput($userSolution): string
    {
        // By default, no answer specific feedback is defined
        $output = '';
        return $this->object->prepareTextareaOutput($output, true);
    }

    /**
     * Sets the ILIAS tabs for this question type
     * called from ilObjTestGUI and ilObjQuestionPoolGUI
     */
    public function setQuestionTabs(): void
    {
        parent::setQuestionTabs();
    }

    /**
     * Muestra el formulario para subir el XML con las preguntas
     * @return void
     */
    public function importQuestionFromILIASSurveyView($checkonly = false)
    {
        global $DIC;
        $tabs = $DIC->tabs();

        //Set all parameters required
        $tabs->activateTab('edit_properties');

        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt('qpl_qst_xqscala_import_xml'));

        //Upload XML file
        $item = new ilFileInputGUI($this->lng->txt('qpl_qst_xqscala_import_xml_file'), 'questions_xml');
        $item->setSuffixes(array('xml'));
        $form->addItem($item);

        $hiddenFirstId = new ilHiddenInputGUI('first_question_id');
        $hiddenFirstId->setValue($_GET['q_id']);
        $form->addItem($hiddenFirstId);

        $form->addCommandButton('importQuestionFromILIASSurvey', $this->lng->txt('import'));
        $form->addCommandButton('editQuestion', $this->lng->txt('cancel'));

        $this->tpl->setContent($form->getHTML());
    }

    /**
     * Muestra el formulario de edición de las preguntas
     * @param bool $checkonly
     * @return bool
     */
    public function editQuestionView($checkonly = false): bool
    {
        global $DIC;
        $tabs = $DIC['ilTabs'];
        $tabs->setTabActive('edit_question');
        $save = $this->isSaveCommand();
        $this->getQuestionTemplate();

        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $this->editForm = $form;

        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->outQuestionType());
        $form->setMultipart(false);
        $form->setTableWidth("100%");
        $form->setId("orderinghorizontal");

        $this->addBasicQuestionFormProperties($form);

        //SPECIFIC PART

        $this->populateTaxonomyFormSection($form);

        $form->addCommandButton("analyze", $this->lng->txt('analyze_errortext'));
        $this->addQuestionFormCommandButtons($form);

        $errors = false;

        if ($save) {
            $form->setValuesByPost();
            $errors = !$form->checkInput();
            $form->setValuesByPost(
            ); // again, because checkInput now performs the whole stripSlashes handling and we need this if we don't want to have duplication of backslashes
            if ($errors) {
                $checkonly = false;
            }
        }

        if (!$checkonly) {
            $this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
        }
        return $errors;
    }

    /**
     * Importa las preguntas desde
     * @return void
     */
    public function importQuestionFromILIASSurvey()
    {
        global $DIC;

        //Set all parameters required
        $DIC->tabs()->activateTab('edit_properties');

        //Getting the xml file from $_FILES
        if (file_exists($_FILES["questions_xml"]["tmp_name"])) {
            $xml_file = $_FILES["questions_xml"]["tmp_name"];//do not allow import directly in tests
            if (isset($_GET['calling_test'])) {
                ilUtil::sendFailure($this->plugin->txt('error_import_not_allowed_in_tests'), true);
            } else {
                $import = new assScalaQuestionSurveyImport(
                    $this->plugin, $this->object, (int) $_POST['first_question_id']
                );
                $num_of_questions = $import->import($xml_file);
                ilUtil::sendSuccess($this->plugin->txt('success_import') . (string) $num_of_questions, true);
            }
        } else {
            ilUtil::sendFailure($this->plugin->txt('error_import_xml_not_loaded'), true);
        }

        $DIC->ctrl()->redirect($this, 'editQuestion');
    }
}
