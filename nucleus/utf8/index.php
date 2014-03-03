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
?>

	<p> This program will attempt to convert your Nucleus database tables to UTF-8 format. </p>

	<p> Only Nucleus tables will be converted by this program. Note there are a few Nucleus tables that do not have primary keys and thus cannot be converted by this program. </p>

	<p> <strong style="color: #c00;">This is an experimental program and you should back up your database before continuing.</strong> </p>

	<p> Once you have backed up the database, <a href="update.php">you may proceed</a>. </p>
