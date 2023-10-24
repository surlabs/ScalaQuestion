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
 */
class ilScalaFormPropertyGUI extends ilMultipartFormProperty2GUI
{

    /**
     * @var ilTemplate la template HTML del formulario
     */
    private ilTemplate $template;

    /**
     * @var Scala El objeto escala
     */
    private Scala $scala;

    function __construct($a_title = "", $a_postvar = "")
    {
        parent::__construct($a_title, $a_postvar);

        //Set template for columns
        $template = new ilTemplate(
            './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assScalaQuestion/templates/custom_form_properties/tpl.scala_form_property.html',
            true, true
        );
        $this->setTemplate($template);
    }

    /**
     * Inicializa el formulario con los valores de la Scala actual
     * @return void
     */
    public function init(string $mode)
    {
        switch ($mode) {
            case 'edit':
                //show author view
                $blank_scala = $this->getScala()->getBlankScala();
                $evaluation_scala = $this->getScala()->getEvaluationScala();
                //var_dump($blank_scala, $evaluation_scala);exit;
                break;
            case 'solution':
                break;
            default:
                //show user view
        }
    }

    /**
     * Add a part to the form, setting the position value in the part object
     * and in the parts array of this class.
     * Añadimos las filas como partes de este formulario
     * @param ilMultipartFormPart $part
     */
    public function addPart(ilMultipartFormPart $part)
    {
        parent::addPart($part);
    }

    protected function render(): string
    {
        global $DIC;

        //Creamos la template
        $template = new ilTemplate(
            'Customizing/global/plugins/Modules/TestQuestionPool/Questions/assScalaQuestion/templates/custom_form_properties/tpl.scala_form_property.html',
            true, true
        );

        //añadimos el CSS
        $DIC->globalScreen()->layout()->meta()->addCss(
            'Customizing/global/plugins/Modules/TestQuestionPool/Questions/assScalaQuestion/templates/custom_form_properties/tpl.scala_form_property.css'
        );

        // Iterate over the matrix rows
        $scala = $this->getScala()->getScalaWithPoints();

        for ($row = 0; $row < sizeof($scala); $row++) {
            // Set row block
            $template->setCurrentBlock('scala_rows');

            // Iterate over the matrix columns
            for ($col = 0; $col < sizeof($scala[$row]); $col++) {
                // Set cell block
                $template->setCurrentBlock('scala_cells');

                // Set the content for the cell
                $template->setVariable("CELL_CONTENT", $scala[$row][$col]);
                $template->setVariable("ROW", (string)$row);
                $template->setVariable("COLUMN", (string)$col);

                // Parse the current cell
                $template->parseCurrentBlock();
            }
            $template->setCurrentBlock('scala_rows');
            // Parse the current row
            $template->parseCurrentBlock();
        }

        // Return the final HTML
        return $template->get();
    }


    /*
     * GETTERS AND SETTERS
     */

    /**
     * @return ilTemplate
     */
    public function getTemplate(): ilTemplate
    {
        return $this->template;
    }

    /**
     * @param ilTemplate $template
     */
    public function setTemplate(ilTemplate $template): void
    {
        $this->template = $template;
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
