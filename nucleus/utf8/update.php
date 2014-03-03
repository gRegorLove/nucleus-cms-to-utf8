<?php
/*
 * Nucleus: PHP/MySQL Weblog CMS (http://nucleuscms.org/)
 * Copyright (C) 2002-2012 The Nucleus Group
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * (see nucleus/documentation/index.html#license for more info)
 *
 * This script will convert existing Nucleus tables to UTF-8 collation
 *
 */

/**
 * @license http://nucleuscms.org/license.txt GNU General Public License
 * @copyright Copyright (C) 2002-2012 The Nucleus Group
 */

	set_time_limit(0);
	error_reporting(E_ALL);

	/**
	 * Due to the way the sql_ wrapper functions are set up, could not escape using
	 * this global variable, despite wanting to encapsulate everything in the ConvertDB class
	 */
	global $MYSQL_CONN;

	define('LF', "\n");

	include('classes/ConvertDB.php');
	include('classes/Encoding.php');

	try
	{
		$ConvertDB = new ConvertDB();
		$summary = $ConvertDB->execute();
		echo $summary;
	}
	catch ( Exception $e )
	{
		echo sprintf('<p> %s </p.', $e->getMessage());
	}
