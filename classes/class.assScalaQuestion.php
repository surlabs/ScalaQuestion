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
        $scala = new Scala($this->getId(), $this->getId());
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

        // get the submitted solution
        $solution = $this->getSolutionSubmit();
        $entered_values = 0;

        // save the submitted values avoiding race conditions
        $this->getProcessLocker()->executeUserSolutionUpdateLockOperation(
            function () use (&$entered_values, $solution, $active_id, $pass, $authorized) {
                $entered_values = isset($solution['value1']) || isset($solution['value2']);

                if ($authorized) {
                    // a new authorized solution will delete the old one and the intermediate
                    $this->removeExistingSolutions($active_id, $pass);
                } elseif ($entered_values) {
                    // a new intermediate solution will only delete a previous one
                    $this->removeIntermediateSolution($active_id, $pass);
                }

                if ($entered_values) {
                    $this->saveCurrentSolution(
                        $active_id, $pass, $solution['value1'], $solution['value2'], $authorized, time()
                    );
                }
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
        if ($returndetails) {
            throw new ilTestException('return details not implemented for ' . __METHOD__);
        }

        return 1.0;
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
        return -1;
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
    public function getSolutionSubmit(): array
    {
        $value1 = trim(stripslashes($_POST['question' . $this->getId() . 'value1']));
        $value2 = trim(stripslashes($_POST['question' . $this->getId() . 'value2']));

        return array(
            'value1' => empty($value1) ? null : $value1,
            'value2' => empty($value2) ? null : (float) $value2
        );
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

}