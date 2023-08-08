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
 * Multipart form property GUI class
 *
 * @author Jesús Copado Mejías <stack@surlabs.es>
 * @version $Id: 7.1$
 *
 */
class ilMultipartFormPropertyGUI extends ilFormPropertyGUI
{
	/**
	 * This array Includes all ilMultipartFormPart of this Form
	 * @var array
	 */
	private array $parts = [];

	/**
	 * If true show property name
	 * @var bool
	 */
	private bool $show_title;

	/**
	 * Is the maximal width of the object
	 * @var int
	 */
	private int $container_width;

	/**
	 * Includes the width for the part title, the part content and the footer
	 * This is for the bootstrap attribute col.
	 * @var array
	 */
	private array $width_division = [];


	function __construct($a_title = "", $a_postvar = "", $a_container_width = 12, $a_show_title = TRUE)
	{
		parent::__construct($a_title, $a_postvar);

		//Maximum width of this object in boostrap columns
		$this->setContainerWidth($a_container_width);

		//Show title of the property or not
		$this->setShowTitle($a_show_title);

		//Depending on showing the title the width of each part will be different
		$this->determineWidthDivision();
	}

    /**
	 * Add a part to the form, setting the position value in the part object
	 * and in the parts array of this class.
	 * @param ilMultipartFormPart $part
	 */
	public function addPart(ilMultipartFormPart $part)
	{
		$this->parts[] = $part;
	}

	/**
	 * Insert property html
	 */
	function insert(&$a_tpl, $a_content_width = "")
	{
		$a_tpl->setCurrentBlock("prop_generic");
		$a_tpl->setVariable("PROP_GENERIC", $this->render());
		if ($a_content_width) {
			$a_tpl->setVariable("CONTENT_WIDTH", $a_content_width);
		}
		$a_tpl->parseCurrentBlock();
	}

	/**
	 * Gets a standard width division for different parts
	 */
	public function determineWidthDivision()
	{
		if ($this->getShowTitle()) {
			$width_division = array(
				'title' => (int)floor($this->getContainerWidth() * 0.3),
				'content' => (int)floor($this->getContainerWidth() * 0.6),
				'footer' => (int)floor($this->getContainerWidth() * 0.1)
			);
		} else {
			$width_division = array(
				'title' => (int)floor($this->getContainerWidth() * 0.1),
				'content' => (int)floor($this->getContainerWidth() * 0.85),
				'footer' => (int)floor($this->getContainerWidth() * 0.1)
			);
		}

		$this->setWidthDivision($width_division);
	}

	/*
	 * GETTERS AND SETTERS
	 */

	/**
	 * @param array $parts
	 */
	public function setParts(array $parts)
	{
		$this->parts = $parts;
	}

	/**
	 * @return array
	 */
	public function getParts(): array
    {
		return $this->parts;
	}

	/**
	 * @param boolean $show_title
	 */
	public function setShowTitle(bool $show_title)
	{
		$this->show_title = $show_title;
	}

	/**
	 * @return boolean
	 */
	public function getShowTitle(): bool
    {
		return $this->show_title;
	}

	/**
	 * @param int $container_width
	 */
	public function setContainerWidth(int $container_width)
	{
		$this->container_width = $container_width;
	}

	/**
	 * @return int
	 */
	public function getContainerWidth(): int
    {
		return $this->container_width;
	}

	/**
	 * @param array $width_division
	 */
	public function setWidthDivision(array $width_division)
	{
		$this->width_division = $width_division;
	}

	/**
	 * @param $parameter
	 * @return mixed
	 * @throws Exception
	 */
	public function getWidthDivision($parameter)
	{
		if (isset($this->width_division[$parameter])) {
			return $this->width_division[$parameter];
		} else {
			throw new Exception('Parameter %s not valid for division', $parameter);
		}
	}



}