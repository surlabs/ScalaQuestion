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
 * @author     Jesus Copado <jcopado@surlabs.es>
 * @version    $Id: 0.0$
 * @ingroup    ModulesTestQuestionPool
 */
class assScalaQuestion extends assQuestion implements ilObjQuestionScoringAdjustable
{
    /**
     * @var Scala The Scala object of this Scala Question
     */
    private Scala $scala;

    /**
     * @var float Necesitamos hacer este atributo de clase para guardar el valor de calculated reached
     *            points for preview para ser usado en get specific feedback (en preview)
     */
    private float $reached_points_for_preview = 0.0;

    /**
     * Constructor
     *
     * The constructor takes possible arguments and creates an instance of the question object.
     *
     * @param string $title    A title string to describe the question
     * @param string $comment  A comment string to describe the question
     * @param string $author   A string containing the name of the questions author
     * @param int    $owner    A numerical ID to identify the owner/creator
     * @param string $question Question text
     * @access public
     *
     */
    function __construct(
        string $title = '',
        string $comment = '',
        string $author = '',
        int $owner = -1,
        string $question = ''
    ) {
        parent::__construct($title, $comment, $author, $owner, $question);

        //set Scala object
        $scala = new Scala($this->getId(), $this->getId());
        $scala->setFeedbackScala($this->parseFeedback($question));

        $this->setScala($scala);
    }

    /**
     * Devuelve true si la pregunta está lista para ser usada
     *
     * @return boolean True, if the matching question is complete for use, otherwise false
     */
    public function isComplete(): bool
    {
        if (strlen($this->title)
            && $this->author
            && $this->question
            && $this->getMaximumPoints() > 0
        ) {
            return true;
        }
        return false;
    }

    /**
     * Borra la pregunta de la BD
     * @param int $question_id
     */
    public function delete($question_id): void
    {
        //delete general question data
        parent::delete($question_id);

        //delete scala specific question data
        global $DIC;
        $db = $DIC->database();

        $db->manipulateF(
            "DELETE FROM " . $this->getAdditionalTableName() . " WHERE question_id = %s",
            array("integer"),
            array($this->getId())
        );
    }

    /**
     * Loads a question object from a database
     * This has to be done here (assQuestion does not load the basic data)!
     *
     * @param integer $question_id A unique key which defines the question in the database
     * @see assQuestion::loadFromDb()
     */
    public function loadFromDb($question_id)
    {
        global $DIC;
        $db = $DIC->database();

        // load the basic question data
        $result = $db->query(
            "SELECT qpl_questions.* FROM qpl_questions WHERE question_id = "
            . $db->quote($question_id, 'integer')
        );

        if ($result->numRows() > 0) {
            $data = $db->fetchAssoc($result);
            $this->setId($question_id);
            $this->setObjId($data['obj_fi']);
            $this->setOriginalId($data['original_id']);
            $this->setOwner($data['owner']);
            $this->setTitle((string) $data['title']);
            $this->setAuthor($data['author']);
            $this->setPoints($data['points']);
            $this->setComment((string) $data['description']);

            $this->setQuestion(ilRTE::_replaceMediaObjectImageSrc((string) $data['question_text'], 1));
            $this->setEstimatedWorkingTime(
                substr($data['working_time'], 0, 2), substr($data['working_time'], 3, 2),
                substr($data['working_time'], 6, 2)
            );

            //load scala specific data
            $res = $db->queryF(
                "SELECT * FROM " . $this->getAdditionalTableName() . " WHERE question_id = %s",
                array('integer'),
                array($question_id)
            );

            if ($db->numRows($res) > 0) {
                $row = $db->fetchAssoc($res);
                $this->setScala(Scala::fromJSON(json_decode($row['scala']), $question_id));
            }

            try {
                $this->setAdditionalContentEditingMode($data['add_cont_edit_mode']);
            } catch (ilTestQuestionPoolException $e) {
            }
        }

        // loads additional stuff like suggested solutions
        parent::loadFromDb($question_id);
    }

    /**
     * Saves question information into the DB
     * @param $original_id
     * @param $a_save_parts
     * @return void
     */
    function saveToDb($original_id = "", $a_save_parts = true)
    {
        if ($this->getTitle() != "" and $this->getAuthor() != "" and $this->getQuestion() != "") {
            // save the basic data (implemented in parent)
            // a new question is created if the id is -1
            // afterwards the new id is set
            $this->saveQuestionDataToDb($original_id);
            $this->saveAdditionalQuestionDataToDb();
            // save data to DB Title, Name, Question

            // save stuff like suggested solutions
            // update the question time stamp and completion status
            parent::saveToDb();
        }
    }

    /**
     * Como esta clase implementa ilObjQuestionScoringAdjustable
     * debemos manejar las llamadas extra a la base de datos a través de este método
     * @return mixed|void
     */
    public function saveAdditionalQuestionDataToDb()
    {
        global $DIC;
        $db = $DIC->database();

        // save additional data
        $db->manipulateF(
            "DELETE FROM " . $this->getAdditionalTableName() . " WHERE question_id = %s",
            array("integer"),
            array($this->getId())
        );

        // Convert object to JSON
        $json = json_encode($this->getScala()->getRawData());

        $db->insert($this->getAdditionalTableName(), array(
                'question_id' => array('integer', $this->getId()),
                'scala' => array('text', $json)
            )
        );
    }

    /**
     * Guarda la respuesta del usuario a la pregunta en un test
     *
     * @param integer      $active_id  Active id of the user
     * @param integer|null $pass       Test pass
     * @param bool         $authorized The solution is authorized
     *
     * @return bool $status
     */
    public function saveWorkingData($active_id, $pass = null, $authorized = true): bool
    {
        global $DIC;

        if (is_null($pass)) {
            $pass = ilObjTest::_getPass($active_id);
        }

        // get the submitted solution, force post not sending active id and pass
        $user_post_solution = $this->getSolutionSubmit();
        $json = json_encode($user_post_solution);

        $entered_values = 0;

        //Save user test solution
        $this->getProcessLocker()->executeUserSolutionUpdateLockOperation(
            function () use (&$entered_values, $active_id, $pass, $authorized, $json) {
                //Remove previous solution
                $this->removeCurrentSolution($active_id, $pass, $authorized);
                //Add current solution
                $entered_values = $this->saveCurrentSolution($active_id, $pass, $json, $this->getScala()->toJSON());
            }
        );

        // Log whether the user entered values
        if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
            assQuestion::logAction(
                $this->lng->txtlng(
                    'assessment',
                    $entered_values ? 'log_user_entered_values' : 'log_user_not_entered_values',
                    ilObjAssessmentFolder::_getLogLanguage()
                ),
                $active_id,
                $this->getId()
            );
        }

        // submitted solution is valid
        return true;
    }

    /**
     * Calcula los puntos para preview y test
     * @param $participant_solution
     * @return float
     */
    public function calculatePoints($participant_solution): float
    {
        $scala = (array) $this->getScala()->getScalaWithPoints();
        $points = [];

        for ($row = 1; $row < sizeof($scala); $row++) {
            if (isset($participant_solution[$row - 1])
                && isset($scala[$row][$participant_solution[$row - 1]])) {
                $points[] = $scala[$row][$participant_solution[$row - 1]];
            }
        }

        $sum = array_sum($points);

        $reached_points = $sum / $this->getScala()->getNumItems();
        $this->reached_points_for_preview = $reached_points;

        return (float) $reached_points;
    }

    /**
     * Returns the points, a learner has reached answering the question.
     * The points are calculated from the given answers.
     *
     * @access public
     * @param integer $active_id
     * @param null    $pass
     * @param bool    $authorizedSolution
     * @param boolean $returndetails (deprecated !!)
     * @return float
     * @throws ilTestException
     */
    public function calculateReachedPoints(
        $active_id,
        $pass = null,
        $authorizedSolution = true,
        $returndetails = false
    ): float {
        //force db as we are in test
        $solution_from_db = $this->getSolutionSubmit($active_id, $pass)[0];

        $participant_solution = (array)json_decode($solution_from_db["value1"]);

        $reached_points = $this->calculatePoints($participant_solution);

        return $this->ensureNonNegativePoints($reached_points);
    }

    /**
     * Calcula los puntos para el modo preview
     * @param ilAssQuestionPreviewSession $previewSession
     * @return int|mixed
     */
    public function calculateReachedPointsFromPreviewSession(ilAssQuestionPreviewSession $previewSession)
    {
        $participant_solution = (array)$previewSession->getParticipantsSolution();

        $reached_points = $this->calculatePoints($participant_solution);

        return $this->ensureNonNegativePoints($reached_points);
    }

    /**
     * Returns the question type of the question
     *
     * @return string The question type of the question
     * @access public
     */
    public function getQuestionType(): string
    {
        return 'assScalaQuestion';
    }

    /**
     * Returns the name of the additional question data table in the database
     *
     * @return string The additional table name
     * @access public
     */
    public function getAdditionalTableName(): string
    {
        return 'xqscala_question';
    }

    /**
     * Duplicates a question
     * This is used for copying a question to a test
     *
     * @param bool         $for_test
     * @param string       $title
     * @param string       $author
     * @param string       $owner
     * @param integer|null $testObjId
     *
     * @return integer id of the clone.
     */
    public function duplicate($for_test = true, $title = "", $author = "", $owner = "", $testObjId = null): int
    {
        if ($this->id <= 0) {
            // The question has not been saved. It cannot be duplicated
            return 0;
        }
        // duplicate the question in database
        $this_id = $this->getId();
        $thisObjId = $this->getObjId();

        $clone = $this;
        include_once("./Modules/TestQuestionPool/classes/class.assQuestion.php");
        $original_id = assQuestion::_getOriginalId($this->id);
        $clone->id = -1;

        if ((int) $testObjId > 0) {
            $clone->setObjId($testObjId);
        }

        if ($title) {
            $clone->setTitle($title);
        }

        if ($author) {
            $clone->setAuthor($author);
        }
        if ($owner) {
            $clone->setOwner($owner);
        }
        if ($for_test) {
            $clone->saveToDb($original_id);
        } else {
            $clone->saveToDb();
        }

        // copy question page content
        $clone->copyPageOfQuestion($this_id);

        // copy XHTML media objects
        $clone->copyXHTMLMediaObjectsOfQuestion($this_id);

        $clone->onDuplicate($thisObjId, $this_id, $clone->getObjId(), $clone->getId());

        return (int) $clone->id;
    }

    /**
     * Copies an assScalaQuestion object into the Clipboard
     *
     * @param integer $target_questionpool_id
     * @param string $title
     *
     * @return void|integer Id of the clone or nothing.
     */
    function copyObject(int $target_questionpool_id, string $title = "")
    {
        if ($this->id <= 0) {
            // The question has not been saved. It cannot be duplicated
            return;
        }
        // duplicate the question in database
        $clone = $this;
        include_once("./Modules/TestQuestionPool/classes/class.assQuestion.php");

        $original_id = assQuestion::_getOriginalId($this->id);
        $clone->id = -1;
        $source_questionpool_id = $this->getObjId();
        $clone->setObjId($target_questionpool_id);
        if ($title) {
            $clone->setTitle($title);
        }
        $clone->saveToDb("", TRUE);
        // copy question page content
        $clone->copyPageOfQuestion($original_id);
        // copy XHTML media objects
        $clone->copyXHTMLMediaObjectsOfQuestion($original_id);

        $clone->onCopy($source_questionpool_id, $original_id, $clone->getObjId(), $clone->getId());

        return $clone->id;
    }

    /* Other overwritten methods */

    /**
     * Get a submitted solution array from $_POST
     *
     * In general this may return any type that can be stored in a php session
     * The return value is used by:
     *        savePreviewData()
     *        saveWorkingData()
     *        calculateReachedPointsForSolution()
     *
     * @return    array    ('value1' => string|null, 'value2' => float|null)
     */
    public function getSolutionSubmit($active_id = null, $pass = null): array
    {
        if ($active_id != null and $pass != null and $active_id != 0) {
            return $this->getUserSolutionPreferingIntermediate($active_id, $pass);
        }

        $user_solution = [];
        foreach ($this->getScala()->getItems() as $item_index => $item_text) {
            if (isset($_POST["scala_qid_" . $this->getId() . "_row_" . ($item_index + 1)])) {
                $chosen_column = explode("_", $_POST["scala_qid_" . $this->getId() . "_row_" . ($item_index + 1)]);
                $user_solution[$item_index] = $chosen_column[1];
            }
        }

        return $user_solution;
    }

    /**
     * Comprueba el texto en busca de placeholders de feedback y devuelve el estado actual del feedback de la pregunta
     * @param string $text_to_parse
     * @return array
     */
    function parseFeedback(string $text_to_parse): array
    {
        $pattern = '/\[\[feedback:(\d+,\d{1,5})\]\](.*?)\[\[\/feedback\]\]/s';
        preg_match_all($pattern, $text_to_parse, $matches, PREG_SET_ORDER);

        $result = [];
        foreach ($matches as $match) {
            $key = $match[1];
            $value = $match[2];
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Devuelve el texto de la pregunta sin placeholders de feedback
     * @param $inputString
     * @return array|string|string[]|null
     */
    function parseText($inputString)
    {
        $pattern = '/\[\[feedback:(\d+,\d{1,5})\]\](.*?)\[\[\/feedback\]\]/s';
        return preg_replace($pattern, '', $inputString);
    }

    /**
     * @return Scala
     */
    public function getScala(): Scala
    {
        return $this->scala;
    }

    /**
     * @param Scala $scala
     */
    public function setScala(Scala $scala): void
    {
        $this->scala = $scala;
    }

    /**
     * @return float
     */
    public function getReachedPointsForPreview(): float
    {
        return $this->reached_points_for_preview;
    }

    /**
     * @param float $reached_points_for_preview
     */
    public function setReachedPointsForPreview(float $reached_points_for_preview): void
    {
        $this->reached_points_for_preview = $reached_points_for_preview;
    }

    public function setExportDetailsXLS($worksheet, $startrow, $active_id, $pass)
    {
        parent::setExportDetailsXLS($worksheet, $startrow, $active_id, $pass);

        $solutions = $this->getSolutionValues($active_id, $pass);

        $i = 1;
        foreach ($solutions as $solution) {

            if (isset($solution["value1"]) && $solution["value1"] != "") {
                $user_response = json_decode($solution["value1"], true);
            }

            foreach ($this->getScala()->getItems() as $order => $question){
                if(isset($user_response[$order])){
                    $worksheet->setCell($startrow + $i, 1, $question);
                    $worksheet->setCell($startrow + $i, 2, $this->getScala()->getColumns()[(int)$user_response[$order]]);
                }
                $i++;
            }

        }

        return $startrow + $i + 1;
    }

    /**
     * Returns a QTI xml representation of the question and sets the internal
     * domxml variable with the DOM XML representation of the QTI xml representation
     * @param bool $a_include_header
     * @param bool $a_include_binary
     * @param bool $a_shuffle
     * @param bool $test_output
     * @param bool $force_image_references
     * @return string The QTI xml representation of the question
     */
    public function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false): string
    {
        $export = new assScalaQuestionExport($this);

        return $export->toXML($a_include_header, $a_include_binary, $a_shuffle, $test_output, $force_image_references);
    }

}