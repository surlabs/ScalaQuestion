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

        //Si la pregunta está guardada en la BD
        if ($this->object->getScala() !== null) {
            if ($this->object->getScala()->getQuestionId() != -1) {
                return $this->editQuestionView($checkonly);
            }
        }

        //Si la scala no se ha iniciado por que la pregunta de la actual id
        //es nueva (import o nueva) y por tanto id = -1
        //mostrar formulario de importación
        if (!$checkonly) {
            $DIC->ctrl()->redirect($this, 'importQuestionFromILIASSurveyView');
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
        $this->writeQuestionGenericPostData();

        //Update columns
        $new_columns = [];
        for ($c = 0; $c <= $this->object->getScala()->getNumColumns(); $c++) {
            if (isset($_POST['scala_cell_0_' . $c])) {
                if ($c !== 0) {
                    $new_columns[$c - 1] = $_POST['scala_cell_0_' . $c];
                }
            }
        }
        $this->object->getScala()->setColumns($new_columns);

        //Update items
        $new_items = [];
        for ($i = 0; $i <= $this->object->getScala()->getNumItems(); $i++) {
            if (isset($_POST['scala_cell_' . $i . '_0'])) {
                if ($i !== 0) {
                    $new_items[$i - 1] = $_POST['scala_cell_' . $i . '_0'];
                }
            }
        }
        $this->object->getScala()->setItems($new_items);

        //Update evaluation scala
        $new_evaluation_scala = [];
        //Calculate max points from each row
        $max_points_array = [];

        for ($i = 1; $i <= $this->object->getScala()->getNumItems(); $i++) {
            for ($c = 1; $c <= $this->object->getScala()->getNumColumns(); $c++) {
                if (isset($_POST['scala_cell_' . $i . '_' . $c])) {
                    if ($i !== 0) {
                        $points = $_POST['scala_cell_' . $i . '_' . $c];
                        $new_evaluation_scala[$i][$c] = $points;

                        //calculate max points
                        if ((float) $max_points_array[$i] < (float) $points) {
                            $max_points_array[$i] = (float) $points;
                        }
                    }
                }
            }
        }
        $this->object->getScala()->setEvaluationScala($new_evaluation_scala);

        //parse question text searching for feedback scala
        $this->object->getScala()->setFeedbackScala($this->object->parseFeedback($this->object->getQuestion()));
        //Update JSON to ensure it is saved to DB
        $this->object->getScala()->toJSON();

        //var_dump($this->object->getScala());exit;
        //TODO no estamos checkeando nada
        //$hasErrors = (!$always) ? $this->editQuestion(true) : false;
        //if (!$hasErrors) {

        $this->object->setPoints((int) array_sum($max_points_array) / $this->object->getScala()->getNumItems());

        //Get Scala data from user Post

        $this->saveTaxonomyAssignments();
        return 0;
        //}
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
        global $DIC;

        if (is_null($pass)) {
            $pass = ilObjTest::_getPass($active_id);
        }

        $user_solution = $this->object->getSolutionSubmit($active_id, $pass);
        // Fill the template with a preview version of the question
        $template = $this->plugin->getTemplate("tpl.il_as_qpl_xqscala_output.html");

        // obtenemos el texto sin placeholders de feedback
        $question_text = $this->object->parseText($this->object->getQuestion());

        $template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($question_text, true));

        //añadimos el CSS
        $DIC->globalScreen()->layout()->meta()->addCss(
            'Customizing/global/plugins/Modules/TestQuestionPool/Questions/assScalaQuestion/templates/tpl.il_as_qpl_xqscala_output.css'
        );

        //añadimos el Javascript
        $DIC->globalScreen()->layout()->meta()->addJs(
            'Customizing/global/plugins/Modules/TestQuestionPool/Questions/assScalaQuestion/templates/tpl.il_as_qpl_xqscala_output.js'
        );

        //Rellenamos los headers de las columnas
        $scala = $this->object->getScala()->getBlankScala();
        for ($col = 0; $col < sizeof($scala[0]); $col++) {
            // Set cell block
            $template->setCurrentBlock('header_row');

            // Set the content for the cell
            $template->setVariable("HEADER_TEXT", $scala[0][$col]);
            $template->setVariable("COLUMN_INDEX", (string) $col);
            $template->setVariable("ROW_INDEX", "0");

            // Parse the current cell
            $template->parseCurrentBlock();
        }

        $points_scala = $this->object->getScala()->getScalaWithPoints();
        //Rellenamos el resto de filas
        for ($row = 1; $row < sizeof($scala); $row++) {
            // Set row block
            $template->setCurrentBlock('scala_rows');

            //Rellenamos el header de cada fila
            $template->setCurrentBlock('scala_header');
            $template->setVariable("HEADER_TEXT", $scala[$row][0]);

            $template->parseCurrentBlock();

            // Iterate over the matrix columns
            for ($col = 1; $col < sizeof($scala[$row]); $col++) {
                // Set cell block
                $template->setCurrentBlock('scala_cells');

                // Set the content for the cell
                $template->setVariable("ROW", (string) $row);
                $template->setVariable("COLUMN", (string) $col);
                $template->setVariable("QUESTION_ID", $this->object->getId());
                $template->setVariable("COLUMN_INDEX", (string) $col);
                $template->setVariable("ROW_INDEX", "0");

                if ($user_solution[$row - 1] == $col) {
                    $template->setVariable("CHECKED", "checked");
                }

                // Parse the current cell
                $template->parseCurrentBlock();
            }
            $template->setCurrentBlock('scala_rows');
            // Parse the current row
            $template->parseCurrentBlock();
        }

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
        global $DIC;

        $user_solution = is_object($this->getPreviewSession()) ? $this->getPreviewSession()->getParticipantsSolution(
        ) : array();

        // Fill the template with a preview version of the question
        $template = $this->plugin->getTemplate("tpl.il_as_qpl_xqscala_output.html");

        // obtenemos el texto sin placeholders de feedback
        $question_text = $this->object->parseText($this->object->getQuestion());

        $template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($question_text, true));

        //añadimos el CSS
        $DIC->globalScreen()->layout()->meta()->addCss(
            'Customizing/global/plugins/Modules/TestQuestionPool/Questions/assScalaQuestion/templates/tpl.il_as_qpl_xqscala_output.css'
        );

        //añadimos el Javascript
        $DIC->globalScreen()->layout()->meta()->addJs(
            'Customizing/global/plugins/Modules/TestQuestionPool/Questions/assScalaQuestion/templates/tpl.il_as_qpl_xqscala_output.js'
        );

        //Rellenamos los headers de las columnas
        $scala = $this->object->getScala()->getBlankScala();
        for ($col = 0; $col < sizeof($scala[0]); $col++) {
            // Set cell block
            $template->setCurrentBlock('header_row');

            // Set the content for the cell
            $template->setVariable("HEADER_TEXT", $scala[0][$col]);
            $template->setVariable("COLUMN_INDEX", (string) $col);
            $template->setVariable("ROW_INDEX", "0");
            // Parse the current cell
            $template->parseCurrentBlock();
        }

        $points_scala = $this->object->getScala()->getScalaWithPoints();
        //Rellenamos el resto de filas
        for ($row = 1; $row < sizeof($scala); $row++) {
            // Set row block
            $template->setCurrentBlock('scala_rows');

            //Rellenamos el header de cada fila
            $template->setCurrentBlock('scala_header');
            $template->setVariable("HEADER_TEXT", $scala[$row][0]);

            $template->parseCurrentBlock();

            // Iterate over the matrix columns
            for ($col = 1; $col < sizeof($scala[$row]); $col++) {
                // Set cell block
                $template->setCurrentBlock('scala_cells');

                // Set the content for the cell
                $template->setVariable("ROW", (string) $row);
                $template->setVariable("COLUMN", (string) $col);
                $template->setVariable("QUESTION_ID", $this->object->getId());
                $template->setVariable("COLUMN_INDEX", (string) $col);
                $template->setVariable("ROW_INDEX", (string) $row);

                if ($user_solution[$row - 1] == $col) {
                    $template->setVariable("CHECKED", "checked");
                }

                // Parse the current cell
                $template->parseCurrentBlock();
            }
            $template->setCurrentBlock('scala_rows');
            // Parse the current row
            $template->parseCurrentBlock();
        }

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
        /*
        if ($active_id == 0) {
            //TODO LNG
            return "no active id";
        }

        if ($show_correct_solution) {
            //TODO LNG
            return "no correct solution for this question type";
        }

        global $DIC;

        if (is_null($pass)) {
            $pass = ilObjTest::_getPass($active_id);
        }



        $participant_solution = $this->object->getUserSolutionPreferingIntermediate($active_id, $pass)[0];

        if (!isset($participant_solution["value1"])) {
            //TODO LNG
            return "not answered";
        }

        $user_solution = json_decode($participant_solution["value1"]);

        // Fill the template with a preview version of the question
        $template = $this->plugin->getTemplate("tpl.il_as_qpl_xqscala_output.html");

        // obtenemos el texto sin placeholders de feedback
        $question_text = $this->object->parseText($this->object->getQuestion());

        $template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($question_text, true));

        //añadimos el CSS
        $DIC->globalScreen()->layout()->meta()->addCss(
            'Customizing/global/plugins/Modules/TestQuestionPool/Questions/assScalaQuestion/templates/tpl.il_as_qpl_xqscala_output.css'
        );

        //añadimos el Javascript
        $DIC->globalScreen()->layout()->meta()->addJs(
            'Customizing/global/plugins/Modules/TestQuestionPool/Questions/assScalaQuestion/templates/tpl.il_as_qpl_xqscala_output.js'
        );

        //Rellenamos los headers de las columnas
        $scala = $this->object->getScala()->getBlankScala();
        for ($col = 0; $col < sizeof($scala[0]); $col++) {
            // Set cell block
            $template->setCurrentBlock('header_row');

            // Set the content for the cell
            $template->setVariable("HEADER_TEXT", $scala[0][$col]);
            $template->setVariable("COLUMN_INDEX", (string) $col);
            $template->setVariable("ROW_INDEX", "0");

            // Parse the current cell
            $template->parseCurrentBlock();
        }

        $points_scala = $this->object->getScala()->getScalaWithPoints();
        //Rellenamos el resto de filas
        for ($row = 1; $row < sizeof($scala); $row++) {
            // Set row block
            $template->setCurrentBlock('scala_rows');

            //Rellenamos el header de cada fila
            $template->setCurrentBlock('scala_header');
            $template->setVariable("HEADER_TEXT", $scala[$row][0]);
            $template->parseCurrentBlock();

            // Iterate over the matrix columns
            for ($col = 1; $col < sizeof($scala[$row]); $col++) {
                // Set cell block
                $template->setCurrentBlock('scala_cells');

                // Set the content for the cell
                $template->setVariable("ROW", (string) $row);
                $template->setVariable("COLUMN", (string) $col);
                $template->setVariable("QUESTION_ID", $this->object->getId());
                $template->setVariable("COLUMN_INDEX", (string) $col);
                $template->setVariable("ROW_INDEX", (string) $row);

                if ($user_solution[$row - 1] == $col) {
                    $template->setVariable("CHECKED", "checked");
                } else {
                    $template->setVariable("DISABLE", "disabled");
                }

                // Parse the current cell
                $template->parseCurrentBlock();
            }
            $template->setCurrentBlock('scala_rows');
            // Parse the current row
            $template->parseCurrentBlock();
        }

        $question_output = $template->get();

        if (!$show_question_only) {
            // get page object output
            $question_output = $this->getILIASPage(
                $this->getSpecificFeedbackOutput(
                    $user_solution, $this->object->getReachedPoints($active_id, $pass)
                )
            );
        }
        return $question_output;*/
        return $this->getSpecificFeedbackOutput(
            $this->object->getSolutionSubmit($active_id, $pass),
            $this->object->getReachedPoints($active_id, $pass)
        );
    }

    /**
     * Returns the answer specific feedback for the question preview
     *
     * @param array $userSolution Array with the user solutions
     * @return string HTML Code with the answer specific feedback
     * @access public
     */
    public function getSpecificFeedbackOutput($userSolution, $reached_points = null): string
    {
        $max_points = $this->object->getPoints();
        $feedback = "";

        if ($reached_points == null) {
            $reached_points = $this->object->getReachedPointsForPreview();
        }
        $this->object->getScala()->setFeedbackScala($this->object->parseFeedback($this->object->getQuestion()));
        $feedback_scala = $this->object->getScala()->getFeedbackScala();
        $previous_maximal_points = 0.0;

        foreach ($feedback_scala as $minimal_points_for_this_feedback => $feedback_text) {

            $maximal_points = floatval(str_replace(',', '.', $minimal_points_for_this_feedback));
            if($reached_points >= $previous_maximal_points && $reached_points <= $maximal_points) {
                $feedback = $feedback_text;
                break;
            }
        }
        return $this->object->prepareTextareaOutput($feedback, true);
    }

    /**
     * Sets the ILIAS tabs for this question type
     * called from ilObjTestGUI and ilObjQuestionPoolGUI
     */
    public function setQuestionTabs(): void
    {
        global $DIC;
        $ilTabs = $DIC['ilTabs'];
        $ilTabs->clearTargets();

        $this->ctrl->setParameterByClass("ilAssQuestionPageGUI", "q_id", $_GET["q_id"]);
        $q_type = $this->object->getQuestionType();

        if (strlen($q_type)) {
            $classname = $q_type . "GUI";
            $this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
            $this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET["q_id"]);
        }

        if ($_GET["q_id"]) {
            $this->addTab_Question($ilTabs);
        }

        $this->addBackTab($ilTabs);
    }

    /**
     * Muestra el formulario para subir el XML con las preguntas
     * @return void
     */
    public function importQuestionFromILIASSurveyView()
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
    public function editQuestionView(bool $checkonly = false): bool
    {
        global $DIC;
        $tabs = $DIC['ilTabs'];
        $tabs->setTabActive('edit_question');
        $save = $this->isSaveCommand();
        $this->getQuestionTemplate();

        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->outQuestionType());
        $form->setId("xqscala_edit");

        $form = $this->addQuestionFormProperties($form);

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
     * @param $form ilPropertyFormGUI
     * @return ilPropertyFormGUI
     */
    public function addQuestionFormProperties($form): ilPropertyFormGUI
    {
        global $DIC;

        // title
        $title = new ilTextInputGUI($this->lng->txt("title"), "title");
        $title->setMaxLength(100);
        $title->setValue($this->object->getTitle());
        $title->setRequired(true);
        $form->addItem($title);

        if (!$this->object->getSelfAssessmentEditingMode()) {
            // author
            $author = new ilTextInputGUI($this->lng->txt("author"), "author");
            $author->setValue($this->object->getAuthor());
            $author->setRequired(true);
            $form->addItem($author);

            // description
            $description = new ilTextInputGUI($this->lng->txt("description"), "comment");
            $description->setValue($this->object->getComment());
            $description->setRequired(false);
            $form->addItem($description);
        } else {
            // author as hidden field
            $hi = new ilHiddenInputGUI("author");
            $author = ilUtil::prepareFormOutput($this->object->getAuthor());
            if (trim($author) == "") {
                $author = "-";
            }
            $hi->setValue($author);
            $form->addItem($hi);
        }

        // lifecycle
        $lifecycle = new ilSelectInputGUI($this->lng->txt('qst_lifecycle'), 'lifecycle');
        $lifecycle->setOptions($this->object->getLifecycle()->getSelectOptions($this->lng));
        $lifecycle->setValue($this->object->getLifecycle()->getIdentifier());
        $form->addItem($lifecycle);

        //SCALA SECTION
        $scala_form = new ilScalaFormPropertyGUI($this->lng->txt("qpl_qst_xqscala_scala"), "scala");
        $scala_form->setScala($this->object->getScala());
        $scala_form->init("edit");
        $form->addItem($scala_form);

        // questiontext -> feedback
        $question = new ilTextAreaInputGUI($this->lng->txt("feedback"), "question");
        $question->setValue($this->object->getQuestion());
        $question->setRequired(true);
        $question->setRows(10);
        $this->addTinyMCESupport($question);
        $form->addItem($question);

        return $form;
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
                ilUtil::sendSuccess($this->plugin->txt('success_import') . " " . (string) $num_of_questions, true);
            }
        } else {
            ilUtil::sendFailure($this->plugin->txt('error_import_xml_not_loaded'), true);
        }

        $DIC->ctrl()->redirect($this, 'editQuestion');
    }

    /**
     */
    public function addTinyMCESupport(ilTextAreaInputGUI $field)
    {
        if (empty($this->rte_tags)) {
            $this->initTinyMCESupport();
        }
        $field->setUseRte(true);
        $field->setRteTags($this->rte_tags);
        $field->addPlugin("latex");
        $field->addButton("latex");
        $field->addButton("pastelatex");
        $field->setRTESupport($this->object->getId(), "qpl", "xqscala");
    }

    /**
     */
    public function initTinyMCESupport()
    {
        include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
        $this->rte_tags = ilObjAdvancedEditing::_getUsedHTMLTags("xqscala");

        $this->required_tags = array(
            "a",
            "blockquote",
            "br",
            "cite",
            "code",
            "div",
            "em",
            "h1",
            "h2",
            "h3",
            "h4",
            "h5",
            "h6",
            "hr",
            "img",
            "li",
            "ol",
            "p",
            "pre",
            "span",
            "strike",
            "strong",
            "sub",
            "sup",
            "table",
            "caption",
            "thead",
            "th",
            "td",
            "tr",
            "u",
            "ul",
            "i",
            "b",
            "gap"
        );

        if (serialize($this->rte_tags) != serialize(($this->required_tags))) {
            $this->rte_tags = $this->required_tags;
            $obj_advance = new ilObjAdvancedEditing();
            $obj_advance->setUsedHTMLTags($this->rte_tags, "xqscala");
        }
    }
}
