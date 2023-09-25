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
class assScalaQuestionSurveyImport
{

    private ilassScalaQuestionPlugin $plugin;
    private assScalaQuestion $ilias_question;

    private int $current_question_id;

    /**
     * @param ilassScalaQuestionPlugin $plugin
     * @param assScalaQuestion         $question
     */
    function __construct(ilassScalaQuestionPlugin $plugin, assScalaQuestion $question, int $first_question_id)
    {
        $this->setPlugin($plugin);
        $this->setIliasQuestion($question);
        $this->setCurrentQuestionId($first_question_id);
    }

    /* MAIN METHODS BEGIN */

    /**
     * ### MAIN METHOD OF THIS CLASS ###
     * This method is called from assStackQuestion to import the questions from an MoodleXML file.
     * @param $xml_file
     * @return int
     */
    public function import($xml_file): int
    {
        global $DIC;

        //establecemos un contador de preguntas importadas
        $number_of_questions_created = 0;

        //LIBXML_NO-CDATA Merge CDATA as Textnodes
        $xml = simplexml_load_file($xml_file, null, LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        $number_of_questions_created = 0;
        if (!isset($array['surveyquestions'])) {
            ilUtil::sendFailure($this->getPlugin()->txt('error_import_xml_not_ilias_survey_questions'), true);
        }

        if (!isset($array['surveyquestions']['question'])) {
            ilUtil::sendFailure($this->getPlugin()->txt('error_import_xml_no_questions_in_survey'), true);
        }

        foreach ($array['surveyquestions']['question'] as $matrix) {
            try {
                //$this->clearMediaObjects();
                if (is_array($matrix)) {
                    if (isset($matrix['@attributes']['title']) && isset($matrix['questiontext']['material']['mattext']) && isset($matrix['author'])) {
                        if ($this->matrixToScala($matrix)) {
                            $this->getIliasQuestion()->saveToDb();
                            $number_of_questions_created++;
                        }

                        //Set current question Id to -1 if we have created already one question, to ensure creation of the others
                        if ($number_of_questions_created > 0) {
                            $this->getIliasQuestion()->setId(-1);
                        }
                    }
                }
            } catch (Exception $exception) {
                ilUtil::sendFailure($exception->getMessage(), true);
            }
        }
        return $number_of_questions_created;
    }

    /**
     * @param array $matrix
     * @return bool
     */
    public function matrixToScala(array $matrix): bool
    {
        try {
            //Establecemos valores generales de la pregunta
            $this->getIliasQuestion()->setTitle($matrix['@attributes']['title']);
            //$this->getIliasQuestion()->setComment($matrix['questiontext']['material']['mattext']);
            $this->getIliasQuestion()->setAuthor($matrix['author']);
            $this->getIliasQuestion()->setQuestion($matrix['questiontext']['material']['mattext']);

            $current_columns = [];
            $current_items = [];
            $current_evaluation = [];

            //Establecemos matrixresponses como Scala Columns
            $matrix_response = $matrix['matrix']['responses']['response_single'];
            foreach ($matrix_response as $index => $response_array) {
                if (isset($response_array['material']['mattext'])) {
                    $current_columns[$index] = $response_array['material']['mattext'];
                }
            }
            $this->getIliasQuestion()->getScala()->setColumns($current_columns);
            $this->getIliasQuestion()->getScala()->setNumColumns(sizeof($current_columns));

            //Establecemos matrixrows como Scala Items
            $matrix_row = $matrix['matrix']['matrixrows']['matrixrow'];

            if (isset($matrix_row['@attributes'])) {
                //una fila
                $current_items[0] = $matrix_row['material']['mattext'];
            } else {
                //varias filas
                foreach ($matrix_row as $index => $response_array) {
                    if (isset($response_array['material']['mattext'])) {
                        $current_items[$index] = $response_array['material']['mattext'];
                    }
                }
            }

            $this->getIliasQuestion()->getScala()->setItems($current_items);
            $this->getIliasQuestion()->getScala()->setNumItems(sizeof($current_items));

            //Nos inventamos una escala ya que este dato no viene desde el objeto survey
            //usaremos el id numero de column como puntos por defecto

            //Calculate max points from each row
            $max_points_array = [];

            foreach ($this->getIliasQuestion()->getScala()->getItems() as $index_item => $item_text) {
                foreach ($this->getIliasQuestion()->getScala()->getColumns() as $index_column => $column_text) {
                    //rellenamos con la puntuacion
                    $points = floatval($index_column + 1);
                    $current_evaluation[($index_item + 1)][($index_column + 1)] = $points;

                    //calculate max points
                    if ((float) $max_points_array[$index_item] < (float) $points) {
                        $max_points_array[$index_item] = $points;
                    }
                }
            }
            $this->getIliasQuestion()->getScala()->setEvaluationScala($current_evaluation);
            if ($this->getIliasQuestion()->getScala()->getNumItems() == 0) {
                $this->getIliasQuestion()->getScala()->setNumItems(1);
            }

            //ensure items minimum is 1
            if ((int) $this->getIliasQuestion()->getScala()->getNumItems() == 0) {
                $this->getIliasQuestion()->getScala()->setNumItems(1);
            }

            $this->getIliasQuestion()->setPoints(
                (int) array_sum($max_points_array) / $this->getIliasQuestion()->getScala()->getNumItems()
            );

            $current_to_json = $this->getIliasQuestion()->getScala()->toJSON();
            $this->getIliasQuestion()->getScala()->setRawData($current_to_json);
            return true;
        } catch (Exception $exception) {
            ilUtil::sendFailure($exception->getMessage() . ' in Frage: ' . $this->getIliasQuestion()->getTitle(), true);
            return false;
        }
    }

    /**
     * @return ilassScalaQuestionPlugin
     */
    public function getPlugin(): ilassScalaQuestionPlugin
    {
        return $this->plugin;
    }

    /**
     * @param ilassScalaQuestionPlugin $plugin
     */
    public function setPlugin(ilassScalaQuestionPlugin $plugin): void
    {
        $this->plugin = $plugin;
    }

    /**
     * @return assScalaQuestion
     */
    public function getIliasQuestion(): assScalaQuestion
    {
        return $this->ilias_question;
    }

    /**
     * @param assScalaQuestion $ilias_question
     */
    public function setIliasQuestion(assScalaQuestion $ilias_question): void
    {
        $this->ilias_question = $ilias_question;
    }

    /**
     * @return int
     */
    public function getCurrentQuestionId(): int
    {
        return $this->current_question_id;
    }

    /**
     * @param int $current_question_id
     */
    public function setCurrentQuestionId(int $current_question_id): void
    {
        $this->current_question_id = $current_question_id;
    }

}