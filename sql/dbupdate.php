<#1>
<?php
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

global $DIC;
$res = $DIC->database()->queryF(/** @lang text */ "SELECT * FROM qpl_qst_type WHERE type_tag = %s", array('text'),
    array('assScalaQuestion'));

if ($res->numRows() == 0) {
    $res = $DIC->database()->query(/** @lang text */ "SELECT MAX(question_type_id) maxid FROM qpl_qst_type");
    $data = $DIC->database()->fetchAssoc($res);
    $max = $data["maxid"] + 1;

    $affectedRows = $DIC->database()->manipulateF(
    /** @lang text */ "INSERT INTO qpl_qst_type (question_type_id, type_tag, plugin) VALUES (%s, %s, %s)",
        array("integer", "text", "integer"),
        array(
            $max,
            'assScalaQuestion',
            1
        )
    );
}
?>
<#2>
<?php
/*
 * Stores each scala as static json
 */
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('xqscala_question')) {
    $fields = array(
        'question_id' => array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ),
        'scala' => array(
            'type' => 'clob',
            'notnull' => true
        ),
    );
    $db->createTable('xqscala_question', $fields);
    $db->addPrimaryKey('xqscala_question', array('question_id'));
}
?>
<#3>
<?php
/*
 * Create Index
 */
global $DIC;
$db = $DIC->database();
if ($db->tableExists('xqscala_question')) {
    if (!$db->indexExistsByFields('xqscala_question', array('question_id', 'scala'))) {
        $db->addIndex('xqscala_question', array('question_id', 'scala'), 'i1', FALSE);
    }
}
?>
