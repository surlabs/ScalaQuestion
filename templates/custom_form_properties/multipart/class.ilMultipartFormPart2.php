<?php

declare(strict_types=1);

/**
 * This file is part of the STACK Question type Plugin for ILIAS
 * Copyright (c) 2023 Laboratorio de Soluciones del Sur, Sociedad Limitada
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
 * Multipart form part object class
 *
 * @author Jesús Copado Mejías <stack@surlabs.es>
 * @version $Id: 7.1$
 *
 */
class ilMultipartFormPart2
{

	/**
	 * Title of the part.
	 * @var string
	 */
	private string $title;

	/**
	 * Array of form properties objects included in this part.
	 * @var array
	 */
	private array $content = [];

	/**
	 * type of the part
	 * @var string
	 */
	private string $type;

	/**
	 * OBJECT CONSTRUCTOR
	 * @param $a_title string the title of this part
	 */
	function __construct(string $a_title, $a_postvar = "")
	{
		$this->setTitle($a_title);
	}

	/**
	 * Add a form property to the end of the list of content
	 * @param $a_form_property
	 */
	public function addFormProperty($a_form_property)
	{
		$this->content[] = $a_form_property;
	}


	/*
	 * GETTERS AND SETTERS
	 */

	/**
	 * @param string $a_title
	 */
	public function setTitle(string $a_title)
	{
		$this->title = $a_title;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
    {
		return $this->title;
	}

	/**
	 * @param array $a_content
	 */
	public function setContent(array $a_content)
	{
		$this->content = $a_content;
	}

	/**
	 * @return array
	 */
	public function getContent(): array
    {
		return $this->content;
	}

	/**
	 * @param string $type
	 */
	public function setType(string $type)
	{
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getType(): string
    {
		return $this->type;
	}

}
