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
class Scala
{
    private $question_id;
    private $scala_id;

    //Scala object from DB as json
    private string $raw_data;

    //Columnas de la escala en formato index => texto
    private array $columns;
    private int $num_columns;

    //Preguntas de la escala en formato index => texto
    private array $items;
    private int $num_items;

    //Puntos de cada celda
    private array $evaluation_scala;

    //Formatos de feedback en formato puntuacion_a_alcanzar_en_porcentaje => texto
    private array $feedback_scala;

    /**
     * Crea el objeto Scala estableciendo sus identificadores
     */
    public function __construct(int $question_id, int $scala_id)
    {
        $this->setQuestionId($question_id);
        $this->setScalaId($scala_id);
    }

    /**
     * Actualiza, establece y devuelve la Scala en formato JSON
     * @return string
     */
    public function toJSON(): string
    {
        $data = [
            'question_id' => $this->getQuestionId(),
            'scala_id' => $this->getScalaId(),
            'columns' => $this->getColumns(),
            'num_columns' => $this->getNumColumns(),
            'items' => $this->getItems(),
            'num_items' => $this->getNumItems(),
            'evaluation_scala' => $this->getEvaluationScala(),
            'feedback_scala' => $this->getFeedbackScala(),
        ];

        $json = json_encode($data);
        $this->setRawData($json);

        return $json;
    }

    /**
     * @param string $json
     * @param        $question_id
     * @return Scala|null
     */
    public static function fromJSON(string $json, $question_id): ?Scala
    {
        $data = json_decode($json, true);

        if (is_array(
                $data
            ) && isset($data['question_id'], $data['scala_id'], $data['columns'], $data['items'], $data['evaluation_scala'])) {
            $scala = new Scala((int) $question_id, (int) $question_id);
            $scala->setRawData($json);
            $scala->setColumns($data['columns']);
            $scala->setNumColumns(count($data['columns']));
            $scala->setItems($data['items']);
            $scala->setNumItems(count($data['items']));
            $scala->setEvaluationScala($data['evaluation_scala']);
            $scala->setFeedbackScala($data['feedback_scala'] ?? []);
            return $scala;
        }

        return null; //Devuelve null si el JSON no se puede convertir en una instancia de Scala
    }

    /**
     * Devuelve un array con las columnas y filas en la posición 0 y rellena el resto de celdas con 0.0
     * @return array
     */
    public function getBlankScala(): array
    {
        $scala = [];

        // Agregar los textos de columnas como la primera fila
        $scala[] = array_merge([''], $this->getColumns());

        // Iterar a través de los elementos y agregar los textos de elementos y los índices de columna
        foreach ($this->getItems() as $itemIndex => $itemText) {
            $row = [$itemText];
            foreach ($this->getColumns() as $columnIndex => $columnText) {
                $row[] = 0.0;
            }
            $scala[] = $row;
        }

        return $scala;
    }

    /**
     * Devuelve un array con las columnas y filas en la posición 0 y rellena el resto de celdas con
     * su valor en Evaluation Scala
     * @return array
     */
    public function getScalaWithPoints(): array
    {
        $scala = [];

        // Agregar los textos de columnas como la primera fila
        $scala[] = array_merge([''], $this->getColumns());

        // Iterar a través de los elementos y agregar los textos de elementos y los índices de columna
        foreach ($this->getItems() as $itemIndex => $itemText) {
            $row = [$itemText];
            foreach ($this->getColumns() as $columnIndex => $columnText) {
                $row[] = $this->getEvaluationScala()[($itemIndex+1)][($columnIndex+1)];
            }
            $scala[] = $row;
        }

        return $scala;
    }

    /**
     * @return mixed
     */
    public function getQuestionId()
    {
        return $this->question_id;
    }

    /**
     * @param mixed $question_id
     */
    public function setQuestionId($question_id): void
    {
        $this->question_id = $question_id;
    }

    /**
     * @return mixed
     */
    public function getScalaId()
    {
        return $this->scala_id;
    }

    /**
     * @param mixed $scala_id
     */
    public function setScalaId($scala_id): void
    {
        $this->scala_id = $scala_id;
    }

    /**
     * @return string
     */
    public function getRawData(): string
    {
        return $this->raw_data;
    }

    /**
     * @param string $raw_data
     */
    public function setRawData(string $raw_data): void
    {
        $this->raw_data = $raw_data;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * @return array
     */
    public function getEvaluationScala(): array
    {
        return $this->evaluation_scala;
    }

    /**
     * @param array $evaluation_scala
     */
    public function setEvaluationScala(array $evaluation_scala): void
    {
        $this->evaluation_scala = $evaluation_scala;
    }

    /**
     * @return array
     */
    public function getFeedbackScala(): array
    {
        return $this->feedback_scala;
    }

    /**
     * @param array $feedback_scala
     */
    public function setFeedbackScala(array $feedback_scala): void
    {
        $this->feedback_scala = $feedback_scala;
    }

    /**
     * @return int
     */
    public function getNumColumns(): int
    {
        return $this->num_columns;
    }

    /**
     * @param int $num_columns
     */
    public function setNumColumns(int $num_columns): void
    {
        $this->num_columns = $num_columns;
    }

    /**
     * @return int
     */
    public function getNumItems(): int
    {
        return $this->num_items;
    }

    /**
     * @param int $num_items
     */
    public function setNumItems(int $num_items): void
    {
        $this->num_items = $num_items;
    }

}