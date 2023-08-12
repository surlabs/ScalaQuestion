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
class ilAssScalaQuestionFeedback extends ilAssMultiOptionQuestionFeedback
{
    /**
     * returns the answer options mapped by answer index
     * (overwrites parent method from ilAssMultiOptionQuestionFeedback)
     *
     * @return array $answerOptionsByAnswerIndex
     */
    public function getAnswerOptionsByAnswerIndex()
    {
    }
}
