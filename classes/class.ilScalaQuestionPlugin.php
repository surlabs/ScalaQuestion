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
 * @author Jesus Copado <jcopado@surlabs.es>
 * @version $Id: 0.0$
 * @ingroup    ModulesTestQuestionPool
 *
 */

class ilScalaQuestionPlugin extends ilQuestionsPlugin
{

    /**
     * @return string The question type name for the system
     */
    public function getQuestionType(): string
    {
        return 'assScalaQuestion';
    }

    /**
     * @return string The question type name for the user
     */
    public function getQuestionTypeTranslation(): string
    {
        return $this->txt($this->getQuestionType());
    }

    /**
     * @return string The plugin name for the system
     */
    public function getPluginName(): string
    {
        return 'assScalaQuestion';
    }
}
