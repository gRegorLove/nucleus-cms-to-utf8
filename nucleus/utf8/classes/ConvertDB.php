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

class ConvertDB
{
	/**
	 * The path to config.php
	 * @var string
	 * @access private
	 */
	private $config_path;


	/**
	 * The database connection resource
	 * @var resource
	 * @access private
	 */
	private $db_connection;


	/**
	 * The database information_schmea connection resource
	 * @var resource
	 * @access private
	 */
	private $schema_connection;


	/**
	 * The MySQL hostname
	 * @var string
	 * @access private
	 */
	private $mysql_hostname;


	/**
	 * The MySQL username
	 * @var string
	 * @access private
	 */
	private $mysql_username;


	/**
	 * The MySQL password
	 * @var string
	 * @access private
	 */
	private $mysql_password;


	/**
	 * The MySQL database
	 * @var string
	 * @access private
	 */
	private $mysql_database;


	/**
	 * The Nucleus table prefix
	 * @var string
	 * @access private
	 */
	private $mysql_prefix;


	/**
	 * The MySQL handler Nucleus uses
	 * @var string
	 * @access private
	 */
	private $mysql_handler;


	/**
	 * The MySQL major version number
	 * @var string
	 * @access private
	 */
	private $mysql_version;


	/**
	 * The path to the nucleus/ directory
	 * @var string
	 * @access private
	 */
	private $dir_nucleus;


	/**
	 * The path to the nucleus/libs/ directory
	 * @var string
	 * @access private
	 */
	private $dir_libs;


	/**
	 * Array of table names that will not be converted.
	 * Some Nucleus tables do not have a primary key; this script cannot (fully) convert them.
	 * This array is an array of strings
	 * @var array
	 * @access private
	 */
	private $skip_tables = array();


	/**
	 * Array of table names that will be converted.
	 * This array is an array of objects with the necessary information to convert each table.
	 * @var array
	 * @access private
	 */
	private $convert_tables = array();


	/**
	 * The character set we are converting to
	 * @var string
	 * @access private
	 */
	private $new_character_set = 'utf8';


	/**
	 * Array of MySQL character types to be converted
	 * @var array
	 * @access private
	 */
	private $char_datatypes = array(
		'CHAR', 
		'VARCHAR', 
		'TINYTEXT', 
		'TEXT', 
		'MEDIUMTEXT', 
		'LONGTEXT', 
		'ENUM'
	);


	/**
	 * Array of collation character sets
	 * @var array
	 * @access private
	 */
	private $collation_character_sets = array();


	/**
	 * This method constructs the ConvertDB object
	 * @access public
	 */
	public function __construct($config_path = '../../config.php')
	{
		$this->config_path = $config_path;
		$this->init();
	} # end method __construct()


	/**
	 * This method handles reading the Nucleus config file
	 * @param array 
	 * @access public
	 * @return 
	 */
	public function init()
	{
		# attempt to open the config file
		$handle = @fopen($this->config_path, 'r');

		# if: file handle opened
		if ( $handle )
		{
			$pattern = '/("[^"]*)"|\'([^\']*)\'/';

			$i = 0;
			$var_count = 6;

			# loop: each line of the file
			while ( ($buffer = fgets($handle)) !== FALSE )
			{

				# if: MySQL hostname on this line
				if ( strpos($buffer, '$MYSQL_HOST') !== FALSE )
				{
					preg_match($pattern, $buffer, $matches);
					$this->mysql_hostname = ( isset($matches[2]) ) ? $matches[2] : NULL;
					$i++;
				} # end if

				# if: MySQL username on this line
				if ( strpos($buffer, '$MYSQL_USER') !== FALSE )
				{
					preg_match($pattern, $buffer, $matches);
					$this->mysql_username = ( isset($matches[2]) ) ? $matches[2] : NULL;
					$i++;
				} # end if

				# if: MySQL password on this line
				if ( strpos($buffer, '$MYSQL_PASSWORD') !== FALSE )
				{
					preg_match($pattern, $buffer, $matches);
					$this->mysql_password = ( isset($matches[2]) ) ? $matches[2] : NULL;
					$i++;
				} # end if

				# if: MySQL database name on this line
				if ( strpos($buffer, '$MYSQL_DATABASE') !== FALSE )
				{
					preg_match($pattern, $buffer, $matches);
					$this->mysql_database = ( isset($matches[2]) ) ? $matches[2] : NULL;
					$i++;
				} # end if

				# if: MySQL prefix name on this line
				if ( strpos($buffer, '$MYSQL_PREFIX') !== FALSE )
				{
					preg_match($pattern, $buffer, $matches);
					$this->mysql_prefix = ( isset($matches[2]) ) ? $matches[2] : NULL;
					$i++;
				} # end if

				# if: Nucleus directory on this line
				if ( strpos($buffer, '$DIR_NUCLEUS') !== FALSE )
				{
					preg_match($pattern, $buffer, $matches);
					$this->dir_nucleus = ( isset($matches[2]) ) ? $matches[2] : NULL;
					$i++;
				} # end if

				if ( $i == $var_count )
				{
					break;
				}

			} # end loop

			fclose($handle);

			# if: no prefix specified; use default nucleus_
			if ( empty($this->mysql_prefix) )
			{
				$this->mysql_prefix = 'nucleus_';
			} # end if

			# if: one or more required variables was not found
			if ( empty($this->mysql_hostname) || 
					empty($this->mysql_username) || 
					empty($this->mysql_password) || 
					empty($this->mysql_database) || 
					empty($this->dir_nucleus) )
			{
				throw new Exception('Unable to read the necessary variables from config.php');
			} # end if

		}
		# else: could not find/open config.php
		else
		{
			throw new Exception('Unable to read config.php');
		} # end if

		$this->dir_libs = $this->dir_nucleus . 'libs/';

		# include mysqli wrappers
		require_once($this->dir_libs . 'mysql.php');

		$this->mysql_handler = ( empty($MYSQL_HANDLER[0]) ) ? 'mysql' : $MYSQL_HANDLER[0];

		# include the sql_ wrappers
		require_once($this->dir_libs . 'sql/' . $this->mysql_handler . '.php');

		global $MYSQL_CONN;

		# connect to database
		$MYSQL_CONN = $this->db_connection = sql_connect_args(
			$this->mysql_hostname, 
			$this->mysql_username, 
			$this->mysql_password, 
			NULL, 
			TRUE
		);

		sql_select_db($this->mysql_database, $this->db_connection);
		sql_set_charset('utf8');

		# query the GRANTS for the current db user
		$query = 'SHOW GRANTS';
		$result = sql_query($query, $this->db_connection);

		# by default, we assume the user cannot alter tables
		$can_alter = FALSE;

		# loop: check each row to see if this mysql user has ALTER permissions
		while ( ($row = sql_fetch_array($result)) && ($can_alter === FALSE) )
		{
			$can_alter = ( stripos($row[0], 'alter') === FALSE ) ? FALSE : TRUE;
		} # end loop

		# if: user cannot ALTER the tables; halt the process
		if ( !$can_alter )
		{
			throw new Exception('The database user does not have permissions to alter tables.');
		} # end if

		# find out the version of MySQL
		$result = sql_query('SELECT VERSION()', $this->db_connection);
		$version = sql_result($result, 0, 0);
		$this->mysql_version = substr($version, 0, strpos($version, '.'));

	} # end method init()


	/**
	 * This method handles converting the Nucleus tables to UTF-8 format
	 * @access public
	 */
	public function execute()
	{

		# if: < MySQL 5
		if ( $this->mysql_version < 5 )
		{
			$this->parse_tables_mysql4();
		}
		# else: MySQL 5
		else
		{
			$this->parse_tables_mysql5();
		} # end if

		return $this->convert();
	} # end method execute()


	/**
	 * This method handles the conversion process for MySQL 4
	 * @access private
	 */
	private function parse_tables_mysql4()
	{
		# query the TABLE STATUS to get the tables collations
		$query = "SHOW TABLE STATUS LIKE '{$this->mysql_prefix}%'";
		$result = sql_query($query, $this->db_connection);

		$number_tables = sql_num_rows($result);

		# if: no results returned
		if ( $number_tables == 0 )
		{
			throw new Exception('No Nucleus tables were found that need to be converted.');
		}
		# else: parse the results
		else
		{

			# loop: each row returned
			while ( $row = sql_fetch_object($result) )
			{
				$table_name = $row->Name;
				$collation = $row->Collation;
				$character_set = $this->lookup_character_set($collation);

				# if: this table's character set does not match the new character set; add it to the list of tables to be converted
				if ( $character_set != $this->new_character_set )
				{
					$sql_type_conditions = implode("%' OR `type` LIKE '", $this->char_datatypes);

					$query = "SHOW COLUMNS FROM `{$table_name}` WHERE `type` LIKE '{$sql_type_conditions}%'";
					$col_result = sql_query($query);

					$query = "SHOW KEYS FROM `{$table_name}` WHERE Key_name = 'PRIMARY'";
					$pk_result = sql_query($query);

					# if: no primary key, or no character columns to update; skip this table
					if ( sql_num_rows($pk_result) == 0 || sql_num_rows($col_result) == 0 )
					{
						$this->skip_tables[] = $table_name;
					}
					# else: add this table
					else
					{
						$temp = new stdClass();
						$temp->character_set_name = $character_set;
						$temp->table_name = $table_name;
						$temp->columns = array();
						$temp->primary_key = array();

						while ( $col_row = sql_fetch_object($col_result) )
						{
							$temp->columns[] = $col_row->Field;
						}

						while ( $pk_row = sql_fetch_object($pk_result) )
						{
							$temp->primary_key[] = $pk_row->Column_name;
						}

						$this->convert_tables[] = $temp;
					} # end if

				} # end if: table's character set does not match

			} # end loop: each row returned

		} # end if: parse the table collations

	} # end method parse_tables_mysql4()


	/**
	 * This method handles the conversion process for MySQL 5
	 * @access private
	 */
	private function parse_tables_mysql5()
	{
		# connect to database
		$this->schema_connection = sql_connect_args(
			$this->mysql_hostname, 
			$this->mysql_username, 
			$this->mysql_password, 
			NULL, 
			TRUE
		);

		sql_select_db('information_schema', $this->schema_connection);

		# get all the tables beginning with the prefix
		$query = <<< END
SELECT 
	`t`.`table_name`, 
	`ccsa`.`character_set_name`
FROM 
	`information_schema`.`TABLES` AS `t`, 
	`information_schema`.`COLLATION_CHARACTER_SET_APPLICABILITY` AS `ccsa`
WHERE 
	`ccsa`.`collation_name` = `t`.`table_collation`
	AND `t`.`table_schema` = '{$this->mysql_database}'
	#AND `t`.`table_name` LIKE '{$this->mysql_prefix}%'
	AND `t`.`table_name` = 'utf8_nucleus_item'
	#AND `ccsa`.`character_set_name` <> '{$this->new_character_set}'
END;
		$result = sql_query($query, $this->schema_connection);
		$number_tables = sql_num_rows($result);

		# if: no results returned
		if ( $number_tables == 0 )
		{
			throw new Exception('No Nucleus tables were found that need to be converted.');
		}
		# else: parse the results
		else
		{

			# loop: each row returned
			while ( $row = sql_fetch_object($result) )
			{
				$table_name = $row->table_name;

				$sql_char_datatypes = sprintf("'%s'", implode("', '", $this->char_datatypes));
				$query = <<< END
SELECT 
	`column_name`, 
	`data_type`
FROM 
	`information_schema`.`columns`
WHERE
	`table_schema` = '{$this->mysql_database}'
	AND `table_name` = '{$table_name}'
	AND `data_type` IN ({$sql_char_datatypes})
END;
				$col_result = sql_query($query, $this->schema_connection);

				$query = <<< END
SELECT
	`constraint_name`,
	`column_name`
FROM 
	`information_schema`.`key_column_usage`
WHERE
	`constraint_name` = 'Primary'
	AND `table_schema` = '{$this->mysql_database}'
	AND `table_name` = '{$table_name}'
END;
				$pk_result = sql_query($query, $this->schema_connection);

				# if: no primary key, or no character columns to update; skip this table
				if ( sql_num_rows($pk_result) == 0 || sql_num_rows($col_result) == 0 )
				{
					$this->skip_tables[] = $table_name;
				}
				# else: add this table
				else
				{
					$temp = new stdClass();
					$temp->character_set_name = $row->character_set_name;
					$temp->table_name = $table_name;
					$temp->columns = array();
					$temp->primary_key = array();

					while ( $col_row = sql_fetch_object($col_result) )
					{
						$temp->columns[] = $col_row->column_name;
					}

					while ( $pk_row = sql_fetch_object($pk_result) )
					{
						$temp->primary_key[] = $pk_row->column_name;
					}

					$this->convert_tables[] = $temp;
				} # end if

			} # end loop: each row returned

		} # end if

	} # end method parse_tables_mysql5()


	/**
	 * This method handles converting the tables and fixing UTF-8 encoding issues
	 * @return string
	 * @access private
	 */
	private function convert()
	{
		$summary = '';

		# loop: each table
		foreach ( $this->convert_tables as $object_table )
		{
			$array_columns = array_merge($object_table->columns, $object_table->primary_key);
			$columns = implode(', ', $array_columns);

			$query = sprintf('SET NAMES %s', $object_table->character_set_name);
			sql_query($query, $this->db_connection);
			// echo $query, LF;

			# Let MySQL auto-convert characters. The problem is that it does this based on the field's character set, not what the actual data is encoded in
			$query = sprintf('ALTER TABLE `%s` CONVERT TO CHARACTER SET %s',
				$object_table->table_name, 
				$this->new_character_set);
			sql_query($query, $this->db_connection);
			// echo $query, LF;

			$query = <<< END
SELECT 
	{$columns}
FROM
	`{$object_table->table_name}`
END;
			// echo $query, LF;
			$result = sql_query($query, $this->db_connection);

			$query = sprintf('SET NAMES %s', $this->new_character_set);
			sql_query($query, $this->db_connection);
			// echo $query, LF;

			# loop: each row
			while ( $row = sql_fetch_object($result) )
			{
				$update_fields = $key_fields = array();

				# loop: each column
				foreach ( $row as $column => $value )
				{
					$utf8_value = ForceUTF8\Encoding::toUTF8($value);

					# if: add to update fields
					if ( in_array($column, $object_table->columns) )
					{
						$update_fields[] = sprintf('`%s` = "%s"', $column, sql_real_escape_string($utf8_value));
					} # end if

					# if: add to primary key(s)
					if ( in_array($column, $object_table->primary_key) )
					{
						$key_fields[] = sprintf('`%s` = "%s"', $column, sql_real_escape_string($value));
					} # end if

				} # end loop: each column

				$sql_update_fields = implode(', ' . LF, $update_fields);
				$sql_key_fields = implode(LF . 'AND ', $key_fields);

				$query = <<< END
UPDATE `{$object_table->table_name}` SET 
	{$sql_update_fields}
WHERE 
	{$sql_key_fields}
END;
				sql_query($query, $this->db_connection);
				// echo $query, LF, LF;
			} # end loop: each row

			$summary .= sprintf('<p> Table "%s" converted to %s. </p>', 
				$object_table->table_name,
				$this->new_character_set
			) . LF;
		} # end loop: each table

		# if: add skipped tables to the summary
		if ( !empty($this->skip_tables) )
		{
			$summary .= sprintf('<p> The following tables were not converted: %s </p>', implode(', ', $this->skip_tables)) . LF;
		} # end if

		return $summary;
	} # end method convert()


	/**
	 * This method handles returning the character set for the supplied collation
	 * @param string $collation 
	 * @access private
	 * @return string
	 */
	private function lookup_character_set($collation)
	{

		# if: character set is not cached yet
		if ( empty($this->collation_character_sets[$collation]) )
		{
			# use the SHOW COLLATION sql to find the corresponding character set
			$query = "SHOW COLLATION LIKE '{$collation}'";
			$result = sql_query($query, $this->db_connection);
			$row = sql_fetch_object($result);
			$character_set = $row->Charset;

			$this->collation_character_sets[$collation] = $character_set;
		}
		# else: use the cached value
		else
		{
			$character_set = $this->collation_character_sets[$collation];
		} # end if

		# return the character set
		return $character_set;
	} # end method lookup_character_set()

}
